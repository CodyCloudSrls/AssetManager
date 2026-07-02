<?php

namespace App\Support\Tenants;

use App\Mail\TenantAssetRenewalDigestMail;
use App\Mail\TenantAuditDueDigestMail;
use App\Mail\TenantDocumentAssignmentReminderDigestMail;
use App\Mail\TenantDocumentReviewDigestMail;
use App\Mail\TenantExpectedCheckinDigestMail;
use App\Mail\TenantInventoryLowDigestMail;
use App\Mail\TenantLicenseExpiryDigestMail;
use App\Mail\TenantTestMail;
use App\Mail\TenantTicketNotificationMail;
use App\Mail\TenantTicketSlaDigestMail;
use App\Mail\TenantWarrantyDigestMail;
use App\Models\Accessory;
use App\Models\Actionlog;
use App\Models\Asset;
use App\Models\Company;
use App\Models\Component;
use App\Models\Consumable;
use App\Models\Document;
use App\Models\DocumentAssignment;
use App\Models\License;
use App\Models\Setting;
use App\Models\Supplier;
use App\Models\Tenant;
use App\Models\Ticket;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Mail\Mailable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Mail;

class TenantMailNotificationService
{
    public function sendTicketCreated(Ticket $ticket): void
    {
        $tenant = $this->tenantFromCompanyId($ticket->company_id);

        if (! $tenant || ! $tenant->notificationEventEnabled(Tenant::MAIL_EVENT_TICKET_CREATED)) {
            return;
        }

        $ticket->loadMissing(['company', 'requester', 'assignee', 'relatedUser', 'asset', 'document', 'location', 'type', 'status', 'priority']);

        $this->sendToTenant($tenant, new TenantTicketNotificationMail(
            $tenant,
            $ticket,
            Tenant::MAIL_EVENT_TICKET_CREATED,
            null,
            $ticket->created_by_display_name
        ));

        if ($ticket->assignee_id) {
            $this->sendTicketAssigned($ticket, null);
        }
    }

    public function sendTicketAssigned(Ticket $ticket, ?int $previousAssigneeId): void
    {
        if (! $ticket->assignee_id || (int) $ticket->assignee_id === (int) $previousAssigneeId) {
            return;
        }

        $tenant = $this->tenantFromCompanyId($ticket->company_id);

        if (! $tenant || ! $tenant->notificationEventEnabled(Tenant::MAIL_EVENT_TICKET_ASSIGNED)) {
            return;
        }

        $ticket->loadMissing(['company', 'requester', 'assignee', 'relatedUser', 'asset', 'document', 'location', 'type', 'status', 'priority']);

        $this->sendToTenant($tenant, new TenantTicketNotificationMail(
            $tenant,
            $ticket,
            Tenant::MAIL_EVENT_TICKET_ASSIGNED,
            null,
            auth()->user()?->display_name
        ));
    }

    public function sendTicketPublicReply(Ticket $ticket, Actionlog $comment, ?string $actorName = null): void
    {
        $tenant = $this->tenantFromCompanyId($ticket->company_id);

        if (! $tenant || ! $tenant->notificationEventEnabled(Tenant::MAIL_EVENT_TICKET_PUBLIC_REPLY)) {
            return;
        }

        $ticket->loadMissing(['company', 'requester', 'assignee', 'relatedUser', 'asset', 'document', 'location', 'type', 'status', 'priority']);

        $actorName = $actorName
            ?: $comment->adminuser?->display_name
            ?: $ticket->guest_name
            ?: $ticket->guest_email
            ?: trans('admin/tickets/general.public_user');

        $this->sendToTenant($tenant, new TenantTicketNotificationMail(
            $tenant,
            $ticket,
            Tenant::MAIL_EVENT_TICKET_PUBLIC_REPLY,
            $comment,
            $actorName
        ));
    }

