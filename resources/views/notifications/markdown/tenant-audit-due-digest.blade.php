@component('mail::message')
# {{ trans('mail.tenant_notification_greeting', ['tenant' => $tenant->display_name]) }}

{{ trans('mail.tenant_audit_due_intro', ['days' => $warningDays]) }}

@foreach ($assets as $asset)
 - [{{ $asset->name ?: $asset->asset_tag }}]({{ route('hardware.show', $asset) }}) — {{ $asset->company?->name ?? '-' }}@if ($asset->next_audit_date) — {{ trans('general.next_audit_date') }}: {{ \App\Helpers\Helper::getFormattedDateObject($asset->next_audit_date, 'date', false) }}@endif @if ($asset->assignedTo) — {{ $asset->assignedTo->display_name }}@endif
@endforeach

@if ($total > $assets->count())
_{{ trans('mail.tenant_audit_due_more', ['count' => $total - $assets->count()]) }}_
@endif

@component('mail::button', ['url' => route('hardware.index')])
{{ trans('mail.your_assets') }}
@endcomponent

{{ trans('mail.best_regards') }}<br>
{{ config('app.name') }}
@endcomponent
