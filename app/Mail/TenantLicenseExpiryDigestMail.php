<?php

namespace App\Mail;

use App\Models\Tenant;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;

class TenantLicenseExpiryDigestMail extends TenantMailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        Tenant $tenant,
        protected Collection $licenses,
        protected int $warningDays,
    ) {
        parent::__construct($tenant);
    }

    public function envelope(): Envelope
    {
        return $this->tenantEnvelope(trans('mail.tenant_license_expiry_subject', [
            'tenant' => $this->tenant->display_name,
            'count' => $this->licenses->count(),
        ]));
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'notifications.markdown.tenant-license-expiry-digest',
            with: [
                'tenant' => $this->tenant,
                'licenses' => $this->licenses,
                'warningDays' => $this->warningDays,
            ]
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
