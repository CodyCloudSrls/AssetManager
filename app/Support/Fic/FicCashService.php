<?php

namespace App\Support\Fic;

use App\Models\FicCashbookEntry;
use App\Models\FicDocument;
use App\Models\FicPaymentAccount;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

/**
 * Computes the real balance of each bank/cash account (conti correnti) from the FiC
 * cashbook (prima nota): balance = Σ all-time inflows − outflows per account. Stored in
 * fic_payment_accounts to feed the cassa / PFN with real numbers.
 */
class FicCashService
{
    private const START_YEAR = 2021;

    /**
     * A response of this size is treated as "possibly truncated": the FiC cashbook endpoint
     * does not page reliably, so instead of trusting page 2+ we split the date range and
     * re-query smaller windows (see collectRange()). Kept at the endpoint's max page size.
     */
    private const PAGE_SIZE = 1000;

    /** Backstop for the single-day pagination fallback (a day with 1M movements is absurd). */
    private const MAX_PAGES = 50;

    public function __construct(private FicClient $client)
    {
    }

    /** @return array{accounts:int, total:float, reconciled:int} */
    public function sync(): array
    {
        $balances = [];
        $settled = []; // fic document id => amount actually moved (cassa reale)
        $ficCompany = (string) config('services.fic.company_id');
        $localCompany = $this->localCompanyId();
        $cursor = Carbon::create(self::START_YEAR, 1, 1)->startOfMonth();
        $end = Carbon::now()->endOfMonth();

        // Fetch month by month: each month fits comfortably in one response today. If a
        // month ever grows past a single page, collectRange() splits it by date and re-queries
        // — so completeness never depends on the (unreliable) cashbook pagination. Months are
        // disjoint date windows, so no movement is ever fetched or counted twice.
        while ($cursor->lte($end)) {
            $rows = $this->collectRange(
                $cursor->copy()->startOfMonth(),
                $cursor->copy()->endOfMonth(),
            );

            foreach ($rows as $row) {
                $in = (float) ($row['amount_in'] ?? 0);
                $out = (float) ($row['amount_out'] ?? 0);
                if ($in != 0.0) {
                    $name = $row['payment_account_in']['name'] ?? 'n/d';
                    $balances[$name] = ($balances[$name] ?? 0) + $in;
                }
                if ($out != 0.0) {
                    $name = $row['payment_account_out']['name'] ?? 'n/d';
                    $balances[$name] = ($balances[$name] ?? 0) - $out;
                }
                // Track the real money settled against each document (the truth for
                // open receivables/payables, more reliable than the payment plan). Key by
                // DIRECTION + id: issued and received documents share FiC id sequences, so
                // an issued invoice and a received expense can both be #100 — keying by id
                // alone would let one's settlement corrupt the other's paid status.
                $docId = $row['document']['id'] ?? null;
                $docDirection = $this->directionForCashbookDoc($row['document']['type'] ?? null);
                if ($docId && $docDirection !== null) {
                    $key = $docDirection.':'.$docId;
                    $settled[$key] = ($settled[$key] ?? 0) + abs($in) + abs($out);
                }

                // Persist the movement for the per-channel incassi reconciliation.
                $this->upsertEntry($ficCompany, $localCompany, $row);
            }

            $cursor->addMonth();
        }

        $reconciled = $this->reconcileDocuments($settled);

        $total = 0.0;

        foreach ($balances as $name => $balance) {
            $balance = round($balance, 2);
            FicPaymentAccount::updateOrCreate(
                ['fic_company_id' => $ficCompany, 'name' => $name],
                ['balance' => $balance, 'company_id' => $localCompany, 'synced_at' => Carbon::now()],
            );
            $total += $balance;
        }

        return ['accounts' => count($balances), 'total' => round($total, 2), 'reconciled' => $reconciled];
    }

    /**
     * Every cashbook movement in the inclusive [$from, $to] date range, resilient to growth.
     *
     * A single page (PAGE_SIZE rows) is enough for any range today. If a range ever comes
     * back "full" — meaning it might be truncated — we do NOT trust page 2 (the endpoint's
     * pagination is unreliable); instead we split the window in half by date and re-query each
     * half. The halves are disjoint date ranges and a movement carries one date, so it lands
     * in exactly one half: no gaps, no double counting. Recursion bottoms out at a single day;
     * a single saturated day (extremely unlikely) is the only case that falls back to paging,
     * with an id-dedup guard and a warning so it never passes silently.
     *
     * @return array<int, array<string, mixed>>
     */
    private function collectRange(Carbon $from, Carbon $to): array
    {
        $rows = $this->client->cashbook($from->format('Y-m-d'), $to->format('Y-m-d'), 1, self::PAGE_SIZE)['data'] ?? [];

        // Not a full page => this single request already covers the whole range.
        if (count($rows) < self::PAGE_SIZE) {
            return $rows;
        }

        // Full page over more than one day => split by date and recurse. intdiv() keeps the
        // midpoint on a day boundary; the right half starts the day after, so the two windows
        // are disjoint and together cover every day of [$from, $to].
        if ($from->toDateString() !== $to->toDateString()) {
            $mid = $from->copy()->addDays(intdiv($from->diffInDays($to), 2));

            return array_merge(
                $this->collectRange($from->copy(), $mid->copy()),
                $this->collectRange($mid->copy()->addDay(), $to->copy()),
            );
        }

        // Degenerate: one calendar day with >= PAGE_SIZE movements — cannot split further.
        // Walk pages as a last resort, de-duplicating by movement id in case the unreliable
        // pagination repeats rows (double counting would corrupt the balances).
        Log::warning('FiC cashbook: '.$from->toDateString().' has >= '.self::PAGE_SIZE.' movements; using pagination fallback for that day.');

        $byId = [];
        foreach ($rows as $row) {
            $byId[$this->rowKey($row)] = $row;
        }

        $page = 1;
        do {
            $page++;
            $pageRows = $this->client->cashbook($from->format('Y-m-d'), $to->format('Y-m-d'), $page, self::PAGE_SIZE)['data'] ?? [];
            foreach ($pageRows as $row) {
                $byId[$this->rowKey($row)] = $row;
            }
        } while (count($pageRows) >= self::PAGE_SIZE && $page < self::MAX_PAGES);

        return array_values($byId);
    }

