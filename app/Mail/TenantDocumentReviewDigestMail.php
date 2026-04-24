<?php

namespace App\Mail;

use App\Models\Tenant;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;

class TenantDocumentReviewDigestMail extends TenantMailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        Tenant $tenant,
        protected Collection $dueDocuments,
        protected Collection $overdueDocuments,
        protected int $warningDays,
    ) {
        parent::__construct($tenant);
    }

    public function envelope(): Envelope
    {
        $total = $this->dueDocuments->count() + $this->overdueDocuments->count();

        return $this->tenantEnvelope(trans('mail.tenant_document_review_subject', [
            'tenant' => $this->tenant->display_name,
            'count' => $total,
        ]));
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'notifications.markdown.tenant-document-review-digest',
            with: [
                'tenant' => $this->tenant,
                'dueDocuments' => $this->dueDocuments,
                'overdueDocuments' => $this->overdueDocuments,
                'warningDays' => $this->warningDays,
            ]
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
