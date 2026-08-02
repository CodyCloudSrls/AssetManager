<?php

namespace Tests\Feature\Erp;

use App\Support\Fic\FicClient;
use App\Support\Fic\FicRateGuard;
use App\Support\Fic\FicRateLimitException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class FicRateGuardTest extends TestCase
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

    private function client(): FicClient
    {
        return new FicClient;
    }

    public function test_low_monthly_remaining_opens_a_cooldown(): void
    {
        Http::fake([
            '*' => Http::response(['data' => []], 200, [
                'ratelimit-monthlyremaining' => '100',   // below the 2000 floor
                'ratelimit-hourlyremaining' => '900',
            ]),
        ]);

        $this->client()->issuedDocuments('invoice', 1, 50);

        $this->assertTrue(FicRateGuard::isCoolingDown(), 'a near-empty monthly budget must open a cooldown');
    }

    public function test_hourly_floor_opens_a_cooldown(): void
    {
        Http::fake([
            '*' => Http::response(['data' => []], 200, [
                'ratelimit-monthlyremaining' => '30000',
                'ratelimit-hourlyremaining' => '10',     // below the 50 floor
            ]),
        ]);

        $this->client()->issuedDocuments('invoice', 1, 50);

        $this->assertTrue(FicRateGuard::isCoolingDown());
    }

    public function test_healthy_budget_does_not_open_a_cooldown(): void
    {
        Http::fake([
            '*' => Http::response(['data' => []], 200, [
                'ratelimit-monthlyremaining' => '34000',
                'ratelimit-hourlyremaining' => '900',
            ]),
        ]);

        $this->client()->issuedDocuments('invoice', 1, 50);

        $this->assertFalse(FicRateGuard::isCoolingDown());
    }

    public function test_429_opens_a_cooldown_and_is_not_retried(): void
    {
        Http::fake([
            '*' => Http::response(['error' => 'slow down'], 429, ['retry-after' => '120']),
        ]);

        try {
            $this->client()->issuedDocuments('invoice', 1, 50);
            $this->fail('a 429 should surface as an exception');
        } catch (\Throwable $e) {
            // expected
        }

        Http::assertSentCount(1); // NEVER retried
        $this->assertTrue(FicRateGuard::isCoolingDown(), '429 must open a cooldown');
    }

    public function test_401_is_not_retried(): void
    {
        Http::fake([
            '*' => Http::response(['error' => 'invalid_token'], 401),
        ]);

        try {
            $this->client()->userInfo();
        } catch (\Throwable $e) {
            // expected
        }

        Http::assertSentCount(1); // an expired token must not be hammered
    }

    public function test_guard_blocks_calls_while_cooling_down(): void
    {
        // Open a cooldown 10 minutes into the future.
        Cache::put('fic:cooldown_until', now()->addMinutes(10)->toDateTimeString(), now()->addMinutes(10));
        Http::fake(['*' => Http::response(['data' => []], 200)]);

        $this->expectException(FicRateLimitException::class);
        try {
            $this->client()->issuedDocuments('invoice', 1, 50);
        } finally {
            Http::assertNothingSent(); // no call is made while cooling down
        }
    }

    public function test_sync_command_skips_gracefully_during_cooldown(): void
    {
        Cache::put('fic:cooldown_until', now()->addHour()->toDateTimeString(), now()->addHour());
        Http::fake(['*' => Http::response(['data' => []], 200)]);

        $this->artisan('fic:sync')
            ->assertExitCode(0); // SUCCESS = skipped, not a failure

        Http::assertNothingSent();
    }

    public function test_sync_failure_leaves_a_log_trace(): void
    {
        // A dead token (401) is a real failure — it must leave a grep-able ERROR in the log,
        // because the tenant e-mail alert goes to MAIL_MAILER=log (nobody reads it).
        Http::fake(['*' => Http::response(['error' => 'invalid_token'], 401)]);
        Log::shouldReceive('error')
            ->atLeast()->once()
            ->withArgs(fn ($message) => str_contains((string) $message, 'FiC document sync failed'));

        $this->artisan('fic:sync')->assertExitCode(1); // FAILURE
    }
}
