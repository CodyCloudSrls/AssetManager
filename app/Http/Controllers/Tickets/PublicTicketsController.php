<?php

namespace App\Http\Controllers\Tickets;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePublicTicketReplyRequest;
use App\Http\Requests\StorePublicTicketRequest;
use App\Http\Requests\UploadFileRequest;
use App\Helpers\StorageHelper;
use App\Models\Actionlog;
use App\Models\Tenant;
use App\Models\Ticket;
use App\Models\TicketPriority;
use App\Models\TicketStatus;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PublicTicketsController extends Controller
{
    public function create(Tenant $tenantPortal): View
    {
        $tenant = $tenantPortal;
        abort_unless($tenant->isHelpdeskEnabled() && $tenant->rootCompany(), 404);

        return view('tickets.public.create', [
            'tenant' => $tenant,
            'rootCompany' => $tenant->rootCompany(),
            'ticketTypes' => $tenant->publicHelpdeskSelectedTicketTypes(),
        ]);
    }

    public function store(StorePublicTicketRequest $request, Tenant $tenantPortal): RedirectResponse
    {
        $tenant = $tenantPortal;
        $rootCompany = $tenant->rootCompany();
        abort_if(is_null($rootCompany) || ! $tenant->isHelpdeskEnabled(), 404);

        $ticket = new Ticket;
        $ticket->fill($request->only([
            'subject',
            'description',
            'guest_name',
            'guest_email',
            'guest_phone',
            'ticket_type_id',
        ]));
        $ticket->company_id = $rootCompany->id;
        $ticket->source = Ticket::SOURCE_PUBLIC;
        $ticket->ticket_status_id = TicketStatus::active()->ordered()->value('id');
        $ticket->ticket_priority_id = TicketPriority::active()->ordered()->where('slug', 'medium')->value('id')
            ?: TicketPriority::active()->ordered()->value('id');

        if (! $ticket->save()) {
            return redirect()->back()->withInput()->withErrors($ticket->getErrors());
        }

        if ($request->hasFile('file')) {
            $this->storeFiles($request, $ticket, 'public submission');
        }

        $ticket->addComment($ticket->description, null, true, [
            'visibility' => ['old' => null, 'new' => 'public'],
            'source' => ['old' => null, 'new' => Ticket::SOURCE_PUBLIC],
        ]);

        return redirect()->route('tickets.portal.show', [
            'tenantPortal' => $tenant->publicHelpdeskRouteKey(),
            'ticket' => $ticket->id,
            'token' => $ticket->portal_token,
        ])->with('success', trans('admin/tickets/message.public.create.success'));
    }

    public function show(Tenant $tenantPortal, Ticket $ticket, string $token): View
    {
        $tenant = $tenantPortal;
        abort_unless($this->canAccessTicket($tenant, $ticket, $token), 404);

        $ticket->load(['status', 'priority', 'type', 'worklogs']);

        return view('tickets.public.show', [
            'tenant' => $tenant,
            'rootCompany' => $tenant->rootCompany(),
            'ticket' => $ticket,
            'publicReplies' => $ticket->publicReplies()->with('adminuser')->get(),
            'publicUploads' => $ticket->publicUploads()->get(),
        ]);
    }

    public function reply(StorePublicTicketReplyRequest $request, Tenant $tenantPortal, Ticket $ticket, string $token): RedirectResponse
    {
        $tenant = $tenantPortal;
        abort_unless($this->canAccessTicket($tenant, $ticket, $token), 404);

        $ticket->addComment($request->input('description'), null, true, [
            'visibility' => ['old' => null, 'new' => 'public'],
            'source' => ['old' => null, 'new' => Ticket::SOURCE_PUBLIC],
            'guest_email' => ['old' => null, 'new' => $ticket->guest_email],
        ]);

        if ($request->hasFile('file')) {
            $this->storeFiles($request, $ticket, 'public reply');
        }

        return redirect()->route('tickets.portal.show', [
            'tenantPortal' => $tenant->publicHelpdeskRouteKey(),
            'ticket' => $ticket->id,
            'token' => $token,
        ])->with('success', trans('admin/tickets/message.public.reply.success'));
    }

    public function downloadFile(Tenant $tenantPortal, Ticket $ticket, string $token, int $fileId): StreamedResponse|BinaryFileResponse
    {
        $tenant = $tenantPortal;
        abort_unless($this->canAccessTicket($tenant, $ticket, $token), 404);

        $fileLog = Actionlog::query()
            ->where('id', $fileId)
            ->where('action_type', 'uploaded')
            ->where('item_type', Ticket::class)
            ->where('item_id', $ticket->id)
            ->whereNull('created_by')
            ->firstOrFail();

        return StorageHelper::downloader($fileLog->uploads_file_path());
    }

    private function canAccessTicket(Tenant $tenant, Ticket $ticket, string $token): bool
    {
        return $tenant->isHelpdeskEnabled()
            && (int) $ticket->company_id === (int) optional($tenant->rootCompany())->id
            && hash_equals((string) $ticket->portal_token, $token);
    }

    private function storeFiles(UploadFileRequest $request, Ticket $ticket, string $note): void
    {
        if (! Storage::exists(self::$map_storage_path['tickets'])) {
            Storage::makeDirectory(self::$map_storage_path['tickets'], 775);
        }

        foreach ($request->file('file', []) as $file) {
            $fileName = $request->handleFile(self::$map_storage_path['tickets'], self::$map_file_prefix['tickets'].'-'.$ticket->id, $file);
            $ticket->logUpload($fileName, $note);
        }
    }
}
