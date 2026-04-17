@extends('layouts/default')

@section('title')
{{ trans('general.document_frameworks') }}
@parent
@stop

@section('content')
    <x-container>
        <x-box>
            <x-table
                name="documentframework"
                buttons="documentframeworkButtons"
                fixed_right_number="1"
                fixed_number="1"
                api_url="{{ route('api.documentframeworks.index', request()->only(['status'])) }}"
                :presenter="\App\Presenters\DocumentFrameworkPresenter::dataTableLayout()"
                export_filename="export-document-frameworks-{{ date('Y-m-d') }}"
            />
        </x-box>
    </x-container>
@stop

@section('moar_scripts')
    @include ('partials.bootstrap-table')
@stop