    public function sendTicketSlaDigest(Tenant $tenant): int
    {
        if (! $tenant->notificationEventEnabled(Tenant::MAIL_EVENT_TICKET_SLA_ALERT)) {
            return 0;
        }

        $companyIds = $tenant->activeCompanyIds();

        if (count($companyIds) === 0) {
            return 0;
        }

        $now = Carbon::now();
        $riskThreshold = $now->copy()->addHours(24);
        $baseQuery = Ticket::query()
            ->whereIn('company_id', $companyIds)
            ->open()
            ->with(['company', 'assignee', 'requester', 'status', 'priority', 'type']);

        $responseBreached = (clone $baseQuery)
            ->whereNull('first_responded_at')
            ->whereNotNull('first_response_due_at')
            ->where('first_response_due_at', '<', $now)
            ->orderBy('first_response_due_at')
            ->get();

        $responseAtRisk = (clone $baseQuery)
            ->whereNull('first_responded_at')
            ->whereNotNull('first_response_due_at')
            ->whereBetween('first_response_due_at', [$now, $riskThreshold])
            ->orderBy('first_response_due_at')
            ->get();

        $resolutionBreached = (clone $baseQuery)
            ->whereNotNull('resolution_due_at')
            ->where('resolution_due_at', '<', $now)
            ->orderBy('resolution_due_at')
            ->get();

        $resolutionAtRisk = (clone $baseQuery)
            ->whereNotNull('resolution_due_at')
            ->whereBetween('resolution_due_at', [$now, $riskThreshold])
            ->orderBy('resolution_due_at')
            ->get();

        $total = $responseBreached->count() + $responseAtRisk->count() + $resolutionBreached->count() + $resolutionAtRisk->count();

        if ($total === 0) {
            return 0;
        }

        $this->sendToTenant($tenant, new TenantTicketSlaDigestMail(
            $tenant,
            $responseBreached,
            $responseAtRisk,
            $resolutionBreached,
            $resolutionAtRisk
        ));

        return $total;
    }

    public function sendDocumentReviewDigest(Tenant $tenant): int
    {
        if (! $tenant->notificationEventEnabled(Tenant::MAIL_EVENT_DOCUMENT_REVIEW_DUE)) {
            return 0;
        }

        $companyIds = $tenant->activeCompanyIds();

        if (count($companyIds) === 0) {
            return 0;
        }

        $warningDays = $tenant->documentReviewWarningDays();

        $dueDocuments = Document::query()
            ->whereIn('company_id', $companyIds)
            ->dueForReview($warningDays)
            ->with(['company', 'owner', 'type', 'framework'])
            ->orderBy('next_review_at')
            ->get();

        $overdueDocuments = Document::query()
            ->whereIn('company_id', $companyIds)
            ->overdueForReview()
            ->with(['company', 'owner', 'type', 'framework'])
            ->orderBy('next_review_at')
            ->get();

        $total = $dueDocuments->count() + $overdueDocuments->count();

        if ($total === 0) {
            return 0;
        }

        $this->sendToTenant($tenant, new TenantDocumentReviewDigestMail(
            $tenant,
            $dueDocuments,
            $overdueDocuments,
            $warningDays
        ));

        return $total;
    }

    /**
     * Tenant-aware digest of assets whose renewal/expiry is due or within N days
     * (domains, IPs, monitoring, certificates), scoped to the tenant's companies and
     * localized to the tenant language by sendToTenant().
     */
    public function sendAssetRenewalDigest(Tenant $tenant, int $warningDays = 30): int
    {
        if (! $tenant->notificationEventEnabled(Tenant::MAIL_EVENT_ASSET_RENEWAL_DUE)) {
            return 0;
        }

        $companyIds = $tenant->activeCompanyIds();

        if (count($companyIds) === 0) {
            return 0;
        }

        $assets = Asset::query()
            ->whereIn('assets.company_id', $companyIds)
            ->ExpiringRenewal($warningDays)
            ->with(['model', 'company'])
            ->orderBy('renewal_date')
            ->get();

        if ($assets->isEmpty()) {
            return 0;
        }

        $this->sendToTenant($tenant, new TenantAssetRenewalDigestMail($tenant, $assets, $warningDays));

        return $assets->count();
    }

