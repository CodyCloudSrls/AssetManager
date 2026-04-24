<?php

namespace App\Mail;

use App\Models\Tenant;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;

class TenantTicketSlaDigestMail extends TenantMailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        Tenant $tenant,
        protected Collection $responseBreached,
        protected Collection $responseAtRisk,
        protected Collection $resolutionBreached,
        protected Collection $resolutionAtRisk,
    ) {
        parent::__construct($tenant);
    }

    public function envelope(): Envelope
    {
        $total = $this->responseBreached->count()
            + $this->responseAtRisk->count()
            + $this->resolutionBreached->count()
            + $this->resolutionAtRisk->count();

        return $this->tenantEnvelope(trans('mail.tenant_ticket_sla_subject', [
            'tenant' => $this->tenant->display_name,
            'count' => $total,
        ]));
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'notifications.markdown.tenant-ticket-sla-digest',
            with: [
                'tenant' => $this->tenant,
                'responseBreached' => $this->responseBreached,
                'responseAtRisk' => $this->responseAtRisk,
                'resolutionBreached' => $this->resolutionBreached,
                'resolutionAtRisk' => $this->resolutionAtRisk,
            ]
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
