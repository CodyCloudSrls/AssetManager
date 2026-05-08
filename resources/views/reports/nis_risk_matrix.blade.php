@extends('layouts/default')

@section('title')
    {{ trans('admin/reports/general.nis_risk_matrix') }}
    @parent
@stop

@section('content')
    <x-container>
        <x-box>
            <div class="table-responsive">
                <table class="table table-striped snipe-table">
                    <thead>
                        <tr>
                            @foreach ($riskLevelOptions as $level => $label)
                                <th>{{ $label }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            @foreach ($riskLevelOptions as $level => $label)
                                <td>
                                    <span class="{{ match ($level) {
                                        \App\Support\Reports\NisRiskMatrixReport::RISK_CRITICAL => 'label label-danger',
                                        \App\Support\Reports\NisRiskMatrixReport::RISK_HIGH => 'label label-warning',
                                        \App\Support\Reports\NisRiskMatrixReport::RISK_MEDIUM => 'label label-info',
                                        \App\Support\Reports\NisRiskMatrixReport::RISK_LOW => 'label label-success',
                                        default => 'label label-default',
                                    } }}">{{ number_format($summary[$level] ?? 0) }}</span>
                                </td>
                            @endforeach
                        </tr>
                    </tbody>
                </table>
            </div>
        </x-box>

        <x-box>
            <h2 class="box-title">{{ trans('admin/reports/general.nis_category_summary') }}</h2>
            <div class="table-responsive">
                <table
                    data-cookie-id-table="nisRiskCategoryReport"
                    data-id-table="nisRiskCategoryReport"
                    id="nisRiskCategoryReport"
                    data-pagination="true"
                    data-search="true"
                    data-show-columns="true"
                    data-show-export="true"
                    data-export-options='{"fileName": "nis-risk-categories-{{ date('Y-m-d') }}"}'
                    class="table table-striped snipe-table">
                    <thead>
                        <tr>
                            <th data-sortable="true">{{ trans('admin/reports/general.nis_category') }}</th>
                            <th data-sortable="true">{{ trans('admin/reports/general.nis_scope') }}</th>
                            <th data-sortable="true">{{ trans('admin/reports/general.nis_assets_count') }}</th>
                            @foreach ($riskLevelOptions as $level => $label)
                                <th data-sortable="true">{{ $label }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($categoryRows as $categoryRow)
                            <tr>
                                <td>
                                    @if ($categoryRow['category'])
                                        @can('view', $categoryRow['category'])
                                            <a href="{{ route('categories.show', $categoryRow['category']) }}">{{ $categoryRow['category_name'] }}</a>
                                        @else
                                            {{ $categoryRow['category_name'] }}
                                        @endcan
                                    @else
                                        {{ $categoryRow['category_name'] }}
                                    @endif
                                </td>
                                <td>{{ $categoryRow['scope_label'] }}</td>
                                <td>{{ number_format($categoryRow['assets_count']) }}</td>
                                @foreach ($riskLevelOptions as $level => $label)
                                    <td>{{ number_format($categoryRow['counts'][$level] ?? 0) }}</td>
                                @endforeach
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </x-box>

        <x-box>
            <h2 class="box-title">{{ trans('admin/reports/general.nis_asset_matrix') }}</h2>
            <div class="table-responsive">
                <table
                    data-cookie-id-table="nisRiskAssetReport"
                    data-id-table="nisRiskAssetReport"
                    id="nisRiskAssetReport"
                    data-pagination="true"
                    data-search="true"
                    data-show-columns="true"
                    data-show-export="true"
                    data-export-options='{"fileName": "nis-risk-assets-{{ date('Y-m-d') }}"}'
                    class="table table-striped snipe-table">
                    <thead>
                        <tr>
                            <th data-sortable="true">{{ trans('admin/reports/general.nis_asset') }}</th>
                            <th data-sortable="true">{{ trans('admin/companies/table.title') }}</th>
                            <th data-sortable="true">{{ trans('admin/reports/general.nis_category') }}</th>
                            <th data-sortable="true">{{ trans('admin/reports/general.nis_scope') }}</th>
                            <th data-sortable="true">{{ trans('admin/reports/general.nis_service_impact') }}</th>
                            <th data-sortable="true">{{ trans('admin/reports/general.nis_exposure') }}</th>
                            <th data-sortable="true">{{ trans('admin/reports/general.nis_source') }}</th>
                            <th data-sortable="true">{{ trans('admin/reports/general.nis_risk_score') }}</th>
                            <th data-sortable="true">{{ trans('admin/reports/general.nis_calculated_risk') }}</th>
                            <th data-sortable="true">{{ trans('admin/reports/general.nis_matrix_notes') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($rows as $row)
                            <tr>
                                <td>
                                    @can('view', $row['asset'])
                                        <a href="{{ route('hardware.show', $row['asset']) }}">{{ $row['asset_name'] }}</a>
                                    @else
                                        {{ $row['asset_name'] }}
                                    @endcan
                                </td>
                                <td>{{ $row['company_name'] }}</td>
                                <td>
                                    @if ($row['category'])
                                        @can('view', $row['category'])
                                            <a href="{{ route('categories.show', $row['category']) }}">{{ $row['category_name'] }}</a>
                                        @else
                                            {{ $row['category_name'] }}
                                        @endcan
                                    @else
                                        {{ $row['category_name'] }}
                                    @endif
                                </td>
                                <td>{{ $row['scope_label'] }}</td>
                                <td>{{ $row['service_impact_label'] }}</td>
                                <td>{{ $row['exposure_label'] }}</td>
                                <td>{{ $row['source_label'] }}</td>
                                <td>{{ number_format($row['risk_score']) }}</td>
                                <td><span class="{{ $row['risk_class'] }}">{{ $row['risk_label'] }}</span></td>
                                <td>{{ $row['notes'] ?: '-' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </x-box>
    </x-container>
@stop

@section('moar_scripts')
    @include('partials.bootstrap-table')
@stop
