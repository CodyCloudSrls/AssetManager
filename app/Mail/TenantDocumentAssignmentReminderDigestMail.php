<?php

namespace App\Mail;

use App\Models\Tenant;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;

class TenantDocumentAssignmentReminderDigestMail extends TenantMailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        Tenant $tenant,
        protected Collection $dueAssignments,
        protected Collection $overdueAssignments,
        protected int $warningDays,
    ) {
        parent::__construct($tenant);
    }

    public function envelope(): Envelope
    {
        $total = $this->dueAssignments->count() + $this->overdueAssignments->count();

        return $this->tenantEnvelope(trans('mail.tenant_document_assignment_reminder_subject', [
            'tenant' => $this->tenant->display_name,
            'count' => $total,
        ]));
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'notifications.markdown.tenant-document-assignment-reminder-digest',
            with: [
                'tenant' => $this->tenant,
                'dueAssignments' => $this->dueAssignments,
                'overdueAssignments' => $this->overdueAssignments,
                'warningDays' => $this->warningDays,
            ]
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
