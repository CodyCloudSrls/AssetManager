@component('mail::message')
# {{ trans('mail.tenant_notification_greeting', ['tenant' => $tenant->display_name]) }}

{{ trans('mail.tenant_document_assignment_reminder_intro', ['days' => $warningDays]) }}

@if ($dueAssignments->count() > 0)
## {{ trans('mail.tenant_document_assignment_reminder_due') }}
@foreach ($dueAssignments as $assignment)
 - [{{ $assignment->document?->name ?? trans('general.na') }}]({{ $assignment->document ? route('documents.show', $assignment->document) : route('documents.evidence_requests.index') }}) - {{ $assignment->assignable_display_name ?? trans('general.na') }} - {{ $assignment->company?->name ?? '-' }} - {{ \App\Helpers\Helper::getFormattedDateObject($assignment->renewal_due_at, 'date', false) }} - {{ $assignment->approval_status_label }}@if ($assignment->reviewer) - {{ trans('admin/documents/form.assignment_reviewer') }}: {{ $assignment->reviewer->display_name }}@endif
@endforeach
@endif

@if ($overdueAssignments->count() > 0)
## {{ trans('mail.tenant_document_assignment_reminder_escalated') }}
@foreach ($overdueAssignments as $assignment)
 - [{{ $assignment->document?->name ?? trans('general.na') }}]({{ $assignment->document ? route('documents.show', $assignment->document) : route('documents.evidence_requests.index') }}) - {{ $assignment->assignable_display_name ?? trans('general.na') }} - {{ $assignment->company?->name ?? '-' }} - {{ \App\Helpers\Helper::getFormattedDateObject($assignment->renewal_due_at ?: $assignment->expires_at, 'date', false) }} - {{ $assignment->approval_status_label }}@if ($assignment->reviewer) - {{ trans('admin/documents/form.assignment_reviewer') }}: {{ $assignment->reviewer->display_name }}@endif
@endforeach
@endif

@component('mail::button', ['url' => route('documents.evidence_requests.index', ['review_status' => $overdueAssignments->count() > 0 ? 'overdue' : 'due'])])
{{ trans('mail.view_evidence_requests') }}
@endcomponent

{{ trans('mail.best_regards') }}<br>
{{ config('app.name') }}
@endcomponent
