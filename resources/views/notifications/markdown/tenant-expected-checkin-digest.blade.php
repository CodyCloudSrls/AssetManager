@component('mail::message')
# {{ trans('mail.tenant_notification_greeting', ['tenant' => $tenant->display_name]) }}

{{ trans('mail.tenant_expected_checkin_intro', ['days' => $warningDays]) }}

@foreach ($assets as $asset)
 - [{{ $asset->name ?: $asset->asset_tag }}]({{ route('hardware.show', $asset) }}) — {{ $asset->company?->name ?? '-' }}@if ($asset->assignedTo) — {{ $asset->assignedTo->display_name }}@endif @if ($asset->expected_checkin) — {{ trans('admin/hardware/form.expected_checkin') }}: {{ \App\Helpers\Helper::getFormattedDateObject($asset->expected_checkin, 'date', false) }}@endif
@endforeach

@component('mail::button', ['url' => route('hardware.index')])
{{ trans('mail.your_assets') }}
@endcomponent

{{ trans('mail.best_regards') }}<br>
{{ config('app.name') }}
@endcomponent