    /**
     * Tenant-aware version of the stock "expiring warranties/EOL" alert: assets whose
     * warranty or end-of-life falls within N days, scoped to the tenant companies.
     * The default horizon reuses the platform alert_interval setting.
     */
    public function sendWarrantyDigest(Tenant $tenant, ?int $warningDays = null): int
    {
        if (! $tenant->notificationEventEnabled(Tenant::MAIL_EVENT_ASSET_WARRANTY_DUE)) {
            return 0;
        }

        $companyIds = $tenant->activeCompanyIds();

        if (count($companyIds) === 0) {
            return 0;
        }

        $days = $warningDays ?? max(1, (int) (Setting::getSettings()?->alert_interval ?? 30));

        // getExpiringWarrantyOrEol computes the warranty window in PHP, so we scope the
        // resulting collection to the tenant companies instead of at the query level.
        $assets = Asset::getExpiringWarrantyOrEol($days)
            ->filter(fn ($asset) => in_array((int) $asset->company_id, $companyIds, true))
            ->values();

        if ($assets->isEmpty()) {
            return 0;
        }

        $assets->load('company');

        $this->sendToTenant($tenant, new TenantWarrantyDigestMail($tenant, $assets, $days));

        return $assets->count();
    }

    /**
     * Tenant-aware version of the stock "expiring licenses" alert, scoped to the tenant
     * companies. The default horizon reuses the platform alert_interval setting.
     */
    public function sendLicenseExpiryDigest(Tenant $tenant, ?int $warningDays = null): int
    {
        if (! $tenant->notificationEventEnabled(Tenant::MAIL_EVENT_LICENSE_EXPIRY_DUE)) {
            return 0;
        }

        $companyIds = $tenant->activeCompanyIds();

        if (count($companyIds) === 0) {
            return 0;
        }

        $days = $warningDays ?? max(1, (int) (Setting::getSettings()?->alert_interval ?? 30));

        // ExpiringLicenses uses AND-grouped closures at its root, so a leading whereIn is safe.
        $licenses = License::query()
            ->whereIn('licenses.company_id', $companyIds)
            ->ExpiringLicenses($days)
            ->with(['company', 'category'])
            ->orderBy('expiration_date')
            ->orderBy('termination_date')
            ->get();

        if ($licenses->isEmpty()) {
            return 0;
        }

        $this->sendToTenant($tenant, new TenantLicenseExpiryDigestMail($tenant, $licenses, $days));

        return $licenses->count();
    }

    /**
     * Tenant-aware version of the stock "low inventory" alert. Scopes consumables,
     * accessories, components and licenses to the tenant companies (asset models have no
     * company and are intentionally left to the platform-wide alert).
     */
    public function sendInventoryLowDigest(Tenant $tenant, ?int $threshold = null): int
    {
        if (! $tenant->notificationEventEnabled(Tenant::MAIL_EVENT_INVENTORY_LOW)) {
            return 0;
        }

        $companyIds = $tenant->activeCompanyIds();

        if (count($companyIds) === 0) {
            return 0;
        }

        $threshold = $threshold ?? max(0, (int) (Setting::getSettings()?->alert_threshold ?? 0));

        $items = $this->lowInventoryForCompanies($companyIds, $threshold);

        if ($items->isEmpty()) {
            return 0;
        }

        $this->sendToTenant($tenant, new TenantInventoryLowDigestMail($tenant, $items, $threshold));

        return $items->count();
    }

    /**
     * Tenant-aware version of the stock "expected checkin" admin digest: assets due or
     * overdue for checkin, scoped to the tenant companies. The DueOrOverdueForCheckin scope
     * mixes where/orWhere, so it is wrapped in a closure to keep the company filter intact.
     */
    public function sendExpectedCheckinDigest(Tenant $tenant, ?int $warningDays = null): int
    {
        if (! $tenant->notificationEventEnabled(Tenant::MAIL_EVENT_EXPECTED_CHECKIN_DUE)) {
            return 0;
        }

        $companyIds = $tenant->activeCompanyIds();

        if (count($companyIds) === 0) {
            return 0;
        }

        $settings = Setting::getSettings() ?? new Setting;
        $days = $warningDays ?? max(0, (int) ($settings->due_checkin_days ?? 0));

        $assets = Asset::query()
            ->whereNull('assets.deleted_at')
            ->whereIn('assets.company_id', $companyIds)
            ->where(function ($query) use ($settings) {
                $query->DueOrOverdueForCheckin($settings);
            })
            ->with(['company', 'assignedTo'])
            ->orderBy('assets.expected_checkin')
            ->get();

        if ($assets->isEmpty()) {
            return 0;
        }

        $this->sendToTenant($tenant, new TenantExpectedCheckinDigestMail($tenant, $assets, $days));

        return $assets->count();
    }

