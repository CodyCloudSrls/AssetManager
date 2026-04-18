<?php

namespace App\Http\Transformers;

use App\Helpers\Helper;
use App\Models\Ticket;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Gate;

class TicketsTransformer
{
    public function transformTickets(Collection $tickets, $total)
    {
        $array = [];

        foreach ($tickets as $ticket) {
            $array[] = $this->transformTicket($ticket);
        }

        return (new DatatablesTransformer)->transformDatatables($array, $total);
    }

    public function transformTicket(Ticket $ticket): array
    {
        return [
            'id' => (int) $ticket->id,
            'ticket_number' => e($ticket->ticket_number),
            'subject' => e($ticket->subject),
            'status' => e($ticket->status?->name),
            'priority' => e($ticket->priority?->name),
            'type' => e($ticket->type?->name),
            'source' => e(Ticket::sourceOptions()[$ticket->source] ?? $ticket->source),
            'company' => $ticket->company ? [
                'id' => (int) $ticket->company->id,
                'name' => e($ticket->company->name),
                'tag_color' => $ticket->company->tag_color ? e($ticket->company->tag_color) : null,
            ] : null,
            'requester' => $ticket->requester ? [
                'id' => (int) $ticket->requester->id,
                'name' => e($ticket->requester->display_name),
            ] : [
                'id' => 0,
                'name' => e($ticket->requester_display_name),
            ],
            'assignee' => $ticket->assignee ? [
                'id' => (int) $ticket->assignee->id,
                'name' => e($ticket->assignee->display_name),
            ] : null,
            'asset' => $ticket->asset ? [
                'id' => (int) $ticket->asset->id,
                'name' => e($ticket->asset->display_name),
                'type' => 'asset',
            ] : null,
            'document' => $ticket->document ? [
                'id' => (int) $ticket->document->id,
                'name' => e($ticket->document->display_name),
                'type' => 'document',
            ] : null,
            'created_at' => Helper::getFormattedDateObject($ticket->created_at, 'datetime'),
            'updated_at' => Helper::getFormattedDateObject($ticket->updated_at, 'datetime'),
            'resolved_at' => Helper::getFormattedDateObject($ticket->resolved_at, 'datetime'),
            'closed_at' => Helper::getFormattedDateObject($ticket->closed_at, 'datetime'),
            'available_actions' => [
                'view' => Gate::allows('view', $ticket),
                'operate' => Gate::allows('operate', $ticket),
                'update' => ($ticket->deleted_at == '' && Gate::allows('update', $ticket)),
                'delete' => ($ticket->deleted_at == '' && Gate::allows('delete', $ticket)),
                'restore' => ($ticket->deleted_at != '' && Gate::allows('create', Ticket::class)),
            ],
        ];
    }
}
