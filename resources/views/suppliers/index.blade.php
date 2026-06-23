@extends('layouts/default')

{{-- Page title --}}
@section('title')
{{ trans('admin/suppliers/table.suppliers') }}
@parent
@stop

{{-- Page content --}}
@section('content')
    <x-container>
        <x-box>

            <x-slot:bulkactions>
                <x-table.bulk-actions
                        name='supplier'
                        action_route="{{route('suppliers.bulkedit.show')}}"
                        model_name="supplier"
                >
                    @can('update', App\Models\Supplier::class)
                        <option value="edit">{{ trans('general.bulk_edit') }}</option>
                    @endcan
                    @can('delete', App\Models\Supplier::class)
                        <option value="delete">{{ trans('general.delete') }}</option>
                    @endcan
                </x-table.bulk-actions>
            </x-slot:bulkactions>

            @if (count($companyOptions) > 1)
                <form method="GET" action="{{ route('suppliers.index') }}" class="form-inline" style="margin-bottom:12px; display:flex; align-items:center; gap:8px; flex-wrap:wrap;">
                    <label for="company_id" style="margin:0; font-weight:600;">
                        <x-icon type="filter" class="fa-fw" /> {{ trans('general.company') }}
                    </label>
                    <select name="company_id" id="company_id" class="form-control" onchange="this.form.submit()" style="min-width:240px;">
                        <option value="">{{ trans('admin/tenantservices/general.company_filter_all') }}</option>
                        @foreach ($companyOptions as $companyId => $companyName)
                            <option value="{{ $companyId }}" {{ (string) request('company_id') === (string) $companyId ? 'selected' : '' }}>{{ $companyName }}</option>
                        @endforeach
                    </select>
                    @if (request()->filled('company_id'))
                        <a href="{{ route('suppliers.index') }}" class="btn btn-default btn-sm">{{ trans('admin/tenantservices/general.company_filter_clear') }}</a>
                    @endif
                </form>
            @endif

            <x-table
                name="supplier"
                buttons="supplierButtons"
                fixed_right_number="1"
                fixed_number="1"
                api_url="{{ route('api.suppliers.index', request()->only(['tenant_id', 'company_id', 'nis_relevant', 'nis_relevance_type', 'nis_criticality', 'nis_assessment_status', 'nis_assessment_method', 'nis_assessment_outcome', 'nis_review_status', 'cpv_code'])) }}"
                :presenter="\App\Presenters\SupplierPresenter::dataTableLayout()"
                export_filename="export-suppliers-{{ date('Y-m-d') }}"
            />

        </x-box>
    </x-container>
@stop

@section('moar_scripts')
@include ('partials.bootstrap-table', ['exportFile' => 'suppliers-export', 'search' => true])
@stop
