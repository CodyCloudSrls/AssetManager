<?php

namespace App\Http\Controllers\Tickets;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\LoadsTicketOperators;
use App\Http\Requests\StoreTicketCommentRequest;
use App\Http\Requests\StoreTicketRequest;
use App\Http\Requests\StoreTicketWorklogRequest;
use App\Models\Asset;
use App\Models\Actionlog;
use App\Models\Company;
use App\Models\Document;
use App\Models\Ticket;
use App\Models\TicketPriority;
use App\Models\TicketStatus;
use App\Models\TicketType;
use App\Models\TicketWorklog;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class TicketsController extends Controller
{
    use LoadsTicketOperators;

    public function index(): View
    {
        $this->authorize('index', Ticket::class);

        $baseQuery = Ticket::query();

        return view('tickets.index', [
            'queueCounts' => [
                'all' => (clone $baseQuery)->count(),
                'open' => (clone $baseQuery)->open()->count(),
                'mine' => (clone $baseQuery)->assignedToMe()->count(),
                'unassigned' => (clone $baseQuery)->unassigned()->count(),
                'waiting_customer' => (clone $baseQuery)->waitingCustomer()->count(),
                'waiting_vendor' => (clone $baseQuery)->waitingVendor()->count(),
                'public' => (clone $baseQuery)->publicPortal()->count(),
                'sla_at_risk' => (clone $baseQuery)->slaAtRisk()->count(),
                'closed' => (clone $baseQuery)->closed()->count(),
            ],
            'currentQueue' => request()->input('queue', 'all'),
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', Ticket::class);

        $ticket = new Ticket;
        $ticket->company_id = Company::preferredCompanySelectionId();
        $ticket->requester_id = request()->integer('requester_id') ?: auth()->id();
        $ticket->assignee_id = request()->integer('assignee_id') ?: null;
        $ticket->asset_id = request()->integer('asset_id') ?: null;
        $ticket->document_id = request()->integer('document_id') ?: null;
        $ticket->related_user_id = request()->integer('related_user_id') ?: null;

        return view('tickets.edit', $this->formData($ticket));
    }

    public function store(StoreTicketRequest $request): RedirectResponse
    {
        $this->authorize('create', Ticket::class);

        $ticket = new Ticket;
        $this->fillTicket($ticket, $request);
        $ticket->created_by = auth()->id();

        if (! $ticket->save()) {
            return redirect()->back()->withInput()->withErrors($ticket->getErrors());
        }

        return redirect()->route('tickets.show', $ticket)
            ->with('success', trans('admin/tickets/message.create.success'));
    }

    public function show(Ticket $ticket): View
    {
        $this->authorize('view', $ticket);

        $ticket->load([
            'company',
            'requester',
            'assignee',
            'relatedUser',
            'asset',
            'document',
            'location',
            'type',
            'status',
            'priority',
            'worklogs.user',
            'adminuser',
        ]);

        return view('tickets.view', [
            'ticket' => $ticket,
            'ticketStatuses' => TicketStatus::active()->ordered()->get(),
            'ticketPriorities' => TicketPriority::active()->ordered()->get(),
            'ticketTypes' => TicketType::active()->ordered()->get(),
            'operationalUsers' => $this->ticketAssignableUsers($ticket),
            'worklogCategories' => TicketWorklog::categoryOptions(),
        ]);
    }

    public function edit(Ticket $ticket): View
    {
        $this->authorize('update', $ticket);

        return view('tickets.edit', $this->formData($ticket));
    }

    public function update(StoreTicketRequest $request, Ticket $ticket): RedirectResponse
    {
        $this->authorize('update', $ticket);

        $this->fillTicket($ticket, $request);

        if (! $ticket->save()) {
            return redirect()->back()->withInput()->withErrors($ticket->getErrors());
        }

        return redirect()->route('tickets.show', $ticket)
            ->with('success', trans('admin/tickets/message.update.success'));
    }

    public function destroy(Ticket $ticket): RedirectResponse
    {
        $this->authorize('delete', $ticket);

        $ticket->delete();

        return redirect()->route('tickets.index')
            ->with('success', trans('admin/tickets/message.delete.success'));
    }

    public function restore(int $id): RedirectResponse
    {
        $this->authorize('create', Ticket::class);

        $ticket = Ticket::withTrashed()->findOrFail($id);
        $ticket->restore();

        $logAction = new Actionlog;
        $logAction->item_type = Ticket::class;
        $logAction->item_id = $ticket->id;
        $logAction->target_type = $ticket->assignee_id ? User::class : null;
        $logAction->target_id = $ticket->assignee_id;
        $logAction->created_at = date('Y-m-d H:i:s');
        $logAction->action_date = date('Y-m-d H:i:s');
        $logAction->created_by = auth()->id();
        $logAction->logaction('restore');

        return redirect()->route('tickets.show', $ticket)
            ->with('success', trans('admin/tickets/message.restore.success'));
    }

    public function storeComment(StoreTicketCommentRequest $request, Ticket $ticket): RedirectResponse
    {
        $this->authorize('operate', $ticket);

        $isPublic = $request->boolean('is_public');

        $ticket->addComment($request->input('message'), auth()->user(), $isPublic, [
            'visibility' => ['old' => null, 'new' => $isPublic ? 'public' : 'internal'],
            'source' => ['old' => null, 'new' => $isPublic ? Ticket::SOURCE_PUBLIC : Ticket::SOURCE_INTERNAL],
        ]);

        return redirect()->route('tickets.show', $ticket)
            ->withFragment('comments')
            ->with('success', trans('admin/tickets/message.comment.success'));
    }

    public function storeWorklog(StoreTicketWorklogRequest $request, Ticket $ticket): RedirectResponse
    {
        $this->authorize('operate', $ticket);

        $ticket->assignee_id = $request->filled('assignee_id') ? $request->integer('assignee_id') : null;
        $ticket->ticket_status_id = $request->filled('ticket_status_id') ? $request->integer('ticket_status_id') : $ticket->ticket_status_id;
        $ticket->ticket_priority_id = $request->filled('ticket_priority_id') ? $request->integer('ticket_priority_id') : null;
        $ticket->ticket_type_id = $request->filled('ticket_type_id') ? $request->integer('ticket_type_id') : null;
        $ticket->first_response_due_at = $request->has('first_response_due_at')
            ? ($request->filled('first_response_due_at') ? $request->date('first_response_due_at') : null)
            : $ticket->first_response_due_at;
        $ticket->resolution_due_at = $request->has('resolution_due_at')
            ? ($request->filled('resolution_due_at') ? $request->date('resolution_due_at') : null)
            : $ticket->resolution_due_at;

        if ($ticket->isDirty() && ! $ticket->save()) {
            return redirect()->back()->withInput()->withErrors($ticket->getErrors());
        }

        if ($request->filled('minutes')) {
            $worklog = new TicketWorklog;
            $worklog->fill($request->all());
            $worklog->ticket_id = $ticket->id;
            $worklog->company_id = $ticket->company_id;
            $worklog->user_id = auth()->id();
            $worklog->created_by = auth()->id();
            $worklog->is_billable = $request->boolean('is_billable');
            $worklog->started_at = $request->filled('started_at') ? $request->date('started_at') : null;
            $worklog->ended_at = $request->filled('ended_at') ? $request->date('ended_at') : null;

            if (! $worklog->save()) {
                return redirect()->back()->withInput()->withErrors($worklog->getErrors());
            }

            $ticket->addWorklog($worklog);
        } elseif ($request->filled('message')) {
            $isPublicMessage = $request->boolean('is_public_message');

            $ticket->addComment($request->input('message'), auth()->user(), $isPublicMessage, [
                'visibility' => ['old' => null, 'new' => $isPublicMessage ? 'public' : 'internal'],
                'source' => ['old' => null, 'new' => $isPublicMessage ? Ticket::SOURCE_PUBLIC : Ticket::SOURCE_INTERNAL],
            ]);
        }

        return redirect()->route('tickets.show', $ticket)
            ->withFragment('worklogs')
            ->with('success', trans('admin/tickets/message.workflow.success'));
    }

    private function fillTicket(Ticket $ticket, StoreTicketRequest $request): void
    {
        $ticket->fill($request->all());
        $ticket->source = $request->input('source', Ticket::SOURCE_INTERNAL);

        if (! $ticket->ticket_status_id) {
            $ticket->ticket_status_id = TicketStatus::active()->ordered()->value('id');
        }

        if (! $ticket->ticket_priority_id) {
            $ticket->ticket_priority_id = TicketPriority::active()->ordered()->value('id');
        }
    }

    private function formData(Ticket $ticket): array
    {
        $companyIds = Company::activeCompanyContextIds();
        $companies = count($companyIds) > 0
            ? Company::whereIn('id', $companyIds)->orderBy('name')->get()
            : Company::withoutGlobalScopes()->whereNull('deleted_at')->orderBy('name')->get();

        return [
            'ticket' => $ticket,
            'companies' => $companies,
            'ticketStatuses' => TicketStatus::active()->ordered()->get(),
            'ticketPriorities' => TicketPriority::active()->ordered()->get(),
            'ticketTypes' => TicketType::active()->ordered()->get(),
            'documents' => Document::orderBy('name')->limit(200)->get(),
            'linkedAsset' => $ticket->asset_id ? Asset::find($ticket->asset_id) : null,
            'linkedRequester' => $ticket->requester_id ? User::find($ticket->requester_id) : null,
            'linkedAssignee' => $ticket->assignee_id ? User::find($ticket->assignee_id) : null,
            'linkedRelatedUser' => $ticket->related_user_id ? User::find($ticket->related_user_id) : null,
            'sources' => Ticket::sourceOptions(),
        ];
    }
}
