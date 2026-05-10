@extends('layouts/default')

@section('title')
{{ trans('admin/reports/general.contract_forecast') }}
@parent
@stop

@section('content')
    <x-container>
        <x-box>
            <form method="GET" action="{{ route('reports.contract-forecast') }}" class="form-inline" style="margin-bottom: 18px;">
                <div class="form-group">
                    <label for="from">{{ trans('admin/reports/general.from') }}</label>
                    <input class="form-control" type="text" name="from" id="from" value="{{ request('from', $from->format('Y-m-d')) }}" placeholder="YYYY-MM-DD">
                </div>
                <div class="form-group" style="margin-left: 10px;">
                    <label for="to">{{ trans('admin/reports/general.to') }}</label>
                    <input class="form-control" type="text" name="to" id="to" value="{{ request('to', $to->format('Y-m-d')) }}" placeholder="YYYY-MM-DD">
                </div>
                @if (request('tenant_id'))
                    <input type="hidden" name="tenant_id" value="{{ request('tenant_id') }}">
                @endif
                <button type="submit" class="btn btn-primary" style="margin-left: 10px;">
                    <x-icon type="search" />
                    {{ trans('general.apply') }}
                </button>
            </form>

            <h3>{{ trans('admin/reports/general.contract_forecast_summary') }}</h3>
            <div class="table-responsive">
                <table class="table table-striped snipe-table">
                    <thead>
                        <tr>
                            <th>{{ trans('admin/contracts/general.currency') }}</th>
                            <th>{{ trans('admin/contracts/general.revenue') }}</th>
                            <th>{{ trans('admin/contracts/general.cost') }}</th>
                            <th>{{ trans('admin/contracts/general.net') }}</th>
                            <th>{{ trans('admin/contracts/general.contracts') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($summaryRows as $row)
                            <tr>
                                <td>{{ $row['currency'] }}</td>
                                <td>{{ $row['currency'] }} {{ \App\Helpers\Helper::formatCurrencyOutput($row['revenue']) }}</td>
                                <td>{{ $row['currency'] }} {{ \App\Helpers\Helper::formatCurrencyOutput($row['cost']) }}</td>
                                <td>{{ $row['currency'] }} {{ \App\Helpers\Helper::formatCurrencyOutput($row['net']) }}</td>
                                <td>{{ $row['contracts_count'] }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="text-muted">{{ trans('general.no_results') }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <h3>{{ trans('admin/reports/general.contract_forecast_monthly') }}</h3>
            <div class="table-responsive">
                <table class="table table-striped snipe-table">
                    <thead>
                        <tr>
                            <th>{{ trans('admin/reports/general.period') }}</th>
                            <th>{{ trans('admin/contracts/general.currency') }}</th>
                            <th>{{ trans('admin/contracts/general.revenue') }}</th>
                            <th>{{ trans('admin/contracts/general.cost') }}</th>
                            <th>{{ trans('admin/contracts/general.net') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($monthlyRows as $row)
                            <tr>
                                <td>{{ $row['month_label'] }}</td>
                                <td>{{ $row['currency'] }}</td>
                                <td>{{ $row['currency'] }} {{ \App\Helpers\Helper::formatCurrencyOutput($row['revenue']) }}</td>
                                <td>{{ $row['currency'] }} {{ \App\Helpers\Helper::formatCurrencyOutput($row['cost']) }}</td>
                                <td>{{ $row['currency'] }} {{ \App\Helpers\Helper::formatCurrencyOutput($row['net']) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="row">
                <div class="col-md-6">
                    <h3>{{ trans('admin/reports/general.contract_forecast_quarterly') }}</h3>
                    <div class="table-responsive">
                        <table class="table table-striped snipe-table">
                            <thead>
                                <tr>
                                    <th>{{ trans('admin/reports/general.period') }}</th>
                                    <th>{{ trans('admin/contracts/general.currency') }}</th>
                                    <th>{{ trans('admin/contracts/general.revenue') }}</th>
                                    <th>{{ trans('admin/contracts/general.cost') }}</th>
                                    <th>{{ trans('admin/contracts/general.net') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($quarterRows as $row)
                                    <tr>
                                        <td>{{ $row['label'] }}</td>
                                        <td>{{ $row['currency'] }}</td>
                                        <td>{{ $row['currency'] }} {{ \App\Helpers\Helper::formatCurrencyOutput($row['revenue']) }}</td>
                                        <td>{{ $row['currency'] }} {{ \App\Helpers\Helper::formatCurrencyOutput($row['cost']) }}</td>
                                        <td>{{ $row['currency'] }} {{ \App\Helpers\Helper::formatCurrencyOutput($row['net']) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="col-md-6">
                    <h3>{{ trans('admin/reports/general.contract_forecast_yearly') }}</h3>
                    <div class="table-responsive">
                        <table class="table table-striped snipe-table">
                            <thead>
                                <tr>
                                    <th>{{ trans('admin/reports/general.period') }}</th>
                                    <th>{{ trans('admin/contracts/general.currency') }}</th>
                                    <th>{{ trans('admin/contracts/general.revenue') }}</th>
                                    <th>{{ trans('admin/contracts/general.cost') }}</th>
                                    <th>{{ trans('admin/contracts/general.net') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($yearRows as $row)
                                    <tr>
                                        <td>{{ $row['label'] }}</td>
                                        <td>{{ $row['currency'] }}</td>
                                        <td>{{ $row['currency'] }} {{ \App\Helpers\Helper::formatCurrencyOutput($row['revenue']) }}</td>
                                        <td>{{ $row['currency'] }} {{ \App\Helpers\Helper::formatCurrencyOutput($row['cost']) }}</td>
                                        <td>{{ $row['currency'] }} {{ \App\Helpers\Helper::formatCurrencyOutput($row['net']) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <h3>{{ trans('admin/reports/general.contract_forecast_by_contract') }}</h3>
            <div class="table-responsive">
                <table class="table table-striped snipe-table">
                    <thead>
                        <tr>
                            <th>{{ trans('admin/contracts/general.contract') }}</th>
                            <th>{{ trans('general.customer') }}</th>
                            <th>{{ trans('general.company') }}</th>
                            <th>{{ trans('admin/contracts/general.currency') }}</th>
                            <th>{{ trans('admin/contracts/general.revenue') }}</th>
                            <th>{{ trans('admin/contracts/general.cost') }}</th>
                            <th>{{ trans('admin/contracts/general.net') }}</th>
                            <th>{{ trans('admin/contracts/general.margin_percent') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($contractRows as $row)
                            <tr>
                                <td><a href="{{ route('contracts.show', $row['contract']) }}">{{ $row['contract']->name }}</a></td>
                                <td>{{ $row['customer']?->name }}</td>
                                <td>{{ $row['company']?->name }}</td>
                                <td>{{ $row['currency'] }}</td>
                                <td>{{ $row['currency'] }} {{ \App\Helpers\Helper::formatCurrencyOutput($row['revenue']) }}</td>
                                <td>{{ $row['currency'] }} {{ \App\Helpers\Helper::formatCurrencyOutput($row['cost']) }}</td>
                                <td>{{ $row['currency'] }} {{ \App\Helpers\Helper::formatCurrencyOutput($row['net']) }}</td>
                                <td>{{ is_null($row['margin_percent']) ? '' : $row['margin_percent'].'%' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="8" class="text-muted">{{ trans('general.no_results') }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-box>
    </x-container>
@endsection
