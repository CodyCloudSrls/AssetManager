<?php

namespace App\Support\Fic;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;

/**
 * Read-only client for the Fatture in Cloud v2 REST API.
 *
 * Fatture in Cloud stays the fiscal source of truth — this connector only READS
 * (documents, payments, VAT, cashbook, payment accounts) to feed the ERP analytical
 * layer. Credentials come from config/services.php (env-backed); nothing is
 * hard-coded. No write methods are exposed here on purpose.
 */
class FicClient
{
    private string $baseUrl;

    private string $token;

    private string $companyId;

    public function __construct(?string $baseUrl = null, ?string $token = null, ?string $companyId = null)
    {
        $this->baseUrl = rtrim($baseUrl ?? (string) config('services.fic.base_url'), '/');
        $this->token = (string) ($token ?? config('services.fic.token'));
        $this->companyId = (string) ($companyId ?? config('services.fic.company_id'));
    }

    public function isConfigured(): bool
    {
        return $this->baseUrl !== '' && $this->token !== '';
    }

    public function hasCompany(): bool
    {
        return $this->companyId !== '';
    }

    private function request(): PendingRequest
    {
        return Http::withToken($this->token)
            ->acceptJson()
            ->baseUrl($this->baseUrl)
            ->timeout(20)
            // Retry ONLY transient network errors. `throw: false` keeps HTTP errors as returned
            // responses (never retryable exceptions), so a 401/403/422/429 is NOT retried —
            // retrying those just wastes calls and, for 429/quota, digs the hole deeper.
            ->retry(3, 300, fn ($e) => $e instanceof ConnectionException, throw: false);
    }

    /**
     * Single choke point for every GET: refuse the call while a rate-limit cooldown is open,
     * then read the response's remaining-budget headers (and open a cooldown if we're near the
     * hourly/monthly quota) BEFORE surfacing any HTTP error to the caller.
     */
    private function fetch(string $path, array $query = []): array
    {
        FicRateGuard::guard();
        $response = $this->request()->get($path, $query);
        FicRateGuard::record($response);

        return $response->throw()->json() ?? [];
    }

    /** GET /user/info — cheapest call to verify the token works. */
    public function userInfo(): array
    {
        return $this->fetch('/user/info');
    }

    /** GET /user/companies — the companies the token can access. */
    public function companies(): array
    {
        return $this->fetch('/user/companies');
    }

    /**
     * GET /c/{company}/issued_documents — sales documents (invoices, etc.).
     * fieldset=detailed is required so the response carries `category` and
     * `payments_list` (needed for cost reclassification and open-receivables).
     */
    public function issuedDocuments(string $type = 'invoice', int $page = 1, int $perPage = 50): array
    {
        return $this->fetch("/c/{$this->companyId}/issued_documents", [
            'type' => $type,
            'fieldset' => 'detailed',
            'page' => $page,
            'per_page' => $perPage,
        ]);
    }

    /** GET /c/{company}/received_documents — purchase documents (supplier invoices). */
    public function receivedDocuments(string $type = 'expense', int $page = 1, int $perPage = 50): array
    {
        return $this->fetch("/c/{$this->companyId}/received_documents", [
            'type' => $type,
            'fieldset' => 'detailed',
            'page' => $page,
            'per_page' => $perPage,
        ]);
    }

    /** GET /c/{company}/info/payment_accounts — bank/cash accounts. */
    /**
     * GET /c/{company}/cashbook — prima nota movements in a date range. Summing in/out
     * per account all-time gives the real bank/cash balances (conti correnti).
     */
    public function cashbook(string $dateFrom, string $dateTo, int $page = 1, int $perPage = 100): array
    {
        return $this->fetch("/c/{$this->companyId}/cashbook", [
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
            'page' => $page,
            'per_page' => $perPage,
        ]);
    }
}
