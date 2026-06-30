<?php

namespace App\Support\Fic;

use App\Models\FicCashbookEntry;
use App\Models\FicDocument;
use App\Models\FicPaymentAccount;
use Illuminate\Support\Carbon;

/**
 * Computes the real balance of each bank/cash account (conti correnti) from the FiC
 * cashbook (prima nota): balance = Σ all-time inflows − outflows per account. Stored in
 * fic_payment_accounts to feed the cassa / PFN with real numbers.
 */
class FicCashService
{
    private const START_YEAR = 2021;

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

        // Fetch month by month: each month fits comfortably in one response, so there is
        // no pagination ambiguity (the cashbook endpoint does not page reliably).
        while ($cursor->lte($end)) {
            $response = $this->client->cashbook(
                $cursor->copy()->startOfMonth()->format('Y-m-d'),
                $cursor->copy()->endOfMonth()->format('Y-m-d'),
                1,
                1000,
            );

            foreach (($response['data'] ?? []) as $row) {
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
                // open receivables/payables, more reliable than the payment plan).
                $docId = $row['document']['id'] ?? null;
                if ($docId) {
                    $settled[$docId] = ($settled[$docId] ?? 0) + abs($in) + abs($out);
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
     * Reconcile document payment status against the real cashbook settlements: the
     * effective paid amount is max(payment-plan paid, cash actually moved). This fixes
     * receivables/payables inflated by stale FiC payment plans on documents that were
     * actually settled (e.g. via the cashbook).
     *
     * @param  array<int|string, float>  $settled  fic document id => settled amount
     */
    private function reconcileDocuments(array $settled): int
    {
        if ($settled === []) {
            return 0;
        }

        $reconciled = 0;
        foreach (array_chunk($settled, 500, true) as $chunk) {
            $docs = FicDocument::whereIn('fic_id', array_keys($chunk))->get();
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

        return $reconciled;
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
