@extends('layouts/default')

@section('title')
{{ trans('general.customers') }}
@parent
@stop

@section('content')
    <x-container>
        <x-box>
            <x-slot:bulkactions>
                <x-table.bulk-actions
                    name="customer"
                    action_route="{{ route('customers.bulk.delete') }}"
                    model_name="customer"
                >
                    @can('delete', App\Models\Customer::class)
                        <option>{{ trans('general.delete') }}</option>
                    @endcan
                </x-table.bulk-actions>
            </x-slot:bulkactions>

            <x-table
                name="customer"
                buttons="customerButtons"
                fixed_right_number="1"
                fixed_number="1"
                api_url="{{ route('api.customers.index', request()->only(['tenant_id', 'company_id', 'status', 'nis_profile', 'nis_service_role', 'nis_criticality', 'nis_review_status'])) }}"
                :presenter="\App\Presenters\CustomerPresenter::dataTableLayout()"
                export_filename="export-customers-{{ date('Y-m-d') }}"
            />
        </x-box>
    </x-container>
@stop

@section('moar_scripts')
@include('partials.bootstrap-table', ['exportFile' => 'customers-export', 'search' => true])
@stop
