@extends('layouts/default')

@section('title')
    {{ trans('admin/documentframeworkrequirements/general.matrix.title') }} - {{ $documentframework->name }}
    @parent
@stop

@section('header_right')
    <a href="{{ route('documentframeworks.show', $documentframework) }}" class="btn btn-default">{{ trans('general.back') }}</a>
@stop

@php
    $documentStatusOptions = \App\Models\Document::getStatusOptions();
    $coverageRoleOptions = \App\Models\Document::coverageRoleOptions();
@endphp

@section('content')
    <style>
        .requirements-matrix-summary {
            display: flex;
            flex-wrap: wrap;
            gap: 18px;
            margin-bottom: 15px;
        }
        .requirements-matrix-summary__item {
            min-width: 110px;
        }
        .requirements-matrix-summary__value {
            font-size: 22px;
            font-weight: 700;
            line-height: 1.1;
        }
        .requirements-matrix-summary__label,
        .requirements-matrix-muted {
            color: var(--color-muted);
            font-size: 12px;
        }
        .requirements-matrix-table > tbody > tr > td {
            vertical-align: top;
        }
        .requirements-matrix-requirement {
            min-width: 260px;
        }
        .requirements-matrix-evidence {
            min-width: 340px;
        }
        .requirements-matrix-evidence-item + .requirements-matrix-evidence-item {
            border-top: 1px solid var(--table-border-row-color);
            margin-top: 8px;
            padding-top: 8px;
        }
        .requirements-matrix-label-line {
            display: flex;
            flex-wrap: wrap;
            gap: 5px;
            margin-top: 4px;
        }
    </style>

    <x-container>
        <x-box>
            <div class="box-header with-border">
                <h2 class="box-title">{{ trans('admin/documentframeworkrequirements/general.matrix.title') }}</h2>
            </div>
            <div class="box-body">
                <p class="requirements-matrix-muted">{{ trans('admin/documentframeworkrequirements/general.matrix.help', ['days' => $reviewWarningDays]) }}</p>

                <div class="requirements-matrix-summary">
                    <div class="requirements-matrix-summary__item">
                        <div class="requirements-matrix-summary__value">{{ number_format($coverageSummary['total']) }}</div>
                        <div class="requirements-matrix-summary__label">{{ trans('admin/documentframeworks/general.coverage.total_requirements') }}</div>
                    </div>
                    <div class="requirements-matrix-summary__item">
                        <div class="requirements-matrix-summary__value">{{ number_format($coverageSummary['covered']) }}</div>
                        <div class="requirements-matrix-summary__label">{{ trans('admin/documentframeworkrequirements/general.coverage.covered') }}</div>
                    </div>
                    <div class="requirements-matrix-summary__item">
                        <div class="requirements-matrix-summary__value">{{ number_format($coverageSummary['at_risk']) }}</div>
                        <div class="requirements-matrix-summary__label">{{ trans('admin/documentframeworkrequirements/general.coverage.at_risk') }}</div>
                    </div>
                    <div class="requirements-matrix-summary__item">
                        <div class="requirements-matrix-summary__value">{{ number_format($coverageSummary['supporting_only']) }}</div>
                        <div class="requirements-matrix-summary__label">{{ trans('admin/documentframeworkrequirements/general.coverage.supporting_only') }}</div>
                    </div>
                    <div class="requirements-matrix-summary__item">
                        <div class="requirements-matrix-summary__value">{{ number_format($coverageSummary['missing']) }}</div>
                        <div class="requirements-matrix-summary__label">{{ trans('admin/documentframeworkrequirements/general.coverage.missing') }}</div>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-striped snipe-table requirements-matrix-table">
                        <thead>
                            <tr>
                                <th>{{ trans('admin/documentframeworkrequirements/table.code') }}</th>
                                <th>{{ trans('admin/documentframeworkrequirements/table.coverage') }}</th>
                                <th>{{ trans('admin/documentframeworkrequirements/table.owner') }}</th>
                                <th>{{ trans('admin/documentframeworkrequirements/table.risk_level') }}</th>
                                <th>{{ trans('admin/documentframeworkrequirements/general.matrix.review') }}</th>
                                <th>{{ trans('admin/documentframeworkrequirements/general.matrix.linked_evidence') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($matrixRows as $row)
                                @php
                                    $requirement = $row['requirement'];
                                    $reviewDocument = $row['review_state']['document'];
                                @endphp
                                <tr>
                                    <td class="requirements-matrix-requirement">
                                        <a href="{{ route('documentframeworkrequirements.show', $requirement) }}"><strong>{{ $requirement->code }}</strong></a>
                                        <div>{{ $requirement->title }}</div>
                                        @if ($requirement->parent_requirement_codes)
                                            <div class="requirements-matrix-muted">
                                                {{ trans('admin/documentframeworkrequirements/table.parent') }}: {{ $requirement->parent_requirement_codes }}
                                            </div>
                                        @endif
                                        @if ($requirement->evidence_guidance)
                                            <div class="requirements-matrix-muted">{{ $requirement->evidence_guidance }}</div>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="{{ $row['coverage_class'] }}">{{ $requirement->coverage_label }}</span>
                                        <div class="requirements-matrix-label-line">
                                            <span class="label label-primary">{{ trans('admin/documentframeworkrequirements/general.matrix.primary_count', ['count' => $row['primary_documents']->count()]) }}</span>
                                            <span class="label label-default">{{ trans('admin/documentframeworkrequirements/general.matrix.supporting_count', ['count' => $row['supporting_documents']->count()]) }}</span>
                                            <span class="label label-default">{{ trans('admin/documentframeworkrequirements/general.matrix.minimum_required_documents', ['count' => $requirement->minimum_required_documents]) }}</span>
                                            @if ($requirement->document_shortfall_count > 0)
                                                <span class="label label-danger">{{ trans('admin/documentframeworkrequirements/general.matrix.missing_documents', ['count' => $requirement->document_shortfall_count]) }}</span>
                                            @endif
                                        </div>
                                    </td>
                                    <td>
                                        @if ($requirement->owner)
                                            {{ $requirement->owner->display_name }}
                                        @else
                                            <span class="requirements-matrix-muted">{{ trans('admin/documentframeworkrequirements/general.matrix.no_owner') }}</span>
                                        @endif
                                        @if ($requirement->defaultDocumentType)
                                            <div class="requirements-matrix-muted">{{ $requirement->defaultDocumentType->name }}</div>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="label label-default">{{ $requirement->risk_level_label }}</span>
                                        @if ($requirement->delegation_level)
                                            <div class="requirements-matrix-muted">{{ $requirement->delegation_level_label }}</div>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="{{ $row['review_state']['class'] }}">{{ $row['review_state']['label'] }}</span>
                                        @if ($reviewDocument)
                                            <div class="requirements-matrix-muted">
                                                {{ $reviewDocument->name }}
                                                @if ($reviewDocument->next_review_at)
                                                    <br>{{ trans('admin/documents/form.next_review_at') }}:
                                                    {{ Helper::getFormattedDateObject($reviewDocument->next_review_at, 'date', false) }}
                                                @endif
                                            </div>
                                        @endif
                                        @if ($requirement->review_frequency_months)
                                            <div class="requirements-matrix-muted">
                                                {{ trans('admin/documentframeworkrequirements/table.review_frequency_months') }}:
                                                {{ trans('admin/documentframeworks/general.months_interval', ['count' => $requirement->review_frequency_months]) }}
                                            </div>
                                        @endif
                                    </td>
                                    <td class="requirements-matrix-evidence">
                                        @forelse ($row['documents'] as $document)
                                            @php
                                                $role = $document->pivot?->coverage_role;
                                                $statusClass = match ($document->status) {
                                                    \App\Models\Document::STATUS_ACTIVE => 'label label-success',
                                                    \App\Models\Document::STATUS_IN_REVIEW => 'label label-warning',
                                                    \App\Models\Document::STATUS_DRAFT => 'label label-default',
                                                    default => 'label label-danger',
                                                };
                                            @endphp
                                            <div class="requirements-matrix-evidence-item">
                                                @can('view', $document)
                                                    <a href="{{ route('documents.show', $document) }}">{{ $document->name }}</a>
                                                @else
                                                    <span class="requirements-matrix-muted">{{ trans('general.insufficient_permissions') }}</span>
                                                @endcan
                                                <div class="requirements-matrix-label-line">
                                                    <span class="label label-info">{{ $coverageRoleOptions[$role] ?? $role }}</span>
                                                    <span class="{{ $statusClass }}">{{ $documentStatusOptions[$document->status] ?? $document->status }}</span>
                                                </div>
                                                <div class="requirements-matrix-muted">
                                                    @if ($document->version)
                                                        {{ trans('admin/documents/form.version') }}: {{ $document->version }}
                                                    @endif
                                                    @if ($document->next_review_at)
                                                        {{ $document->version ? ' | ' : '' }}{{ trans('admin/documents/form.next_review_at') }}:
                                                        {{ Helper::getFormattedDateObject($document->next_review_at, 'date', false) }}
                                                    @endif
                                                    @if ($document->pivot?->covered_at)
                                                        {{ ($document->version || $document->next_review_at) ? ' | ' : '' }}{{ trans('admin/documentframeworkrequirements/general.matrix.covered_at') }}:
                                                        {{ Helper::getFormattedDateObject($document->pivot->covered_at, 'date', false) }}
                                                    @endif
                                                </div>
                                                @if ($document->pivot?->notes)
                                                    <div class="requirements-matrix-muted">{{ $document->pivot->notes }}</div>
                                                @endif
                                            </div>
                                        @empty
                                            <span class="requirements-matrix-muted">{{ trans('admin/documentframeworkrequirements/general.matrix.no_documents') }}</span>
                                        @endforelse
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6">{{ trans('admin/documentframeworkrequirements/general.matrix.no_requirements') }}</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </x-box>
    </x-container>
@stop
