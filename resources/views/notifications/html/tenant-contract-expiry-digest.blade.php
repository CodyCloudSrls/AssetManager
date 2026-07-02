@include('notifications.html._header')
<p>{{ trans('mail.tenant_contract_expiry_intro', ['days' => $warningDays]) }}</p>
<ul style="padding-left:18px;margin:0 0 12px;">
@foreach ($contracts as $contract)
    <li style="margin:4px 0;">
        <a href="{{ route('contracts.show', $contract) }}" style="color:#2f6fed;">{{ $contract->display_name }}</a>@if ($contract->customer) — {{ $contract->customer->name }}@endif
        @if ($contract->renewal_due_at) — {{ trans('mail.tenant_contract_renewal_due') }}: {{ \App\Helpers\Helper::getFormattedDateObject($contract->renewal_due_at, 'date', false) }}@endif
        @if ($contract->ends_at) — {{ trans('mail.tenant_contract_ends') }}: {{ \App\Helpers\Helper::getFormattedDateObject($contract->ends_at, 'date', false) }}@endif
    </li>
@endforeach
</ul>
<p><a href="{{ route('contracts.index') }}" style="display:inline-block;background:#2f6fed;color:#fff;padding:9px 16px;border-radius:5px;text-decoration:none;">{{ trans('erp/general.modules.contracts') }}</a></p>
@include('notifications.html._footer')
