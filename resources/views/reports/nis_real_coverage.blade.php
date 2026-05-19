@extends('layouts/default')

@section('title')
    {{ trans('admin/reports/general.nis_real_coverage') }}
    @parent
@stop

@section('content')
    <x-container>
        <x-box>
            <div class="table-responsive">
                <table class="table table-striped snipe-table">
                    <thead>
                        <tr>
                            <th>{{ trans('admin/documentframeworks/general.coverage.total_requirements') }}</th>
                            <th>{{ trans('admin/documentframeworkrequirements/general.coverage.covered') }}</th>
                            <th>{{ trans('admin/documentframeworkrequirements/general.coverage.at_risk') }}</th>
                            <th>{{ trans('admin/documentframeworkrequirements/general.coverage.supporting_only') }}</th>
                            <th>{{ trans('admin/documentframeworkrequirements/general.coverage.missing') }}</th>
                            <th>{{ trans('admin/documentframeworks/general.coverage.coverage_percent') }}</th>
                            <th>{{ trans('admin/documentframeworkrequirements/table.minimum_required_documents') }}</th>
                            <th>{{ trans('admin/documentframeworkrequirements/table.healthy_primary_documents_count') }}</th>
                            <th>{{ trans('admin/documentframeworkrequirements/table.document_shortfall_count') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>{{ number_format($summary['total']) }}</td>
                            <td><span class="label label-success">{{ number_format($summary['covered']) }}</span></td>
                            <td><span class="label label-danger">{{ number_format($summary['at_risk']) }}</span></td>
                            <td><span class="label label-warning">{{ number_format($summary['supporting_only']) }}</span></td>
                            <td><span class="label label-default">{{ number_format($summary['missing']) }}</span></td>
                            <td>{{ $summary['coverage_percent'] }}%</td>
                            <td>{{ number_format($summary['minimum_required_documents']) }}</td>
                            <td>{{ number_format($summary['healthy_primary_documents']) }}</td>
                            <td><span class="{{ $summary['document_shortfall_count'] > 0 ? 'text-danger' : '' }}">{{ number_format($summary['document_shortfall_count']) }}</span></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </x-box>

        <x-box>
            <h2 class="box-title">{{ trans('admin/reports/general.nis_real_coverage_frameworks') }}</h2>
            <div class="table-responsive">
                <table
                    data-cookie-id-table="nisRealCoverageFrameworkReport"
                    data-id-table="nisRealCoverageFrameworkReport"
                    id="nisRealCoverageFrameworkReport"
                    data-pagination="true"
                    data-search="true"
                    data-show-columns="true"
                    data-show-export="true"
                    data-export-options='{"fileName": "nis-real-coverage-frameworks-{{ date('Y-m-d') }}"}'
                    class="table table-striped snipe-table">
                    <thead>
                        <tr>
                            <th data-sortable="true">{{ trans('general.document_framework') }}</th>
                            <th data-sortable="true">{{ trans('admin/companies/table.title') }}</th>
                            <th data-sortable="true">{{ trans('admin/documentframeworks/general.coverage.total_requirements') }}</th>
                            <th data-sortable="true">{{ trans('admin/documentframeworkrequirements/general.coverage.covered') }}</th>
                            <th data-sortable="true">{{ trans('admin/documentframeworkrequirements/general.coverage.at_risk') }}</th>
                            <th data-sortable="true">{{ trans('admin/documentframeworkrequirements/general.coverage.missing') }}</th>
                            <th data-sortable="true">{{ trans('admin/documentframeworks/general.coverage.coverage_percent') }}</th>
                            <th data-sortable="true">{{ trans('admin/documentframeworkrequirements/table.document_shortfall_count') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($frameworkRows as $row)
                            <tr>
                                <td>
                                    @can('view', $row['framework'])
                                        <a href="{{ route('documentframeworks.show', $row['framework']) }}">{{ $row['framework']->name }}</a>
                                    @else
                                        {{ $row['framework']->name }}
                                    @endcan
                                </td>
                                <td>{{ $row['company_name'] }}</td>
                                <td>{{ number_format($row['summary']['total']) }}</td>
                                <td>{{ number_format($row['summary']['covered']) }}</td>
                                <td>{{ number_format($row['summary']['at_risk']) }}</td>
                                <td>{{ number_format($row['summary']['missing']) }}</td>
                                <td>{{ $row['summary']['coverage_percent'] }}%</td>
                                <td><span class="{{ $row['summary']['document_shortfall_count'] > 0 ? 'text-danger' : '' }}">{{ number_format($row['summary']['document_shortfall_count']) }}</span></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </x-box>

        <x-box>
            <h2 class="box-title">{{ trans('admin/reports/general.nis_real_coverage_requirements') }}</h2>
            <div class="table-responsive">
                <table
                    data-cookie-id-table="nisRealCoverageRequirementReport"
                    data-id-table="nisRealCoverageRequirementReport"
                    id="nisRealCoverageRequirementReport"
                    data-pagination="true"
                    data-search="true"
                    data-show-columns="true"
                    data-show-export="true"
                    data-export-options='{"fileName": "nis-real-coverage-requirements-{{ date('Y-m-d') }}"}'
                    class="table table-striped snipe-table">
                    <thead>
                        <tr>
                            <th data-sortable="true">{{ trans('admin/documentframeworkrequirements/table.code') }}</th>
                            <th data-sortable="true">{{ trans('admin/documentframeworkrequirements/table.title') }}</th>
                            <th data-sortable="true">{{ trans('general.document_framework') }}</th>
                            <th data-sortable="true">{{ trans('admin/companies/table.title') }}</th>
                            <th data-sortable="true">{{ trans('admin/documentframeworkrequirements/table.coverage') }}</th>
                            <th data-sortable="true">{{ trans('general.documents') }}</th>
                            <th data-sortable="true">{{ trans('admin/documentframeworkrequirements/table.primary_documents_count') }}</th>
                            <th data-sortable="true">{{ trans('admin/documentframeworkrequirements/table.healthy_primary_documents_count') }}</th>
                            <th data-sortable="true">{{ trans('admin/documentframeworkrequirements/table.minimum_required_documents') }}</th>
                            <th data-sortable="true">{{ trans('admin/documentframeworkrequirements/table.document_shortfall_count') }}</th>
                            <th data-sortable="true">{{ trans('admin/documentframeworkrequirements/table.owner') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($requirementRows as $row)
                            @php
                                $requirement = $row['requirement'];
                            @endphp
                            <tr>
                                <td>
                                    @can('view', $requirement)
                                        <a href="{{ route('documentframeworkrequirements.show', $requirement) }}">{{ $requirement->code }}</a>
                                    @else
                                        {{ $requirement->code }}
                                    @endcan
                                </td>
                                <td>{{ $requirement->title }}</td>
                                <td>{{ $row['framework']->name }}</td>
                                <td>{{ $row['company_name'] }}</td>
                                <td><span class="{{ $row['coverage_class'] }}">{{ $requirement->coverage_label }}</span></td>
                                <td><span class="{{ $requirement->document_minimum_satisfied ? '' : 'text-danger' }}">{{ number_format($requirement->documents_count) }}</span></td>
                                <td>{{ number_format($requirement->primary_documents_count) }}</td>
                                <td>{{ number_format($requirement->healthy_primary_documents_count) }}</td>
                                <td>{{ number_format($requirement->minimum_required_documents) }}</td>
                                <td><span class="{{ $requirement->document_shortfall_count > 0 ? 'text-danger' : '' }}">{{ number_format($requirement->document_shortfall_count) }}</span></td>
                                <td>{{ $requirement->owner?->display_name ?: '-' }}</td>
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
