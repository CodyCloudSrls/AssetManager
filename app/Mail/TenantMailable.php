<?php

namespace App\Mail;

use App\Models\Tenant;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Envelope;

abstract class TenantMailable extends BaseMailable
{
    public function __construct(protected Tenant $tenant)
    {
    }

    protected function tenantEnvelope(string $subject): Envelope
    {
        $fromAddress = $this->tenant->notificationFromEmail();
        $replyToAddress = $this->tenant->notificationReplyToEmail();

        return new Envelope(
            from: new Address($fromAddress, $this->tenant->notificationFromName()),
            replyTo: $replyToAddress ? [new Address($replyToAddress, $this->tenant->notificationReplyToName())] : [],
            subject: $subject,
        );
    }
}
