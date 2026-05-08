@extends('layouts/default')

@section('title')
    {{ trans('admin/documents/general.delegated_evidence_requests') }}
    @parent
@stop

@section('content')
    @php
        $apiFilters = array_filter(request()->only(['tenant_id', 'company_id', 'target_type', 'status', 'relation_type', 'review_status']), fn ($value) => ! is_null($value) && $value !== '');
        $apiFilters['delegated_evidence'] = 1;
    @endphp

    <x-container>
        <x-box>
            <div class="row" style="margin-bottom: 15px;">
                <div class="col-md-12">
                    <p class="help-block" style="margin-top: 0;">{{ trans('admin/documents/general.delegated_evidence_requests_help') }}</p>
                    <form method="get" action="{{ route('documents.evidence_requests.index') }}" class="form-inline" role="search">
                        @foreach (request()->only(['tenant_id', 'company_id']) as $filterName => $filterValue)
                            <input type="hidden" name="{{ $filterName }}" value="{{ $filterValue }}">
                        @endforeach

                        <div class="form-group">
                            <label for="evidence_request_target_type" class="sr-only">{{ trans('admin/documents/form.assignable_type') }}</label>
                            <select class="form-control select2" name="target_type" id="evidence_request_target_type" aria-label="{{ trans('admin/documents/form.assignable_type') }}" style="min-width: 180px;">
                                <option value="">{{ trans('admin/documents/general.all_delegated_targets') }}</option>
                                <option value="{{ \App\Models\DocumentAssignment::ASSIGNABLE_USER }}" @selected(request('target_type') === \App\Models\DocumentAssignment::ASSIGNABLE_USER)>{{ trans('general.user') }}</option>
                                <option value="{{ \App\Models\DocumentAssignment::ASSIGNABLE_SUPPLIER }}" @selected(request('target_type') === \App\Models\DocumentAssignment::ASSIGNABLE_SUPPLIER)>{{ trans('general.supplier') }}</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="evidence_request_relation_type" class="sr-only">{{ trans('admin/documents/form.assignment_relation') }}</label>
                            <select class="form-control select2" name="relation_type" id="evidence_request_relation_type" aria-label="{{ trans('admin/documents/form.assignment_relation') }}" style="min-width: 190px;">
                                <option value="">{{ trans('admin/documents/general.all_evidence_relations') }}</option>
                                @foreach ([\App\Models\DocumentAssignment::RELATION_REQUIRED_FOR, \App\Models\DocumentAssignment::RELATION_EVIDENCE_FOR] as $relationType)
                                    <option value="{{ $relationType }}" @selected(request('relation_type') === $relationType)>{{ \App\Models\DocumentAssignment::relationTypeOptions()[$relationType] }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="evidence_request_status" class="sr-only">{{ trans('admin/documents/form.assignment_status') }}</label>
                            <select class="form-control select2" name="status" id="evidence_request_status" aria-label="{{ trans('admin/documents/form.assignment_status') }}" style="min-width: 190px;">
                                <option value="">{{ trans('admin/documents/general.open_evidence_requests') }}</option>
                                @foreach (\App\Models\DocumentAssignment::statusOptions() as $statusValue => $statusLabel)
                                    <option value="{{ $statusValue }}" @selected(request('status') === $statusValue)>{{ $statusLabel }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="evidence_request_review_status" class="sr-only">{{ trans('admin/documents/general.review_due') }}</label>
                            <select class="form-control select2" name="review_status" id="evidence_request_review_status" aria-label="{{ trans('admin/documents/general.review_due') }}" style="min-width: 190px;">
                                <option value="">{{ trans('admin/documents/general.all_review_statuses') }}</option>
                                <option value="due" @selected(request('review_status') === 'due')>{{ trans('admin/documents/general.assignment_expiring_flag') }}</option>
                                <option value="overdue" @selected(request('review_status') === 'overdue')>{{ trans('admin/documents/general.assignment_expired_flag') }}</option>
                            </select>
                        </div>

                        <button type="submit" class="btn btn-primary">
                            <x-icon type="search" />
                            {{ trans('admin/documents/form.apply_filters') }}
                        </button>

                        <a href="{{ route('documents.evidence_requests.index', request()->only(['tenant_id', 'company_id'])) }}" class="btn btn-default">
                            {{ trans('admin/documents/form.clear_filters') }}
                        </a>
                    </form>
                </div>
            </div>

            <x-table
                name="delegatedEvidenceRequests"
                :presenter="\App\Presenters\DocumentAssignmentPresenter::dataTableLayout()"
                fixed_right_number="1"
                show_column_search="true"
                show_advanced_search="true"
                api_url="{{ route('api.documentassignments.index', $apiFilters) }}"
                export_filename="export-delegated-evidence-requests-{{ date('Y-m-d') }}"
            />
        </x-box>
    </x-container>
@stop

@section('moar_scripts')
    @include('partials.bootstrap-table')
@stop
