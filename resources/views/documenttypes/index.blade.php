@extends('layouts/default')

@section('title')
{{ trans('general.document_types') }}
@parent
@stop

@section('content')
    <x-container>
        <x-box>
            <x-table
                name="documenttype"
                buttons="documenttypeButtons"
                fixed_right_number="1"
                fixed_number="1"
                api_url="{{ route('api.documenttypes.index', request()->only(['status'])) }}"
                :presenter="\App\Presenters\DocumentTypePresenter::dataTableLayout()"
                export_filename="export-document-types-{{ date('Y-m-d') }}"
            />
        </x-box>
    </x-container>
@stop

@section('moar_scripts')
    @include ('partials.bootstrap-table')
@stop
