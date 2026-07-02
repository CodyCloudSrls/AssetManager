<?php

namespace App\Mail;

use App\Models\Tenant;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TenantFicSyncErrorMail extends TenantMailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        Tenant $tenant,
        protected string $errorMessage,
        protected string $failedAt,
    ) {
        parent::__construct($tenant);
    }

    public function envelope(): Envelope
    {
        return $this->tenantEnvelope(trans('mail.tenant_fic_sync_error_subject', [
            'tenant' => $this->tenant->display_name,
        ]));
    }

    public function content(): Content
    {
        return new Content(
            view: 'notifications.html.tenant-fic-sync-error',
            with: ['tenant' => $this->tenant, 'errorMessage' => $this->errorMessage, 'failedAt' => $this->failedAt]
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
