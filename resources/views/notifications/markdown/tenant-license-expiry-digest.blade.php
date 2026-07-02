@component('mail::message')
# {{ trans('mail.tenant_notification_greeting', ['tenant' => $tenant->display_name]) }}

{{ trans('mail.tenant_license_expiry_intro', ['days' => $warningDays]) }}

@foreach ($licenses as $license)
 - [{{ $license->name }}]({{ route('licenses.show', $license) }}) — {{ $license->company?->name ?? '-' }}@if ($license->expiration_date) — {{ trans('mail.expires') }}: {{ \App\Helpers\Helper::getFormattedDateObject($license->expiration_date, 'date', false) }}@endif @if ($license->termination_date) — {{ trans('mail.terminates') }}: {{ \App\Helpers\Helper::getFormattedDateObject($license->termination_date, 'date', false) }}@endif
@endforeach

@component('mail::button', ['url' => route('licenses.index')])
{{ trans('general.licenses') }}
@endcomponent

{{ trans('mail.best_regards') }}<br>
{{ config('app.name') }}
@endcomponent
