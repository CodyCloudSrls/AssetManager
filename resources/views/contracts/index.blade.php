@extends('layouts/default')

@section('title')
{{ trans('admin/contracts/general.contracts') }}
@parent
@stop

@section('content')
    <x-container>
        <x-box name="contract">
            <x-slot:bulkactions>
                <x-table.bulk-contracts/>
            </x-slot:bulkactions>

            <div class="row" style="margin-bottom: 15px;">
                <div class="col-md-12">
                    <form method="get" action="{{ route('contracts.index') }}" class="form-inline" role="search">
                        @foreach (request()->only(['tenant_id', 'company_id', 'customer_id', 'renewal_status', 'tenant_service_id']) as $filterName => $filterValue)
                            <input type="hidden" name="{{ $filterName }}" value="{{ $filterValue }}">
                        @endforeach

                        <div class="form-group">
                            <label for="contract_status_filter" class="sr-only">{{ trans('general.status') }}</label>
                            <select class="form-control select2" name="status" id="contract_status_filter" aria-label="{{ trans('general.status') }}" style="min-width: 180px;">
                                <option value="">{{ trans('admin/contracts/general.all_statuses') }}</option>
                                @foreach (\App\Models\CustomerContract::statusOptions() as $statusValue => $statusLabel)
                                    <option value="{{ $statusValue }}" @selected(request('status') === $statusValue)>{{ $statusLabel }}</option>
                                @endforeach
                            </select>
                        </div>

                        <button type="submit" class="btn btn-primary">
                            <x-icon type="search" />
                            {{ trans('admin/contracts/general.apply_filters') }}
                        </button>

                        <a href="{{ route('contracts.index') }}" class="btn btn-default">
                            {{ trans('admin/contracts/general.clear_filters') }}
                        </a>
                    </form>
                </div>
            </div>

            <x-table
                name="contract"
                buttons="contractButtons"
                fixed_right_number="1"
                sort_reset="true"
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
