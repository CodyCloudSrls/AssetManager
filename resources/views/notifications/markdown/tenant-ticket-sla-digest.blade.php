@component('mail::message')
# {{ trans('mail.tenant_notification_greeting', ['tenant' => $tenant->display_name]) }}

{{ trans('mail.tenant_ticket_sla_intro') }}

@if ($responseBreached->count() > 0)
## {{ trans('mail.tenant_ticket_sla_response_breached') }}
@foreach ($responseBreached as $ticket)
 - [{{ $ticket->ticket_number }}]({{ route('tickets.show', $ticket) }}) - {{ $ticket->subject }} - {{ \App\Helpers\Helper::getFormattedDateObject($ticket->first_response_due_at, 'datetime') }}
@endforeach
@endif

@if ($responseAtRisk->count() > 0)
## {{ trans('mail.tenant_ticket_sla_response_risk') }}
@foreach ($responseAtRisk as $ticket)
 - [{{ $ticket->ticket_number }}]({{ route('tickets.show', $ticket) }}) - {{ $ticket->subject }} - {{ \App\Helpers\Helper::getFormattedDateObject($ticket->first_response_due_at, 'datetime') }}
@endforeach
@endif

@if ($resolutionBreached->count() > 0)
## {{ trans('mail.tenant_ticket_sla_resolution_breached') }}
@foreach ($resolutionBreached as $ticket)
 - [{{ $ticket->ticket_number }}]({{ route('tickets.show', $ticket) }}) - {{ $ticket->subject }} - {{ \App\Helpers\Helper::getFormattedDateObject($ticket->resolution_due_at, 'datetime') }}
@endforeach
@endif

@if ($resolutionAtRisk->count() > 0)
## {{ trans('mail.tenant_ticket_sla_resolution_risk') }}
@foreach ($resolutionAtRisk as $ticket)
 - [{{ $ticket->ticket_number }}]({{ route('tickets.show', $ticket) }}) - {{ $ticket->subject }} - {{ \App\Helpers\Helper::getFormattedDateObject($ticket->resolution_due_at, 'datetime') }}
@endforeach
@endif

@component('mail::button', ['url' => route('tickets.index', ['queue' => 'sla_at_risk'])])
{{ trans('mail.view_ticket_queue') }}
@endcomponent

{{ trans('mail.best_regards') }}<br>
{{ config('app.name') }}
@endcomponent
