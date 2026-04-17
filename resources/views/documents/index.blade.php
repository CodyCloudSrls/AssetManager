@extends('layouts/default')

@section('title')
    @if (request('review_status') === 'due')
        {{ trans('admin/documents/general.review_due') }}
    @elseif (request('review_status') === 'overdue')
        {{ trans('admin/documents/general.review_overdue') }}
    @elseif (request('status'))
        {{ trans('admin/documents/general.statuses.'.request('status')) }}
    @elseif (request('status_type') === 'Deleted')
        {{ trans('general.deleted') }}
    @else
        {{ trans('general.all') }}
    @endif
    {{ trans('general.documents') }}
    @parent
@stop

@section('content')
    <x-container>
        <x-box name="documents">
            <x-table.documents :route="route('api.documents.index', request()->only(['status', 'review_status', 'status_type', 'company_id', 'owner_id', 'document_type_id', 'document_framework_id']))"/>
        </x-box>
    </x-container>
@stop

@section('moar_scripts')
    @include('partials.bootstrap-table')
@stop
