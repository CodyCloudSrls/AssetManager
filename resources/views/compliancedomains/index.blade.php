@extends('layouts/default')

@section('title')
{{ trans('admin/compliancedomains/general.title') }}
@parent
@stop

@section('content')
    <x-container>
        <x-box>
            <x-table
                name="compliancedomain"
                buttons="complianceDomainButtons"
                fixed_right_number="1"
                fixed_number="1"
                api_url="{{ route('api.compliancedomains.index', request()->only(['status', 'is_active'])) }}"
                :presenter="\App\Presenters\ComplianceDomainPresenter::dataTableLayout()"
                export_filename="export-compliance-domains-{{ date('Y-m-d') }}"
            />
        </x-box>
    </x-container>
@stop

@section('moar_scripts')
    @include('partials.bootstrap-table')
@stop
