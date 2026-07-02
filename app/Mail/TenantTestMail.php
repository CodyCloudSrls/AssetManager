<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * A simple deliverability test email for a tenant: confirms the tenant's SMTP (or the
 * platform fallback), from-name, reply-to and language render correctly.
 */
class TenantTestMail extends TenantMailable
{
    use Queueable;
    use SerializesModels;

    public function envelope(): Envelope
    {
        return $this->tenantEnvelope(trans('mail.tenant_test_subject', ['tenant' => $this->tenant->display_name]));
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'notifications.markdown.tenant-test',
            with: ['tenant' => $this->tenant],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
