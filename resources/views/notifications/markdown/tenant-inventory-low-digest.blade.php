@component('mail::message')
# {{ trans('mail.tenant_notification_greeting', ['tenant' => $tenant->display_name]) }}

{{ trans('mail.tenant_inventory_low_intro') }}

| {{ trans('mail.name') }} | {{ trans('mail.type') }} | {{ trans('mail.current_QTY') }} | {{ trans('mail.min_QTY') }} |
|---|---|---|---|
@foreach ($items as $item)
| {{ $item['name'] }} | {{ trans('general.'.$item['type']) }}@if (! empty($item['company'])) — {{ $item['company'] }}@endif | {{ $item['remaining'] }} | {{ $item['min_amt'] }} |
@endforeach

{{ trans('mail.best_regards') }}<br>
{{ config('app.name') }}
@endcomponent
