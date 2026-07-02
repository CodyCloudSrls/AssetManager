<?php

namespace App\Mail;

use App\Models\Tenant;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;

class TenantContractExpiryDigestMail extends TenantMailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        Tenant $tenant,
        protected Collection $contracts,
        protected int $warningDays,
    ) {
        parent::__construct($tenant);
    }

    public function envelope(): Envelope
    {
        return $this->tenantEnvelope(trans('mail.tenant_contract_expiry_subject', [
            'tenant' => $this->tenant->display_name,
            'count' => $this->contracts->count(),
        ]));
    }

    public function content(): Content
    {
        return new Content(
            view: 'notifications.html.tenant-contract-expiry-digest',
            with: ['tenant' => $this->tenant, 'contracts' => $this->contracts, 'warningDays' => $this->warningDays]
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
