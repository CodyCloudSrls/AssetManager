<?php

namespace App\Support\Fic;

use App\Models\FicDocument;
use Illuminate\Support\Carbon;

/**
 * Pulls Fatture in Cloud documents into the read-only fic_documents mirror.
 * Idempotent: every row is upserted on (fic_company_id, direction, fic_id), so
 * re-running the sync converges instead of duplicating. No data is written back to FiC.
 */
class FicSyncService
{
    private const PAGE_SIZE = 100;

    private const MAX_PAGES = 1000; // runaway guard

    public function __construct(private FicClient $client)
    {
    }

    /**
     * Document types synced per direction. Credit notes (note di credito, issued AND received)
     * net AGAINST invoices/costs, so they are stored with negative amounts. Receipts (ricevute)
     * are sales paid on issue. `passive_credit_note` = supplier credit notes: they REDUCE costs,
     * so they must be synced (otherwise the controllo di gestione overstates costs).
     * NB: `self_invoice` (autofatture reverse-charge, issued+received) is deliberately NOT here —
     * its cost/revenue treatment is an accounting decision, left to the bookkeeper.
     *
     * @var array<string, string[]>
     */
    private const TYPES = [
        FicDocument::DIRECTION_ISSUED => ['invoice', 'credit_note', 'receipt'],
        FicDocument::DIRECTION_RECEIVED => ['expense', 'passive_credit_note'],
    ];

    /** Types whose amounts NET (subtract) rather than add — stored with a negative sign. */
    private const CREDIT_NOTE_TYPES = ['credit_note', 'passive_credit_note'];

    /** @return array{issued:int, received:int} */
    public function syncAll(): array
    {
        $counts = [];
        foreach (self::TYPES as $direction => $types) {
            $total = 0;
            foreach ($types as $type) {
                $total += $this->syncType($direction, $type);
            }
            $counts[$direction === FicDocument::DIRECTION_ISSUED ? 'issued' : 'received'] = $total;
        }

        return $counts;
    }

    private function syncType(string $direction, string $type): int
    {
        $count = 0;
        $page = 1;

        do {
            $response = $direction === FicDocument::DIRECTION_ISSUED
                ? $this->client->issuedDocuments($type, $page, self::PAGE_SIZE)
                : $this->client->receivedDocuments($type, $page, self::PAGE_SIZE);

            foreach (($response['data'] ?? []) as $doc) {
                $this->upsert($direction, $type, $doc);
                $count++;
            }

            $currentPage = (int) ($response['current_page'] ?? $page);
            $lastPage = (int) ($response['last_page'] ?? $currentPage);
            $page = $currentPage + 1;
        } while ($currentPage < $lastPage && $page <= self::MAX_PAGES);

        return $count;
    }

    private function upsert(string $direction, string $type, array $doc): void
    {
        [$paid, $paidAmount, $dueOn, $paidOn] = $this->paymentSummary($doc['payments_list'] ?? []);

        // Credit notes (issued AND passive) reduce revenue/cost/receivables/payables: store them
        // negative so every aggregate (ricavi, costi, IVA, crediti, debiti) nets them automatically.
        $sign = in_array($type, self::CREDIT_NOTE_TYPES, true) ? -1 : 1;

        FicDocument::updateOrCreate(
            [
                'fic_company_id' => (string) config('services.fic.company_id'),
                'direction' => $direction,
                'fic_id' => (int) ($doc['id'] ?? 0),
            ],
            [
                'doc_type' => is_string($doc['type'] ?? null) ? $doc['type'] : $type,
                'category' => is_string($doc['category'] ?? null) ? $doc['category'] : null,
                'number' => is_scalar($doc['number'] ?? null) ? (string) $doc['number'] : null,
                'issued_on' => $this->date($doc['date'] ?? null),
                'due_on' => $dueOn ?? $this->date($doc['next_due_date'] ?? null),
                'paid_on' => $paidOn,
                'entity_name' => is_string($doc['entity']['name'] ?? null) ? $doc['entity']['name'] : null,
                'entity_vat' => is_string($doc['entity']['vat_number'] ?? null) ? $doc['entity']['vat_number'] : null,
                'amount_net' => $sign * (float) ($doc['amount_net'] ?? 0),
                'amount_vat' => $sign * (float) ($doc['amount_vat'] ?? 0),
                'amount_gross' => $sign * (float) ($doc['amount_gross'] ?? 0),
                'currency' => is_string($doc['currency'] ?? null) ? $doc['currency'] : (is_array($doc['currency'] ?? null) ? ($doc['currency']['id'] ?? 'EUR') : 'EUR'),
                'paid' => $paid,
                'paid_amount' => $sign * $paidAmount,
                'company_id' => $this->localCompanyId(),
                'synced_at' => Carbon::now(),
            ]
        );
    }

    /**
     * Summarize a FiC payments_list into [allPaid, paidAmount, earliestUnpaidDueDate, lastPaidDate].
     * lastPaidDate is the cash-realization date used by the flussi di cassa.
     *
     * @return array{0:bool,1:float,2:?Carbon,3:?Carbon}
     */
    private function paymentSummary(array $payments): array
    {
        if ($payments === []) {
            return [false, 0.0, null, null];
        }

        $paidAmount = 0.0;
        $earliestDue = null;
        $lastPaid = null;
        $allPaid = true;

        foreach ($payments as $payment) {
            $amount = (float) ($payment['amount'] ?? 0);
            if (($payment['status'] ?? null) === 'paid') {
                $paidAmount += $amount;
                $paid = $this->date($payment['paid_date'] ?? null);
                if ($paid && (is_null($lastPaid) || $paid->gt($lastPaid))) {
                    $lastPaid = $paid;
                }

                continue;
            }

            $allPaid = false;
            $due = $this->date($payment['due_date'] ?? null);
            if ($due && (is_null($earliestDue) || $due->lt($earliestDue))) {
                $earliestDue = $due;
            }
        }

        return [$allPaid, round($paidAmount, 2), $earliestDue, $lastPaid];
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
