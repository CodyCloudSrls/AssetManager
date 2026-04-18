<?php

namespace App\Http\Requests;

use App\Models\Tenant;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Support\Str;

class StoreTenantHelpdeskSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('helpdesk_slug')) {
            $this->merge([
                'helpdesk_slug' => filled($this->input('helpdesk_slug'))
                    ? Str::slug((string) $this->input('helpdesk_slug'))
                    : null,
            ]);
        }
    }

    public function rules(): array
    {
        /** @var Tenant|null $tenant */
        $tenant = $this->route('tenant');
        $rootCompanyId = $tenant?->rootCompany()?->id;

        return [
            'helpdesk_enabled' => 'nullable|boolean',
            'helpdesk_allow_attachments' => 'nullable|boolean',
            'helpdesk_slug' => [
                'nullable',
                'string',
                'max:80',
                'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
                Rule::unique('companies', 'helpdesk_slug')
                    ->ignore($rootCompanyId)
                    ->whereNull('deleted_at'),
            ],
            'helpdesk_intro' => 'nullable|string|max:5000',
            'helpdesk_privacy_note' => 'nullable|string|max:5000',
            'helpdesk_contact_email' => 'nullable|email|max:150',
            'helpdesk_contact_phone' => 'nullable|string|max:35',
            'public_ticket_type_ids' => 'nullable|array',
            'public_ticket_type_ids.*' => 'integer',
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            /** @var Tenant|null $tenant */
            $tenant = $this->route('tenant');

            if (! $tenant instanceof Tenant) {
                return;
            }

            $availableIds = $tenant->publicHelpdeskAvailableTicketTypes()
                ->pluck('id')
                ->map(fn ($id) => (int) $id)
                ->all();

            $selectedIds = collect($this->input('public_ticket_type_ids', []))
                ->filter(fn ($id) => $id !== null && $id !== '')
                ->map(fn ($id) => (int) $id)
                ->unique()
                ->values()
                ->all();

            foreach ($selectedIds as $selectedId) {
                if (! in_array($selectedId, $availableIds, true)) {
                    $validator->errors()->add('public_ticket_type_ids', trans('validation.exists', ['attribute' => 'ticket type']));
                    break;
                }
            }

            if ($this->boolean('helpdesk_enabled') && count($selectedIds) === 0) {
                $validator->errors()->add('public_ticket_type_ids', trans('admin/tenants/message.helpdesk.no_public_types'));
            }
        });
    }
}
