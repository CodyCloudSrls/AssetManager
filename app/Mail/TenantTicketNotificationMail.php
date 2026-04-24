<?php

namespace App\Mail;

use App\Models\Actionlog;
use App\Models\Tenant;
use App\Models\Ticket;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TenantTicketNotificationMail extends TenantMailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        Tenant $tenant,
        protected Ticket $ticket,
        protected string $eventKey,
        protected ?Actionlog $comment = null,
        protected ?string $actorName = null,
    ) {
        parent::__construct($tenant);
    }

    public function envelope(): Envelope
    {
        return $this->tenantEnvelope(match ($this->eventKey) {
            Tenant::MAIL_EVENT_TICKET_CREATED => trans('mail.tenant_ticket_created_subject', ['ticket' => $this->ticket->ticket_number, 'tenant' => $this->tenant->display_name]),
            Tenant::MAIL_EVENT_TICKET_PUBLIC_REPLY => trans('mail.tenant_ticket_public_reply_subject', ['ticket' => $this->ticket->ticket_number, 'tenant' => $this->tenant->display_name]),
            Tenant::MAIL_EVENT_TICKET_ASSIGNED => trans('mail.tenant_ticket_assigned_subject', ['ticket' => $this->ticket->ticket_number, 'tenant' => $this->tenant->display_name]),
            default => trans('mail.tenant_ticket_created_subject', ['ticket' => $this->ticket->ticket_number, 'tenant' => $this->tenant->display_name]),
        });
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'notifications.markdown.tenant-ticket-event',
            with: [
                'tenant' => $this->tenant,
                'ticket' => $this->ticket,
                'eventKey' => $this->eventKey,
                'comment' => $this->comment,
                'actorName' => $this->actorName,
            ]
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