    /** A stable de-dup key for a cashbook movement (its id; a content hash if it somehow lacks one). */
    private function rowKey(array $row): string
    {
        return isset($row['id']) ? 'id:'.$row['id'] : 'noid:'.md5((string) json_encode($row));
    }

    /**
     * Reconcile document payment status against the real cashbook settlements: the
     * effective paid amount is max(payment-plan paid, cash actually moved). This fixes
     * receivables/payables inflated by stale FiC payment plans on documents that were
     * actually settled (e.g. via the cashbook).
     *
     * @param  array<string, float>  $settled  "direction:fic_id" => settled amount
     */
    private function reconcileDocuments(array $settled): int
    {
        if ($settled === []) {
            return 0;
        }

        // Group by direction so each fic_id is matched to the document of the RIGHT
        // direction only (issued/received share id sequences — see sync()).
        $byDirection = [];
        foreach ($settled as $key => $amount) {
            [$direction, $ficId] = explode(':', $key, 2);
            $byDirection[$direction][$ficId] = ($byDirection[$direction][$ficId] ?? 0) + (float) $amount;
        }

        $reconciled = 0;
        foreach ($byDirection as $direction => $amounts) {
            foreach (array_chunk($amounts, 500, true) as $chunk) {
                $docs = FicDocument::where('direction', $direction)
                    ->whereIn('fic_id', array_keys($chunk))->get();
                foreach ($docs as $doc) {
                    $cash = round((float) ($chunk[$doc->fic_id] ?? 0), 2);
                    $effectivePaid = round(max((float) $doc->paid_amount, $cash), 2);
                    $effectivePaid = min($effectivePaid, (float) $doc->amount_gross); // never over-pay
                    $paid = $effectivePaid >= (float) $doc->amount_gross - 0.01;

                    if (abs($effectivePaid - (float) $doc->paid_amount) > 0.01 || $doc->paid !== $paid) {
                        $doc->paid_amount = $effectivePaid;
                        $doc->paid = $paid;
                        $doc->save();
                        $reconciled++;
                    }
                }
            }
        }

        return $reconciled;
    }

    /**
     * The fic_documents direction a cashbook movement's document settles, or null when the
     * document type is not mirrored into fic_documents (so it is skipped rather than matched
     * by id alone). Mirrors FicSyncService::TYPES.
     */
    private function directionForCashbookDoc(?string $type): ?string
    {
        return match ($type) {
            'invoice', 'credit_note', 'receipt' => FicDocument::DIRECTION_ISSUED,
            'expense' => FicDocument::DIRECTION_RECEIVED,
            default => null,
        };
    }

    /**
     * Mirror one cashbook movement into fic_cashbook_entries (idempotent on the entry id),
     * picking the direction/amount/account from the entry's declared type.
     *
     * @param  array<string, mixed>  $row
     */
    private function upsertEntry(string $ficCompany, ?int $localCompany, array $row): void
    {
        $ficId = (string) ($row['id'] ?? '');
        if ($ficId === '') {
            return;
        }

        $type = $row['type'] ?? null;
        $in = (float) ($row['amount_in'] ?? 0);
        $out = (float) ($row['amount_out'] ?? 0);

        if ($type === FicCashbookEntry::DIRECTION_IN || ($type === null && $in != 0.0)) {
            $direction = FicCashbookEntry::DIRECTION_IN;
            $amount = $in;
            $account = $row['payment_account_in'] ?? null;
        } elseif ($type === FicCashbookEntry::DIRECTION_OUT || ($type === null && $out != 0.0)) {
            $direction = FicCashbookEntry::DIRECTION_OUT;
            $amount = $out;
            $account = $row['payment_account_out'] ?? null;
        } else {
            return;
        }

        FicCashbookEntry::updateOrCreate(
            ['fic_company_id' => $ficCompany, 'fic_id' => $ficId],
            [
                'entry_date' => $this->date($row['date'] ?? null),
                'direction' => $direction,
                'amount' => round(abs($amount), 2),
                'account_name' => is_array($account) ? ($account['name'] ?? null) : null,
                'account_id' => is_array($account) && isset($account['id']) ? (string) $account['id'] : null,
                'description' => is_string($row['description'] ?? null) ? mb_substr($row['description'], 0, 255) : null,
                'entity_name' => is_string($row['entity_name'] ?? null) ? mb_substr($row['entity_name'], 0, 191) : null,
                'kind' => is_string($row['kind'] ?? null) ? $row['kind'] : null,
                'document_fic_id' => isset($row['document']['id']) ? (int) $row['document']['id'] : null,
                'document_type' => is_string($row['document']['type'] ?? null) ? $row['document']['type'] : null,
                'company_id' => $localCompany,
                'synced_at' => Carbon::now(),
            ]
        );
    }

    private function date($value): ?Carbon
    {
        if (empty($value)) {
            return null;
        }

        try {
            return Carbon::parse($value)->startOfDay();
        } catch (\Throwable) {
            return null;
        }
    }

    private function localCompanyId(): ?int
    {
        $id = config('services.fic.local_company_id');

        return ($id === null || $id === '') ? null : (int) $id;
    }
}
