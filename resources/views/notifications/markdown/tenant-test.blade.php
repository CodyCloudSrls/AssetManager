@component('mail::message')
# {{ trans('mail.tenant_notification_greeting', ['tenant' => $tenant->display_name]) }}

{{ trans('mail.tenant_test_body') }}

{{ trans('mail.best_regards') }}<br>
{{ config('app.name') }}
@endcomponent
