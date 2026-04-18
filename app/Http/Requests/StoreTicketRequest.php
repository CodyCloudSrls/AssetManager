<?php

namespace App\Http\Requests;

use App\Models\Asset;
use App\Models\Company;
use App\Models\Document;
use App\Models\Location;
use App\Models\Ticket;
use App\Models\TicketPriority;
use App\Models\TicketStatus;
use App\Models\TicketType;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;

class StoreTicketRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function prepareForValidation(): void
    {
        $this->merge([
            'company_id' => Company::getIdForCurrentUser($this->input('company_id')),
            'source' => $this->input('source') ?: Ticket::SOURCE_INTERNAL,
        ]);
    }

    public function rules(): array
    {
        return [
            'company_id' => 'nullable|integer|exists:companies,id',
            'requester_id' => 'nullable|integer',
            'assignee_id' => 'nullable|integer',
            'ticket_type_id' => 'nullable|integer',
            'ticket_status_id' => 'nullable|integer',
            'ticket_priority_id' => 'nullable|integer',
            'asset_id' => 'nullable|integer',
            'document_id' => 'nullable|integer',
            'location_id' => 'nullable|integer',
            'related_user_id' => 'nullable|integer',
            'source' => 'required|string|in:internal,public,email',
            'subject' => 'required|string|max:255',
            'description' => 'required|string|max:65535',
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            foreach ([
                'requester_id' => User::class,
                'assignee_id' => User::class,
                'related_user_id' => User::class,
                'ticket_type_id' => TicketType::class,
                'ticket_status_id' => TicketStatus::class,
                'ticket_priority_id' => TicketPriority::class,
                'asset_id' => Asset::class,
                'document_id' => Document::class,
                'location_id' => Location::class,
            ] as $field => $modelClass) {
                if (! $this->filled($field)) {
                    continue;
                }

                if (! $modelClass::find($this->input($field))) {
                    $validator->errors()->add($field, trans('validation.exists', ['attribute' => $field]));
                }
            }
        });
    }
}
