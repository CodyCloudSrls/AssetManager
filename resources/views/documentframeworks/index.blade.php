@extends('layouts/default')

@section('title')
@php($ccDomainKey = request('compliance_domain'))
@php($ccDomain = $ccDomainKey ? \App\Models\ComplianceDomain::where('key', $ccDomainKey)->value('name') : null)
{{ $ccDomain ? trans('general.document_frameworks').' — '.$ccDomain : trans('general.document_frameworks') }}
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
                api_url="{{ route('api.documentframeworks.index', request()->only(['tenant_id', 'status', 'is_active', 'compliance_domain'])) }}"
                :presenter="\App\Presenters\DocumentFrameworkPresenter::dataTableLayout()"
                export_filename="export-document-frameworks-{{ date('Y-m-d') }}"
            />
        </x-box>
    </x-container>
@stop

@section('moar_scripts')
    @include ('partials.bootstrap-table')
@stop
