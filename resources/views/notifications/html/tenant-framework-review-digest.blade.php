@include('notifications.html._header')
<p>{{ trans('mail.tenant_framework_review_intro', ['days' => $warningDays]) }}</p>
<ul style="padding-left:18px;margin:0 0 12px;">
@foreach ($frameworks as $framework)
    <li style="margin:4px 0;">
        <a href="{{ route('documentframeworks.show', $framework) }}" style="color:#2f6fed;">{{ $framework->name }}</a>@if ($framework->review_due_at) — {{ trans('mail.tenant_framework_review_due') }}: {{ \App\Helpers\Helper::getFormattedDateObject($framework->review_due_at, 'date', false) }}@endif
        @if ($framework->review_cadence_months) — {{ trans('mail.tenant_framework_cadence', ['months' => $framework->review_cadence_months]) }}@endif
    </li>
@endforeach
</ul>
@include('notifications.html._footer')
