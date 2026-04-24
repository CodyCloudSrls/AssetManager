@component('mail::message')
# {{ trans('mail.tenant_notification_greeting', ['tenant' => $tenant->display_name]) }}

@if ($eventKey === \App\Models\Tenant::MAIL_EVENT_TICKET_CREATED)
{{ trans('mail.tenant_ticket_created_intro', ['ticket' => $ticket->ticket_number]) }}
@elseif ($eventKey === \App\Models\Tenant::MAIL_EVENT_TICKET_PUBLIC_REPLY)
{{ trans('mail.tenant_ticket_public_reply_intro', ['ticket' => $ticket->ticket_number]) }}
@elseif ($eventKey === \App\Models\Tenant::MAIL_EVENT_TICKET_ASSIGNED)
{{ trans('mail.tenant_ticket_assigned_intro', ['ticket' => $ticket->ticket_number]) }}
@endif

**{{ trans('general.tenant') }}:** {{ $tenant->display_name }}

**{{ trans('general.company') }}:** {{ $ticket->company?->name ?? '-' }}

**{{ trans('admin/tickets/form.ticket_number') }}:** {{ $ticket->ticket_number }}

**{{ trans('general.subject') }}:** {{ $ticket->subject }}

**{{ trans('general.status') }}:** {{ $ticket->status?->name ?? '-' }}

**{{ trans('admin/tickets/form.priority') }}:** {{ $ticket->priority?->name ?? '-' }}

**{{ trans('admin/tickets/form.type') }}:** {{ $ticket->type?->name ?? '-' }}

**{{ trans('admin/tickets/form.source') }}:** {{ \App\Models\Ticket::sourceOptions()[$ticket->source] ?? $ticket->source }}

**{{ trans('admin/tickets/form.requester') }}:** {{ $ticket->requester_display_name }}

**{{ trans('admin/tickets/form.assignee') }}:** {{ $ticket->assignee?->display_name ?? '-' }}

@if ($actorName)
**{{ trans('general.created_by') }}:** {{ $actorName }}
@endif

@if ($ticket->asset)
**{{ trans('mail.asset') }}:** {{ $ticket->asset->display_name }}
@endif

@if ($ticket->document)
**{{ trans('general.document') }}:** {{ $ticket->document->name }}
@endif

@if ($ticket->relatedUser)
**{{ trans('general.user') }}:** {{ $ticket->relatedUser->display_name }}
@endif

@if ($ticket->location)
**{{ trans('general.location') }}:** {{ $ticket->location->name }}
@endif

@if ($comment?->note)
**{{ trans('mail.message') }}:**

{!! nl2br(e($comment->note)) !!}
@endif

@if ($ticket->description)
**{{ trans('general.description') }}:**

{!! nl2br(e($ticket->description)) !!}
@endif

@component('mail::button', ['url' => route('tickets.show', $ticket)])
{{ trans('mail.view_ticket') }}
@endcomponent

{{ trans('mail.best_regards') }}<br>
{{ config('app.name') }}
@endcomponent
