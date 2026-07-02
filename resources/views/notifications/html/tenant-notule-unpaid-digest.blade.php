@include('notifications.html._header')
<p>{{ trans('mail.tenant_notule_unpaid_intro') }}</p>
<ul style="padding-left:18px;margin:0 0 12px;">
@foreach ($notule as $notula)
    <li style="margin:4px 0;">{{ $notula->display_name }} — <strong>{{ \App\Helpers\Helper::formatCurrencyOutput($notula->residuo) }}</strong>@if ($notula->expected_invoice_date) — {{ trans('mail.tenant_notule_expected_invoice') }}: {{ \App\Helpers\Helper::getFormattedDateObject($notula->expected_invoice_date, 'date', false) }}@endif</li>
@endforeach
</ul>
<p style="font-size:15px;"><strong>{{ trans('mail.tenant_notule_residuo_total') }}: {{ \App\Helpers\Helper::formatCurrencyOutput($residuoTotal) }}</strong></p>
<p><a href="{{ route('erp.notule.index') }}" style="display:inline-block;background:#2f6fed;color:#fff;padding:9px 16px;border-radius:5px;text-decoration:none;">{{ trans('erp/notule.title') }}</a></p>
@include('notifications.html._footer')
