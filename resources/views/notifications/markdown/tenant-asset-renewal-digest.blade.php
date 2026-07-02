@component('mail::message')
# {{ trans('mail.tenant_notification_greeting', ['tenant' => $tenant->display_name]) }}

{{ trans('mail.tenant_asset_renewal_intro', ['days' => $warningDays]) }}

@foreach ($assets as $asset)
 - [{{ $asset->name ?: $asset->asset_tag }}]({{ route('hardware.show', $asset) }}) — {{ $asset->company?->name ?? '-' }} — {{ \App\Helpers\Helper::getFormattedDateObject($asset->renewal_date, 'date', false) }}@if ($asset->auto_renewal) ({{ trans('admin/hardware/form.auto_renewal') }})@endif
@endforeach

@component('mail::button', ['url' => route('hardware.index', ['expiring_renewal' => 1])])
{{ trans('mail.tenant_asset_renewal_view') }}
@endcomponent

{{ trans('mail.best_regards') }}<br>
{{ config('app.name') }}
@endcomponent
