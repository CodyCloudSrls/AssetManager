<?php

namespace App\Mail;

use App\Models\Tenant;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;

class TenantFrameworkReviewDigestMail extends TenantMailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        Tenant $tenant,
        protected Collection $frameworks,
        protected int $warningDays,
    ) {
        parent::__construct($tenant);
    }

    public function envelope(): Envelope
    {
        return $this->tenantEnvelope(trans('mail.tenant_framework_review_subject', [
            'tenant' => $this->tenant->display_name,
            'count' => $this->frameworks->count(),
        ]));
    }

    public function content(): Content
    {
        return new Content(
            view: 'notifications.html.tenant-framework-review-digest',
            with: ['tenant' => $this->tenant, 'frameworks' => $this->frameworks, 'warningDays' => $this->warningDays]
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