    /**
     * Tenant-aware version of the stock "upcoming audits" alert: assets due or overdue for
     * audit, scoped to the tenant companies. The list is capped at 30 rows but the subject
     * and body report the full count. dueOrOverdueForAudit mixes where/orWhere, so it is
     * wrapped in a closure to keep the company filter intact.
     */
    public function sendAuditDueDigest(Tenant $tenant, ?int $warningDays = null): int
    {
        if (! $tenant->notificationEventEnabled(Tenant::MAIL_EVENT_AUDIT_DUE)) {
            return 0;
        }

        $companyIds = $tenant->activeCompanyIds();

        if (count($companyIds) === 0) {
            return 0;
        }

        $settings = Setting::getSettings() ?? new Setting;
        $days = $warningDays ?? max(0, (int) ($settings->audit_warning_days ?? 0));

        $query = Asset::query()
            ->whereNull('assets.deleted_at')
            ->whereIn('assets.company_id', $companyIds)
            ->where(function ($inner) use ($settings) {
                $inner->dueOrOverdueForAudit($settings);
            });

        $total = (clone $query)->count();

        if ($total === 0) {
            return 0;
        }

        $assets = $query->with(['company', 'assignedTo'])
            ->orderBy('assets.next_audit_date')
            ->limit(30)
            ->get();

        $this->sendToTenant($tenant, new TenantAuditDueDigestMail($tenant, $assets, $days, $total));

        return $total;
    }

    /**
     * Company-scoped low-inventory scan mirroring Helper::checkLowInventory(), but limited to
     * the given companies and enriched with each item's company name for the digest.
     *
     * @param  array<int>  $companyIds
     */
    private function lowInventoryForCompanies(array $companyIds, int $threshold): Collection
    {
        $items = collect();

        $consumables = Consumable::whereIn('company_id', $companyIds)->whereNotNull('min_amt')->with('company')->get();
        foreach ($consumables as $consumable) {
            $avail = $consumable->numRemaining();
            if ($avail <= ($consumable->min_amt + $threshold)) {
                $items->push($this->lowInventoryRow($consumable, 'consumables', $avail));
            }
        }

        $accessories = Accessory::withCount('checkouts as checkouts_count')
            ->whereIn('company_id', $companyIds)->whereNotNull('min_amt')->with('company')->get();
        foreach ($accessories as $accessory) {
            $avail = $accessory->qty - $accessory->checkouts_count;
            if ($avail <= ($accessory->min_amt + $threshold)) {
                $items->push($this->lowInventoryRow($accessory, 'accessories', $avail));
            }
        }

        $components = Component::whereIn('company_id', $companyIds)->whereNotNull('min_amt')->with('company')->get();
        foreach ($components as $component) {
            $avail = $component->numRemaining();
            if ($avail <= ($component->min_amt + $threshold)) {
                $items->push($this->lowInventoryRow($component, 'components', $avail));
            }
        }

        $licenses = License::whereIn('company_id', $companyIds)->where('min_amt', '>', 0)->with('company')->get();
        foreach ($licenses as $license) {
            $avail = $license->remaincount();
            if ($avail <= ($license->min_amt + $threshold)) {
                $items->push($this->lowInventoryRow($license, 'licenses', $avail));
            }
        }

        return $items;
    }

    private function lowInventoryRow($item, string $type, int $remaining): array
    {
        return [
            'id' => $item->id,
            'name' => $item->name,
            'type' => $type,
            'remaining' => $remaining,
            'min_amt' => $item->min_amt,
            'company' => $item->company?->name,
        ];
    }

