@component('mail::message')
# {{ trans('mail.tenant_notification_greeting', ['tenant' => $tenant->display_name]) }}

{{ trans('mail.tenant_warranty_intro', ['days' => $warningDays]) }}

@foreach ($assets as $asset)
 - [{{ $asset->name ?: $asset->asset_tag }}]({{ route('hardware.show', $asset) }}) — {{ $asset->company?->name ?? '-' }}@if ($asset->warranty_expires) — {{ trans('admin/hardware/form.warranty_expires') }}: {{ \App\Helpers\Helper::getFormattedDateObject($asset->warranty_expires, 'date', false) }}@endif @if ($asset->asset_eol_date) — {{ trans('admin/hardware/form.eol_date') }}: {{ \App\Helpers\Helper::getFormattedDateObject($asset->asset_eol_date, 'date', false) }}@endif
@endforeach

@component('mail::button', ['url' => route('hardware.index')])
{{ trans('mail.your_assets') }}
@endcomponent

{{ trans('mail.best_regards') }}<br>
{{ config('app.name') }}
@endcomponent
