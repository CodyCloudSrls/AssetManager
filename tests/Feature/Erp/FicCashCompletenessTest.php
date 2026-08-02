<?php

namespace Tests\Feature\Erp;

use App\Models\FicPaymentAccount;
use App\Support\Fic\FicCashService;
use App\Support\Fic\FicClient;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Guards the "growth-proof" completeness of the cashbook balance sync: when a month returns a
 * full page (possibly truncated), the service must split the range by date and re-query the
 * halves instead of trusting the endpoint's unreliable pagination — with no gaps and no double
 * counting.
 */
class FicCashCompletenessTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
        config([
            'services.fic.base_url' => 'https://api-v2.fattureincloud.it',
            'services.fic.token' => 'test-token',
            'services.fic.company_id' => '999',
        ]);
    }

    private const HEALTHY = ['ratelimit-monthlyremaining' => '39000', 'ratelimit-hourlyremaining' => '900'];

    /** A movement row as the FiC cashbook returns it. */
    private function row(string $id, float $in, float $out, string $account): array
    {
        return [
            'id' => $id,
            'date' => '2021-01-10',
            'amount_in' => $in,
            'amount_out' => $out,
            'payment_account_in' => $in != 0.0 ? ['id' => 1, 'name' => $account] : null,
            'payment_account_out' => $out != 0.0 ? ['id' => 1, 'name' => $account] : null,
        ];
    }

    public function test_a_saturated_month_is_split_by_date_not_truncated(): void
    {
        // January 2021 comes back "full" (1000 rows) => the naive read would be truncated.
        // Those rows must be DISCARDED in favour of the two half-range re-queries below.
        $januaryFull = [];
        for ($i = 0; $i < 1000; $i++) {
            $januaryFull[] = $this->row('DUMMY'.$i, 999, 0, 'WRONG'); // if counted => balance blows up
        }

        Http::fake([
            '*cashbook*' => function ($request) use ($januaryFull) {
                $from = $request['date_from'];
                $to = $request['date_to'];
                $page = (int) ($request['page'] ?? 1);

                // The full January window (first, un-split query) -> saturated marker set.
                if ($from === '2021-01-01' && $to === '2021-01-31' && $page === 1) {
                    return Http::response(['data' => $januaryFull], 200, self::HEALTHY);
                }
                // First half of January -> two real inflows.
                if ($from === '2021-01-01' && $to === '2021-01-16') {
                    return Http::response(['data' => [
                        $this->row('L1', 100, 0, 'BankA'),
                        $this->row('L2', 50, 0, 'BankA'),
                    ]], 200, self::HEALTHY);
                }
                // Second half of January -> one real outflow.
                if ($from === '2021-01-17' && $to === '2021-01-31') {
                    return Http::response(['data' => [
                        $this->row('R1', 0, 30, 'BankA'),
                    ]], 200, self::HEALTHY);
                }

                // Every other month is empty.
                return Http::response(['data' => []], 200, self::HEALTHY);
            },
        ]);

        $result = app(FicCashService::class)->sync();

        // 100 + 50 - 30 = 120 on the ONE real account. If the split had not happened, the
        // 1000 discarded DUMMY rows would have produced a "WRONG" account worth 999000.
        $this->assertSame(1, $result['accounts'], 'only the real account must exist');
        $account = FicPaymentAccount::where('name', 'BankA')->first();
        $this->assertNotNull($account);
        $this->assertSame(120.0, (float) $account->balance);
        $this->assertNull(FicPaymentAccount::where('name', 'WRONG')->first(), 'truncated page rows must be discarded');
    }

    public function test_a_normal_month_makes_exactly_one_request_per_month(): void
    {
        // No month is saturated -> the recursion never splits; behaviour is identical to before.
        Http::fake(['*cashbook*' => Http::response(['data' => [
            $this->row('N1', 200, 0, 'BankB'),
        ]], 200, self::HEALTHY)]);

        $result = app(FicCashService::class)->sync();

        // One BankB inflow per month, summed over every synced month since START_YEAR.
        $account = FicPaymentAccount::where('name', 'BankB')->first();
        $this->assertNotNull($account);
        $this->assertSame(1, $result['accounts']);

        // Exactly one call per month, no extra pagination probes.
        $months = Http::recorded()->count();
        $this->assertGreaterThan(12, $months); // at least a few years of months
        $this->assertSame($months, Http::recorded(fn ($req) => (int) ($req['page'] ?? 1) === 1)->count(),
            'a non-saturated month must never request page 2');
    }
}