    public function sendDocumentAssignmentReminderDigest(Tenant $tenant): int
    {
        if (! $tenant->notificationEventEnabled(Tenant::MAIL_EVENT_DOCUMENT_ASSIGNMENT_REMINDER)) {
            return 0;
        }

        $companyIds = $tenant->activeCompanyIds();

        if (count($companyIds) === 0) {
            return 0;
        }

        $warningDays = $tenant->documentReviewWarningDays();
        $today = Carbon::today();
        $warningLimit = $today->copy()->addDays($warningDays);
        $baseQuery = DocumentAssignment::query()
            ->whereIn('company_id', $companyIds)
            ->whereIn('assignable_type', [User::class, Supplier::class])
            ->whereIn('relation_type', [
                DocumentAssignment::RELATION_REQUIRED_FOR,
                DocumentAssignment::RELATION_EVIDENCE_FOR,
            ])
            ->whereHas('document', fn ($query) => $query->whereNull('documents.deleted_at'))
            ->where('status', '!=', DocumentAssignment::STATUS_REVOKED)
            ->where('approval_status', '!=', DocumentAssignment::APPROVAL_APPROVED)
            ->with(['document.type', 'company', 'issuer', 'reviewer', 'assignable']);

        $dueAssignments = (clone $baseQuery)
            ->whereNotNull('renewal_due_at')
            ->whereDate('renewal_due_at', '>=', $today->toDateString())
            ->whereDate('renewal_due_at', '<=', $warningLimit->toDateString())
            ->orderBy('renewal_due_at')
            ->get();

        $overdueAssignments = (clone $baseQuery)
            ->where(function ($query) use ($today) {
                $query->whereDate('expires_at', '<', $today->toDateString())
                    ->orWhereDate('renewal_due_at', '<', $today->toDateString());
            })
            ->orderByRaw('COALESCE(renewal_due_at, expires_at) asc')
            ->get();

        $total = $dueAssignments->count() + $overdueAssignments->count();

        if ($total === 0) {
            return 0;
        }

        $this->sendToTenant($tenant, new TenantDocumentAssignmentReminderDigestMail(
            $tenant,
            $dueAssignments,
            $overdueAssignments,
            $warningDays
        ));

        return $total;
    }

    public function tenantFromCompanyId(?int $companyId): ?Tenant
    {
        if (! $companyId) {
            return null;
        }

        $tenantId = Company::withoutGlobalScopes()
            ->where('id', $companyId)
            ->value('tenant_id');

        return $tenantId ? Tenant::query()->find($tenantId) : null;
    }

    /**
     * Send a deliverability test email to the tenant's recipients through the tenant's
     * own SMTP (or the platform fallback). Bypasses per-event toggles on purpose.
     * Returns the number of recipients, or 0 when none are configured.
     */
    public function sendTestEmail(Tenant $tenant): int
    {
        $recipients = $tenant->notificationRecipients();

        if (count($recipients) === 0) {
            return 0;
        }

        $this->sendToTenant($tenant, new TenantTestMail($tenant));

        return count($recipients);
    }

    private function sendToTenant(Tenant $tenant, Mailable $mailable): void
    {
        $recipients = $tenant->notificationRecipients();

        if (count($recipients) === 0) {
            return;
        }

        // Send through the tenant's own SMTP when configured (else the platform mailer),
        // and always render in the tenant's own language (it-IT / en-US).
        Mail::mailer($this->tenantMailer($tenant))
            ->to($recipients)
            ->locale($tenant->defaultLocale())
            ->send($mailable);
    }

    /**
     * Resolve the mailer name for a tenant: a runtime SMTP mailer built from the tenant's
     * own settings when configured, otherwise null — which makes Laravel use the platform
     * default mailer (the fallback).
     */
    private function tenantMailer(Tenant $tenant): ?string
    {
        $config = $tenant->customMailerConfig();

        if (is_null($config)) {
            return null;
        }

        $name = 'tenant_'.$tenant->id;
        config()->set('mail.mailers.'.$name, $config);

        return $name;
    }
}
