<?php

namespace App\Http\Requests;

use App\Models\Tenant;

class StorePublicTicketReplyRequest extends UploadFileRequest
{
    public function rules()
    {
        return array_merge(parent::rules(), [
            'description' => 'required|string|max:65535',
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

            if ($tenant instanceof Tenant && ! $tenant->publicHelpdeskAllowsAttachments() && $this->hasFile('file')) {
                $validator->errors()->add('file', trans('admin/tenants/message.helpdesk.attachments_disabled'));
            }
        });
    }
}
