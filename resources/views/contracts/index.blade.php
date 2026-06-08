@extends('layouts/default')

@section('title')
{{ trans('admin/contracts/general.contracts') }}
@parent
@stop

@section('content')
    <x-container>
        <x-box>
            <x-table
                name="contract"
                buttons="contractButtons"
                fixed_right_number="1"
                api_url="{{ route('api.contracts.index', request()->only(['tenant_id', 'company_id', 'customer_id', 'status', 'renewal_status', 'tenant_service_id'])) }}"
                :presenter="\App\Presenters\CustomerContractPresenter::dataTableLayout()"
                export_filename="export-contracts-{{ date('Y-m-d') }}"
            />
        </x-box>
    </x-container>
@stop

@section('moar_scripts')
@include('partials.bootstrap-table', ['exportFile' => 'contracts-export', 'search' => true])
@stop
