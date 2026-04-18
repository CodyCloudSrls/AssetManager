<?php

namespace App\Http\Controllers\Api;

use App\Helpers\Helper;
use App\Http\Controllers\Controller;
use App\Http\Requests\FilterRequest;
use App\Http\Requests\StoreTicketRequest;
use App\Http\Transformers\ActionlogsTransformer;
use App\Http\Transformers\TicketsTransformer;
use App\Models\Ticket;
use App\Models\TicketPriority;
use App\Models\TicketStatus;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TicketsController extends Controller
{
    public function index(FilterRequest $request): JsonResponse|array
    {
        $this->authorize('index', Ticket::class);

        $tickets = Ticket::select('tickets.*')
            ->with('company', 'requester', 'assignee', 'type', 'status', 'priority', 'asset', 'document', 'adminuser');

        if ($request->filled('filter') || $request->filled('search')) {
            $tickets->TextSearch($request->input('filter') ?: $request->input('search'));
        }

        if ($request->input('status_type') === 'Deleted') {
            $tickets->onlyTrashed();
        }

        if ($request->filled('company_id')) {
            $tickets->where('tickets.company_id', $request->input('company_id'));
        }

        if ($request->filled('ticket_status_id')) {
            $tickets->where('tickets.ticket_status_id', $request->input('ticket_status_id'));
        }

        if ($request->filled('ticket_priority_id')) {
            $tickets->where('tickets.ticket_priority_id', $request->input('ticket_priority_id'));
        }

        if ($request->filled('ticket_type_id')) {
            $tickets->where('tickets.ticket_type_id', $request->input('ticket_type_id'));
        }

        if ($request->filled('assignee_id')) {
            if ($request->input('assignee_id') === 'me') {
                $tickets->AssignedToMe();
            } else {
                $tickets->where('tickets.assignee_id', $request->input('assignee_id'));
            }
        }

        if ($request->filled('requester_id')) {
            if ($request->input('requester_id') === 'me') {
                $tickets->RequestedByMe();
            } else {
                $tickets->where('tickets.requester_id', $request->input('requester_id'));
            }
        }

        if ($request->boolean('unassigned')) {
            $tickets->Unassigned();
        }

        if ($request->filled('source')) {
            $tickets->where('tickets.source', $request->input('source'));
        }

        if ($request->filled('queue')) {
            if ($request->input('queue') === 'open') {
                $tickets->Open();
            } elseif ($request->input('queue') === 'mine') {
                $tickets->AssignedToMe();
            } elseif ($request->input('queue') === 'closed') {
                $tickets->Closed();
            } elseif ($request->input('queue') === 'waiting_customer') {
                $tickets->WaitingCustomer();
            } elseif ($request->input('queue') === 'waiting_vendor') {
                $tickets->WaitingVendor();
            } elseif ($request->input('queue') === 'public') {
                $tickets->PublicPortal();
            } elseif ($request->input('queue') === 'sla_at_risk') {
                $tickets->SlaAtRisk();
            }
        }

        $allowedColumns = [
            'ticket_number',
            'subject',
            'status',
            'priority',
            'type',
            'requester',
            'assignee',
            'company',
            'source',
            'created_at',
            'updated_at',
        ];

        $limit = app('api_limit_value');
        $offset = Helper::clampPaginationOffset($request->input('offset'), $tickets->count(), $limit);
        $order = $request->input('order') === 'asc' ? 'asc' : 'desc';
        $sort = in_array($request->input('sort'), $allowedColumns) ? $request->input('sort') : 'updated_at';

        switch ($sort) {
            case 'company':
                $tickets = $tickets->OrderByCompany($order);
                break;
            case 'requester':
                $tickets = $tickets->OrderByRequester($order);
                break;
            case 'assignee':
                $tickets = $tickets->OrderByAssignee($order);
                break;
            case 'status':
                $tickets = $tickets->OrderByStatus($order);
                break;
            case 'priority':
                $tickets = $tickets->OrderByPriority($order);
                break;
            case 'type':
                $tickets = $tickets->OrderByType($order);
                break;
            default:
                $tickets = $tickets->orderBy($sort, $order);
        }

        $total = $tickets->count();
        $tickets = $tickets->skip($offset)->take($limit)->get();

        return (new TicketsTransformer)->transformTickets($tickets, $total);
    }

    public function show(Ticket $ticket): JsonResponse|array
    {
        $this->authorize('view', $ticket);

        return response()->json((new TicketsTransformer)->transformTicket($ticket));
    }

    public function store(StoreTicketRequest $request): JsonResponse|array
    {
        $this->authorize('create', Ticket::class);

        $ticket = new Ticket;
        $ticket->fill($request->all());
        $ticket->created_by = auth()->id();
        $ticket->ticket_status_id = $ticket->ticket_status_id ?: TicketStatus::active()->ordered()->value('id');
        $ticket->ticket_priority_id = $ticket->ticket_priority_id ?: TicketPriority::active()->ordered()->value('id');

        if ($ticket->save()) {
            return response()->json(Helper::formatStandardApiResponse('success', (new TicketsTransformer)->transformTicket($ticket), trans('admin/tickets/message.create.success')));
        }

        return response()->json(Helper::formatStandardApiResponse('error', null, $ticket->getErrors()), 422);
    }

    public function update(StoreTicketRequest $request, Ticket $ticket): JsonResponse|array
    {
        $this->authorize('update', $ticket);

        $ticket->fill($request->all());

        if ($ticket->save()) {
            return response()->json(Helper::formatStandardApiResponse('success', (new TicketsTransformer)->transformTicket($ticket), trans('admin/tickets/message.update.success')));
        }

        return response()->json(Helper::formatStandardApiResponse('error', null, $ticket->getErrors()), 422);
    }

    public function destroy(Ticket $ticket): JsonResponse|array
    {
        $this->authorize('delete', $ticket);

        $ticket->delete();

        return response()->json(Helper::formatStandardApiResponse('success', null, trans('admin/tickets/message.delete.success')));
    }

    public function history(Request $request, Ticket $ticket): JsonResponse|array
    {
        $this->authorize('view', $ticket);

        $history = $ticket->getHistory($request);
        $total = $history->count();
        $history = $history->skip(app('api_offset_value'))->take(app('api_limit_value'))->get();

        return response()->json((new ActionlogsTransformer)->transformActionlogs($history, $total), 200, ['Content-Type' => 'application/json;charset=utf8'], JSON_UNESCAPED_UNICODE);
    }
}
