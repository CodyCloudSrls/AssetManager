@extends('layouts/default')

@section('title')
    {{ $documentframework->name }}
    @parent
@stop

@section('header_right')
    <x-button.info-panel-toggle/>
@endsection

@php
    $coverageSummary = $documentframework->coverage_summary;
@endphp

@section('content')
    <style>
        .framework-coverage-grid {
            display: grid;
            gap: 12px;
            grid-template-columns: repeat(5, minmax(0, 1fr));
            margin-bottom: 16px;
        }
        .framework-coverage-card {
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid var(--table-border-row-color);
            border-radius: 4px;
            padding: 14px 16px;
        }
        .framework-coverage-card__value {
            color: var(--color-fg);
            font-size: 28px;
            font-weight: 700;
            line-height: 1.1;
        }
        .framework-coverage-card__label {
            color: var(--color-muted);
            font-size: 12px;
            margin-top: 4px;
            text-transform: uppercase;
        }
        .framework-coverage-progress {
            background: rgba(255, 255, 255, 0.06);
            border-radius: 999px;
            height: 10px;
            overflow: hidden;
        }
        .framework-coverage-progress__bar {
            background: #00a65a;
            height: 100%;
        }
        .framework-meta-note {
            color: var(--color-muted);
            margin-top: 6px;
        }
        @media (max-width: 991px) {
            .framework-coverage-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }
        @media (max-width: 767px) {
            .framework-coverage-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>

    <x-container columns="2">
        <x-page-column class="col-md-9 main-panel">
            <x-tabs>
                <x-slot:tabnav>
                    <x-tabs.details-tab/>
                    <x-tabs.nav-item name="requirements" :label="trans('admin/documentframeworks/general.requirements_tab')" :count="$documentframework->requirements_count" icon="fa-solid fa-list-check"/>
                    <x-tabs.nav-item name="documents" :label="trans('general.documents')" :count="$documentframework->documents_count" icon="fa-regular fa-file-lines"/>
                </x-slot:tabnav>

                <x-slot:tabpanes>
                    <x-tabs.pane name="details">
                        <div class="framework-coverage-grid">
                            <div class="framework-coverage-card">
                                <div class="framework-coverage-card__value">{{ number_format($coverageSummary['total']) }}</div>
                                <div class="framework-coverage-card__label">{{ trans('admin/documentframeworks/general.coverage.total_requirements') }}</div>
                            </div>
                            <div class="framework-coverage-card">
                                <div class="framework-coverage-card__value">{{ number_format($coverageSummary['covered']) }}</div>
                                <div class="framework-coverage-card__label">{{ trans('admin/documentframeworkrequirements/general.coverage.covered') }}</div>
                            </div>
                            <div class="framework-coverage-card">
                                <div class="framework-coverage-card__value">{{ number_format($coverageSummary['at_risk']) }}</div>
                                <div class="framework-coverage-card__label">{{ trans('admin/documentframeworkrequirements/general.coverage.at_risk') }}</div>
                            </div>
                            <div class="framework-coverage-card">
                                <div class="framework-coverage-card__value">{{ number_format($coverageSummary['supporting_only']) }}</div>
                                <div class="framework-coverage-card__label">{{ trans('admin/documentframeworkrequirements/general.coverage.supporting_only') }}</div>
                            </div>
                            <div class="framework-coverage-card">
                                <div class="framework-coverage-card__value">{{ number_format($coverageSummary['missing']) }}</div>
                                <div class="framework-coverage-card__label">{{ trans('admin/documentframeworkrequirements/general.coverage.missing') }}</div>
                            </div>
                        </div>

                        <div class="framework-coverage-card" style="margin-bottom: 18px;">
                            <div class="framework-coverage-card__value">{{ $coverageSummary['coverage_percent'] }}%</div>
                            <div class="framework-coverage-card__label">{{ trans('admin/documentframeworks/general.coverage.coverage_percent') }}</div>
                            <div class="framework-coverage-progress" style="margin-top: 12px;">
                                <div class="framework-coverage-progress__bar" style="width: {{ $coverageSummary['coverage_percent'] }}%;"></div>
                            </div>
                            <div class="framework-meta-note">
                                {{ trans('admin/documentframeworks/general.coverage.coverage_help') }}
                            </div>
                        </div>

                        <div class="clearfix visible-lg-block" style="padding: 6px;"></div>

                        <x-page-column class="col-md-8 col-sm-12">
                            <x-page-data>
                                <x-data-row :label="trans('admin/documentframeworks/table.name')">{{ $documentframework->name }}</x-data-row>
                                <x-data-row :label="trans('admin/documentframeworks/table.framework_code')">{{ $documentframework->framework_code }}</x-data-row>
                                <x-data-row :label="trans('admin/documentframeworks/table.authority_name')">{{ $documentframework->authority_name }}</x-data-row>
                                <x-data-row :label="trans('admin/documentframeworks/table.framework_type')">
                                    {{ \App\Models\DocumentFramework::getFrameworkTypeOptions()[$documentframework->framework_type] ?? $documentframework->framework_type }}
                                </x-data-row>
                                <x-data-row :label="trans('admin/documentframeworks/table.compliance_domain')">
                                    {{ \App\Models\DocumentFramework::complianceDomainOptions()[$documentframework->compliance_domain] ?? $documentframework->compliance_domain }}
                                </x-data-row>
                                <x-data-row :label="trans('admin/documentframeworks/table.jurisdiction')">{{ $documentframework->jurisdiction }}</x-data-row>
                                <x-data-row :label="trans('admin/documentframeworks/table.version')">{{ $documentframework->version }}</x-data-row>
                                <x-data-row :label="trans('admin/documentframeworks/table.status')">
                                    {{ \App\Models\DocumentFramework::getStatusOptions()[$documentframework->status] ?? $documentframework->status }}
                                </x-data-row>
                                <x-data-row :label="trans('admin/documentframeworks/table.owner')">{{ $documentframework->owner?->display_name }}</x-data-row>
                                <x-data-row :label="trans('admin/documentframeworks/table.review_cadence_months')">
                                    @if ($documentframework->review_cadence_months)
                                        {{ trans('admin/documentframeworks/general.months_interval', ['count' => $documentframework->review_cadence_months]) }}
                                    @endif
                                </x-data-row>
                                <x-data-row :label="trans('admin/documentframeworks/table.effective_from')">{{ Helper::getFormattedDateObject($documentframework->effective_from, 'date', false) }}</x-data-row>
                                <x-data-row :label="trans('admin/documentframeworks/table.effective_to')">{{ Helper::getFormattedDateObject($documentframework->effective_to, 'date', false) }}</x-data-row>
                                <x-data-row :label="trans('admin/documentframeworks/table.external_reference_url')">
                                    @if ($documentframework->external_reference_url)
                                        <a href="{{ $documentframework->external_reference_url }}" target="_blank" rel="noopener noreferrer">{{ $documentframework->external_reference_url }}</a>
                                    @endif
                                </x-data-row>
                                <x-data-row :label="trans('admin/documentframeworks/table.description')">{{ $documentframework->description }}</x-data-row>
                                <x-data-row :label="trans('admin/documentframeworks/table.compliance_objective')">{{ $documentframework->compliance_objective }}</x-data-row>
                            </x-page-data>
                        </x-page-column>
                    </x-tabs.pane>

                    <x-tabs.pane name="requirements">
                        <div class="text-right" style="margin-bottom: 15px;">
                            @can('update', $documentframework)
                                <a href="{{ route('documentframeworkrequirements.create', $documentframework) }}" class="btn btn-primary">
                                    <x-icon type="plus" />
                                    {{ trans('admin/documentframeworkrequirements/general.create') }}
                                </a>
                            @endcan
                        </div>

                        <x-table.documentframeworkrequirements
                            name="documentframeworkrequirements"
                            :route="route('api.documentframeworkrequirements.index', ['document_framework_id' => $documentframework->id])"
                        />
                    </x-tabs.pane>

                    <x-tabs.pane name="documents">
                        <x-table.documents :route="route('api.documents.index', ['document_framework_id' => $documentframework->id])" />
                    </x-tabs.pane>
                </x-slot:tabpanes>
            </x-tabs>
        </x-page-column>

        <x-page-column class="col-md-3">
            <x-box class="side-box expanded">
                <div class="box-body">
                    <div class="text-right" style="margin-bottom: 15px;">
                        <x-button.edit :item="$documentframework" :route="route('documentframeworks.edit', $documentframework)" />
                        @can('delete', $documentframework)
                            @if ($documentframework->isDeletable())
                                <a href="{{ route('documentframeworks.destroy', $documentframework) }}"
                                   class="pull-right btn btn-sm btn-danger delete-asset"
                                   style="margin-right: 8px;"
                                   data-toggle="modal"
                                   data-title="{{ trans('general.delete') }}"
                                   data-content="{{ trans('general.sure_to_delete_var', ['item' => $documentframework->name]) }}"
                                   data-target="#dataConfirmModal"
                                   data-tooltip="true"
                                   data-icon="fa fa-trash"
                                   data-placement="top"
                                   onClick="return false;">
                                    <x-icon type="delete" class="fa-fw" />
                                </a>
                            @endif
                        @endcan
                    </div>

                    <x-page-data>
                        <x-data-row :label="trans('general.company')">{{ $documentframework->company?->name ?? trans('general.na') }}</x-data-row>
                        <x-data-row :label="trans('general.template_visibility.label')">{{ $documentframework->visibility_label }}</x-data-row>
                        <x-data-row :label="trans('admin/documentframeworks/table.slug')"><code>{{ $documentframework->slug }}</code></x-data-row>
                        <x-data-row :label="trans('admin/documentframeworks/table.source_pack_version')">{{ $documentframework->source_pack_version }}</x-data-row>
                        <x-data-row :label="trans('admin/documentframeworks/table.is_active')">{{ $documentframework->is_active ? trans('general.yes') : trans('general.no') }}</x-data-row>
                        <x-data-row :label="trans('admin/documentframeworks/table.sort_order')">{{ $documentframework->sort_order }}</x-data-row>
                        <x-data-row :label="trans('general.documents')">{{ number_format($documentframework->documents_count) }}</x-data-row>
                        <x-data-row :label="trans('admin/documentframeworks/table.requirements_count')">{{ number_format($documentframework->requirements_count) }}</x-data-row>
                        <x-data-row :label="trans('general.created_by')">{{ $documentframework->adminuser?->display_name }}</x-data-row>
                        <x-data-row :label="trans('general.created_at')">{{ Helper::getFormattedDateObject($documentframework->created_at, 'datetime', false) }}</x-data-row>
                        <x-data-row :label="trans('general.updated_at')">{{ Helper::getFormattedDateObject($documentframework->updated_at, 'datetime', false) }}</x-data-row>
                    </x-page-data>
                </div>
            </x-box>
        </x-page-column>
    </x-container>
@stop

@section('moar_scripts')
    @include ('partials.bootstrap-table')
@stop
