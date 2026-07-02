<?php

namespace App\Support\Tenants;

use App\Mail\TenantDocumentAssignmentReminderDigestMail;
use App\Mail\TenantDocumentReviewDigestMail;
use App\Mail\TenantTicketNotificationMail;
use App\Mail\TenantTicketSlaDigestMail;
use App\Models\Actionlog;
use App\Models\Company;
use App\Models\Document;
use App\Models\DocumentAssignment;
use App\Models\Supplier;
use App\Models\Tenant;
use App\Models\Ticket;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Mail\Mailable;
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

    private function sendToTenant(Tenant $tenant, Mailable $mailable): void
    {
        $recipients = $tenant->notificationRecipients();

        if (count($recipients) === 0) {
            return;
        }

        // Every tenant email renders in the tenant's own language (it-IT / en-US).
        Mail::to($recipients)->locale($tenant->defaultLocale())->send($mailable);
    }
}
