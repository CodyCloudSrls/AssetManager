<?php

namespace App\Mail;

use App\Models\Tenant;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;

class TenantExpectedCheckinDigestMail extends TenantMailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        Tenant $tenant,
        protected Collection $assets,
        protected int $warningDays,
    ) {
        parent::__construct($tenant);
    }

    public function envelope(): Envelope
    {
        return $this->tenantEnvelope(trans('mail.tenant_expected_checkin_subject', [
            'tenant' => $this->tenant->display_name,
            'count' => $this->assets->count(),
        ]));
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'notifications.markdown.tenant-expected-checkin-digest',
            with: [
                'tenant' => $this->tenant,
                'assets' => $this->assets,
                'warningDays' => $this->warningDays,
            ]
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
