<?php

namespace App\Http\Requests;

use App\Models\Tenant;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTenantMailSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'tenant_notification_email' => 'nullable|email_array|max:500',
            'tenant_mail_reply_to_email' => 'nullable|email|max:150',
            'tenant_mail_reply_to_name' => 'nullable|string|max:150',
            'tenant_mail_from_name' => 'nullable|string|max:150',
            'helpdesk_contact_email' => 'nullable|email|max:150',
            'tenant_document_review_warning_days' => 'nullable|integer|min:1|max:365',
            'tenant_mail_notification_events' => 'nullable|array',
            'tenant_mail_notification_events.*' => [
                'string',
                Rule::in(array_keys(Tenant::mailNotificationEventOptions())),
            ],
        ];
    }
}
