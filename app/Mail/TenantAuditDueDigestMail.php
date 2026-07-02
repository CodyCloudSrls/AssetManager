<?php

namespace App\Mail;

use App\Models\Tenant;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;

class TenantAuditDueDigestMail extends TenantMailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        Tenant $tenant,
        protected Collection $assets,
        protected int $warningDays,
        protected int $total,
    ) {
        parent::__construct($tenant);
    }

    public function envelope(): Envelope
    {
        return $this->tenantEnvelope(trans('mail.tenant_audit_due_subject', [
            'tenant' => $this->tenant->display_name,
            'count' => $this->total,
        ]));
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'notifications.markdown.tenant-audit-due-digest',
            with: [
                'tenant' => $this->tenant,
                'assets' => $this->assets,
                'warningDays' => $this->warningDays,
                'total' => $this->total,
            ]
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
