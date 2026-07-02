<?php

namespace App\Mail;

use App\Models\Tenant;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;

class TenantInventoryLowDigestMail extends TenantMailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        Tenant $tenant,
        protected Collection $items,
        protected int $threshold,
    ) {
        parent::__construct($tenant);
    }

    public function envelope(): Envelope
    {
        return $this->tenantEnvelope(trans('mail.tenant_inventory_low_subject', [
            'tenant' => $this->tenant->display_name,
            'count' => $this->items->count(),
        ]));
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'notifications.markdown.tenant-inventory-low-digest',
            with: [
                'tenant' => $this->tenant,
                'items' => $this->items,
                'threshold' => $this->threshold,
            ]
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
