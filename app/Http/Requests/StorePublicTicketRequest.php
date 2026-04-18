<?php

namespace App\Http\Requests;

use App\Models\Tenant;
use App\Models\Ticket;
use App\Models\TicketType;

class StorePublicTicketRequest extends UploadFileRequest
{
    public function rules()
    {
        return array_merge(parent::rules(), [
            'subject' => 'required|string|max:255',
            'description' => 'required|string|max:65535',
            'guest_name' => 'required|string|max:255',
            'guest_email' => 'required|email|max:255',
            'guest_phone' => 'nullable|string|max:80',
            'ticket_type_id' => 'nullable|integer',
        ]);
    }

    public function prepareForValidation(): void
    {
        $files = $this->file('file');

        if (empty($files) || (is_array($files) && count(array_filter($files)) === 0)) {
            $this->offsetUnset('file');
        }
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            /** @var Tenant|null $tenant */
            $tenant = $this->route('tenantPortal');

            if (! $tenant instanceof Tenant || ! $tenant->isHelpdeskEnabled()) {
                $validator->errors()->add('subject', trans('admin/tenants/message.helpdesk.disabled'));

                return;
            }

            if (! $tenant->publicHelpdeskAllowsAttachments() && $this->hasFile('file')) {
                $validator->errors()->add('file', trans('admin/tenants/message.helpdesk.attachments_disabled'));
            }

            $allowedTypeIds = $tenant->publicHelpdeskSelectedTicketTypes()
                ->pluck('id')
                ->map(fn ($id) => (int) $id)
                ->all();

            if ($this->filled('ticket_type_id') && ! in_array((int) $this->input('ticket_type_id'), $allowedTypeIds, true)) {
                $validator->errors()->add('ticket_type_id', trans('validation.exists', ['attribute' => 'ticket type']));
            }
        });
    }
}
