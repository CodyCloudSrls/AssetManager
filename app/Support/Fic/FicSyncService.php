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

    /** @return array{issued:int, received:int} */
    public function syncAll(): array
    {
        return [
            'issued' => $this->syncDirection(FicDocument::DIRECTION_ISSUED),
            'received' => $this->syncDirection(FicDocument::DIRECTION_RECEIVED),
        ];
    }

    private function syncDirection(string $direction): int
    {
        $count = 0;
        $page = 1;

        do {
            $response = $direction === FicDocument::DIRECTION_ISSUED
                ? $this->client->issuedDocuments('invoice', $page, self::PAGE_SIZE)
                : $this->client->receivedDocuments('expense', $page, self::PAGE_SIZE);

            foreach (($response['data'] ?? []) as $doc) {
                $this->upsert($direction, $doc);
                $count++;
            }

            $currentPage = (int) ($response['current_page'] ?? $page);
            $lastPage = (int) ($response['last_page'] ?? $currentPage);
            $page = $currentPage + 1;
        } while ($currentPage < $lastPage && $page <= self::MAX_PAGES);

        return $count;
    }

    private function upsert(string $direction, array $doc): void
    {
        [$paid, $paidAmount, $dueOn] = $this->paymentSummary($doc['payments_list'] ?? []);

        FicDocument::updateOrCreate(
            [
                'fic_company_id' => (string) config('services.fic.company_id'),
                'direction' => $direction,
                'fic_id' => (int) ($doc['id'] ?? 0),
            ],
            [
                'doc_type' => $doc['type'] ?? null,
                'number' => $doc['number'] ?? null,
                'issued_on' => $this->date($doc['date'] ?? null),
                'due_on' => $dueOn ?? $this->date($doc['next_due_date'] ?? null),
                'entity_name' => $doc['entity']['name'] ?? null,
                'entity_vat' => $doc['entity']['vat_number'] ?? null,
                'amount_net' => (float) ($doc['amount_net'] ?? 0),
                'amount_vat' => (float) ($doc['amount_vat'] ?? 0),
                'amount_gross' => (float) ($doc['amount_gross'] ?? 0),
                'currency' => $doc['currency'] ?? 'EUR',
                'paid' => $paid,
                'paid_amount' => $paidAmount,
                'company_id' => $this->localCompanyId(),
                'synced_at' => Carbon::now(),
            ]
        );
    }

    /**
     * Summarize a FiC payments_list into [allPaid, paidAmount, earliestUnpaidDueDate].
     *
     * @return array{0:bool,1:float,2:?Carbon}
     */
    private function paymentSummary(array $payments): array
    {
        if ($payments === []) {
            return [false, 0.0, null];
        }

        $paidAmount = 0.0;
        $earliestDue = null;
        $allPaid = true;

        foreach ($payments as $payment) {
            $amount = (float) ($payment['amount'] ?? 0);
            if (($payment['status'] ?? null) === 'paid') {
                $paidAmount += $amount;

                continue;
            }

            $allPaid = false;
            $due = $this->date($payment['due_date'] ?? null);
            if ($due && (is_null($earliestDue) || $due->lt($earliestDue))) {
                $earliestDue = $due;
            }
        }

        return [$allPaid, round($paidAmount, 2), $earliestDue];
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
