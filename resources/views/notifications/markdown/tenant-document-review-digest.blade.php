@component('mail::message')
# {{ trans('mail.tenant_notification_greeting', ['tenant' => $tenant->display_name]) }}

{{ trans('mail.tenant_document_review_intro', ['days' => $warningDays]) }}

@if ($dueDocuments->count() > 0)
## {{ trans('mail.tenant_document_review_due') }}
@foreach ($dueDocuments as $document)
 - [{{ $document->name }}]({{ route('documents.show', $document) }}) - {{ $document->company?->name ?? '-' }} - {{ \App\Helpers\Helper::getFormattedDateObject($document->next_review_at, 'date', false) }}
@endforeach
@endif

@if ($overdueDocuments->count() > 0)
## {{ trans('mail.tenant_document_review_overdue') }}
@foreach ($overdueDocuments as $document)
 - [{{ $document->name }}]({{ route('documents.show', $document) }}) - {{ $document->company?->name ?? '-' }} - {{ \App\Helpers\Helper::getFormattedDateObject($document->next_review_at, 'date', false) }}
@endforeach
@endif

@component('mail::button', ['url' => route('documents.index', ['review_status' => 'due'])])
{{ trans('mail.view_documents') }}
@endcomponent

{{ trans('mail.best_regards') }}<br>
{{ config('app.name') }}
@endcomponent
