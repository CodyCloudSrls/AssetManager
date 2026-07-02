<?php

namespace App\Mail;

use App\Models\Tenant;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;

class TenantNotuleUnpaidDigestMail extends TenantMailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        Tenant $tenant,
        protected Collection $notule,
        protected float $residuoTotal,
    ) {
        parent::__construct($tenant);
    }

    public function envelope(): Envelope
    {
        return $this->tenantEnvelope(trans('mail.tenant_notule_unpaid_subject', [
            'tenant' => $this->tenant->display_name,
            'count' => $this->notule->count(),
        ]));
    }

    public function content(): Content
    {
        return new Content(
            view: 'notifications.html.tenant-notule-unpaid-digest',
            with: ['tenant' => $this->tenant, 'notule' => $this->notule, 'residuoTotal' => $this->residuoTotal]
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
