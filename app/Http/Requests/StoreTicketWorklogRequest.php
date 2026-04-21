<?php

namespace App\Http\Requests;

use App\Models\Company;
use App\Models\Ticket;
use App\Models\TicketPriority;
use App\Models\TicketStatus;
use App\Models\TicketType;
use App\Models\TicketWorklog;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;

class StoreTicketWorklogRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function prepareForValidation(): void
    {
        $this->merge([
            'assignee_id' => Company::getIdFromInput($this->input('assignee_id')),
            'ticket_type_id' => Company::getIdFromInput($this->input('ticket_type_id')),
            'ticket_status_id' => Company::getIdFromInput($this->input('ticket_status_id')),
            'ticket_priority_id' => Company::getIdFromInput($this->input('ticket_priority_id')),
            'message' => $this->input('message', $this->input('notes')),
            'is_public_message' => $this->boolean('is_public_message'),
        ]);
    }

    public function rules(): array
    {
        return [
            'minutes' => 'nullable|integer|min:1|max:10080',
            'category' => 'nullable|string|in:'.implode(',', array_keys(TicketWorklog::categoryOptions())),
            'started_at' => 'nullable|date_format:Y-m-d\\TH:i',
            'ended_at' => 'nullable|date_format:Y-m-d\\TH:i|after_or_equal:started_at',
            'is_billable' => 'nullable|boolean',
            'message' => 'nullable|string|max:65535',
            'assignee_id' => 'nullable|integer',
            'ticket_type_id' => 'nullable|integer',
            'ticket_status_id' => 'nullable|integer',
            'ticket_priority_id' => 'nullable|integer',
            'first_response_due_at' => 'nullable|date_format:Y-m-d\\TH:i',
            'resolution_due_at' => 'nullable|date_format:Y-m-d\\TH:i|after_or_equal:first_response_due_at',
            'is_public_message' => 'nullable|boolean',
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            /** @var Ticket|null $ticket */
            $ticket = $this->route('ticket');

            if (! $ticket instanceof Ticket) {
                return;
            }

            if ($this->filled('ticket_status_id') && ! TicketStatus::find($this->integer('ticket_status_id'))) {
                $validator->errors()->add('ticket_status_id', trans('validation.exists', ['attribute' => 'ticket_status_id']));
            }

            if ($this->filled('ticket_priority_id') && ! TicketPriority::find($this->integer('ticket_priority_id'))) {
                $validator->errors()->add('ticket_priority_id', trans('validation.exists', ['attribute' => 'ticket_priority_id']));
            }

            if ($this->filled('ticket_type_id') && ! TicketType::find($this->integer('ticket_type_id'))) {
                $validator->errors()->add('ticket_type_id', trans('validation.exists', ['attribute' => 'ticket_type_id']));
            }

            if ($this->filled('assignee_id')) {
                $assignee = User::withoutGlobalScopes()->whereNull('deleted_at')->find($this->integer('assignee_id'));

                if (! $assignee) {
                    $validator->errors()->add('assignee_id', trans('validation.exists', ['attribute' => 'assignee_id']));
                } else {
                    $ticketCompany = Company::withoutGlobalScopes()->find($ticket->company_id);
                    $assigneeCompany = Company::withoutGlobalScopes()->find($assignee->company_id);

                    if ($ticketCompany && $ticketCompany->tenant_id) {
                        if (! $assigneeCompany || (int) $assigneeCompany->tenant_id !== (int) $ticketCompany->tenant_id) {
                            $validator->errors()->add('assignee_id', trans('validation.exists', ['attribute' => 'assignee_id']));
                        }
                    } elseif ((int) ($assignee->company_id ?? 0) !== (int) ($ticket->company_id ?? 0)) {
                        $validator->errors()->add('assignee_id', trans('validation.exists', ['attribute' => 'assignee_id']));
                    }
                }
            }

            if ($this->filled('minutes') && ! $this->filled('category')) {
                $validator->errors()->add('category', trans('validation.required', ['attribute' => 'category']));
            }

            $currentFirstResponse = optional($ticket->first_response_due_at)->format('Y-m-d\\TH:i');
            $currentResolution = optional($ticket->resolution_due_at)->format('Y-m-d\\TH:i');

            $hasOperationalChange =
                ((int) $this->input('ticket_status_id', $ticket->ticket_status_id) !== (int) $ticket->ticket_status_id)
                || ((int) ($this->input('ticket_priority_id') ?: 0) !== (int) ($ticket->ticket_priority_id ?: 0))
                || ((int) ($this->input('ticket_type_id') ?: 0) !== (int) ($ticket->ticket_type_id ?: 0))
                || ((int) ($this->input('assignee_id') ?: 0) !== (int) ($ticket->assignee_id ?: 0))
                || ((string) $this->input('first_response_due_at', $currentFirstResponse) !== (string) $currentFirstResponse)
                || ((string) $this->input('resolution_due_at', $currentResolution) !== (string) $currentResolution);

            if (! $hasOperationalChange && ! $this->filled('minutes') && ! $this->filled('message')) {
                $validator->errors()->add('minutes', trans('admin/tickets/message.workflow.empty'));
            }
        });
    }
}
