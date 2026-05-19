@extends('layouts/default')

@section('title')
    {{ trans('general.bulk_edit') }} {{ trans('general.document_framework_requirements') }}
    @parent
@stop

@section('header_right')
    <a href="{{ URL::previous() }}" class="btn btn-sm btn-theme pull-right">
        {{ trans('general.back') }}
    </a>
@stop

@section('content')
    @php
        $selectedParentIds = collect(old('parent_ids', $selectedParentIds ?? []))
            ->filter(fn ($parentId) => filled($parentId))
            ->map(fn ($parentId) => (int) $parentId)
            ->all();
    @endphp

    <div class="row">
        <div class="col-md-9 col-md-offset-1">
            <form class="form-horizontal" method="post" action="{{ route('documentframeworkrequirements.bulk.update') }}" autocomplete="off" role="form">
                @csrf

                @foreach ($requirements as $requirement)
                    <input type="hidden" name="ids[]" value="{{ $requirement->id }}">
                @endforeach

                <div class="box box-default">
                    <div class="box-body">
                        <div class="callout callout-warning">
                            <i class="fas fa-exclamation-triangle" aria-hidden="true"></i>
                            {{ trans('general.bulk_edit_about_to') }}
                            {{ trans('general.document_framework_requirements') }}: {{ number_format($requirements->count()) }}
                        </div>

                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>{{ trans('admin/documentframeworkrequirements/table.code') }}</th>
                                    <th>{{ trans('admin/documentframeworkrequirements/table.title') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($requirements as $requirement)
                                    <tr>
                                        <td>{{ $requirement->code }}</td>
                                        <td>{{ $requirement->title }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>

                        <div class="form-group {{ $errors->has('domain') ? ' has-error' : '' }}">
                            <label for="domain" class="col-md-3 control-label">{{ trans('admin/documentframeworkrequirements/table.domain') }}</label>
                            <div class="col-md-5">
                                <input class="form-control" type="text" name="domain" id="domain" value="{{ old('domain') }}">
                                {!! $errors->first('domain', '<span class="alert-msg"><i class="fas fa-times" aria-hidden="true"></i> :message</span>') !!}
                            </div>
                            <div class="col-md-4">
                                <label class="form-control">
                                    <input type="checkbox" name="apply_domain" value="1" @checked(old('apply_domain'))>
                                    {{ trans('general.apply') }}
                                </label>
                            </div>
                        </div>

                        <div class="form-group {{ $errors->has('obligation_type') ? ' has-error' : '' }}">
                            <label for="obligation_type" class="col-md-3 control-label">{{ trans('admin/documentframeworkrequirements/table.obligation_type') }}</label>
                            <div class="col-md-5">
                                <select class="form-control select2" name="obligation_type" id="obligation_type" aria-label="obligation_type">
                                    <option value="">{{ trans('general.none') }}</option>
                                    @foreach ($obligationTypeOptions as $typeValue => $typeLabel)
                                        <option value="{{ $typeValue }}" @selected(old('obligation_type') === $typeValue)>{{ $typeLabel }}</option>
                                    @endforeach
                                </select>
                                {!! $errors->first('obligation_type', '<span class="alert-msg"><i class="fas fa-times" aria-hidden="true"></i> :message</span>') !!}
                            </div>
                            <div class="col-md-4">
                                <label class="form-control">
                                    <input type="checkbox" name="apply_obligation_type" value="1" @checked(old('apply_obligation_type'))>
                                    {{ trans('general.apply') }}
                                </label>
                            </div>
                        </div>

                        <div class="form-group {{ ($errors->has('parent_ids') || $errors->has('parent_ids.*')) ? ' has-error' : '' }}">
                            <label for="parent_ids" class="col-md-3 control-label">{{ trans('admin/documentframeworkrequirements/table.parent') }}</label>
                            <div class="col-md-5">
                                <input type="hidden" name="parent_ids[]" value="">
                                <select class="form-control select2" name="parent_ids[]" id="parent_ids" multiple="multiple" data-placeholder="{{ trans('general.none') }}">
                                    @foreach ($parentOptions as $parentOption)
                                        <option value="{{ $parentOption->id }}" @selected(in_array((int) $parentOption->id, $selectedParentIds, true))>{{ $parentOption->code }} - {{ $parentOption->title }}</option>
                                    @endforeach
                                </select>
                                {!! $errors->first('parent_ids', '<span class="alert-msg"><i class="fas fa-times" aria-hidden="true"></i> :message</span>') !!}
                                {!! $errors->first('parent_ids.*', '<span class="alert-msg"><i class="fas fa-times" aria-hidden="true"></i> :message</span>') !!}
                            </div>
                            <div class="col-md-4">
                                <label class="form-control">
                                    <input type="checkbox" name="apply_parent_ids" value="1" @checked(old('apply_parent_ids'))>
                                    {{ trans('general.apply') }}
                                </label>
                            </div>
                        </div>

                        @include ('partials.forms.edit.user-select', [
                            'translated_name' => trans('admin/documentframeworkrequirements/table.owner'),
                            'fieldname' => 'owner_id',
                            'item' => new \App\Models\DocumentFrameworkRequirement,
                            'required' => 'false',
                            'hide_new' => 'true',
                            'select_id' => 'bulk_owner_id',
                        ])
                        <div class="form-group">
                            <div class="col-md-4 col-md-offset-8">
                                <label class="form-control">
                                    <input type="checkbox" name="apply_owner_id" value="1" @checked(old('apply_owner_id'))>
                                    {{ trans('general.apply') }}
                                </label>
                            </div>
                        </div>

                        @include ('partials.forms.edit.document-type-select', [
                            'translated_name' => trans('admin/documentframeworkrequirements/table.default_document_type'),
                            'fieldname' => 'default_document_type_id',
                            'item' => new \App\Models\DocumentFrameworkRequirement,
                            'required' => 'false',
                            'hide_new' => 'true',
                        ])
                        <div class="form-group">
                            <div class="col-md-4 col-md-offset-8">
                                <label class="form-control">
                                    <input type="checkbox" name="apply_default_document_type_id" value="1" @checked(old('apply_default_document_type_id'))>
                                    {{ trans('general.apply') }}
                                </label>
                            </div>
                        </div>

                        <div class="form-group {{ $errors->has('evidence_type') ? ' has-error' : '' }}">
                            <label for="evidence_type" class="col-md-3 control-label">{{ trans('admin/documentframeworkrequirements/table.evidence_type') }}</label>
                            <div class="col-md-5">
                                <select class="form-control select2" name="evidence_type" id="evidence_type" aria-label="evidence_type">
                                    <option value="">{{ trans('general.none') }}</option>
                                    @foreach ($evidenceTypeOptions as $typeValue => $typeLabel)
                                        <option value="{{ $typeValue }}" @selected(old('evidence_type') === $typeValue)>{{ $typeLabel }}</option>
                                    @endforeach
                                </select>
                                {!! $errors->first('evidence_type', '<span class="alert-msg"><i class="fas fa-times" aria-hidden="true"></i> :message</span>') !!}
                            </div>
                            <div class="col-md-4">
                                <label class="form-control">
                                    <input type="checkbox" name="apply_evidence_type" value="1" @checked(old('apply_evidence_type'))>
                                    {{ trans('general.apply') }}
                                </label>
                            </div>
                        </div>

                        <div class="form-group {{ $errors->has('delegation_level') ? ' has-error' : '' }}">
                            <label for="delegation_level" class="col-md-3 control-label">{{ trans('admin/documentframeworkrequirements/table.delegation_level') }}</label>
                            <div class="col-md-5">
                                <select class="form-control select2" name="delegation_level" id="delegation_level" aria-label="delegation_level">
                                    @foreach ($delegationLevelOptions as $levelValue => $levelLabel)
                                        <option value="{{ $levelValue }}" @selected(old('delegation_level', 'owner_review') === $levelValue)>{{ $levelLabel }}</option>
                                    @endforeach
                                </select>
                                {!! $errors->first('delegation_level', '<span class="alert-msg"><i class="fas fa-times" aria-hidden="true"></i> :message</span>') !!}
                            </div>
                            <div class="col-md-4">
                                <label class="form-control">
                                    <input type="checkbox" name="apply_delegation_level" value="1" @checked(old('apply_delegation_level'))>
                                    {{ trans('general.apply') }}
                                </label>
                            </div>
                        </div>

                        <div class="form-group {{ $errors->has('risk_level') ? ' has-error' : '' }}">
                            <label for="risk_level" class="col-md-3 control-label">{{ trans('admin/documentframeworkrequirements/table.risk_level') }}</label>
                            <div class="col-md-5">
                                @if ($isNis2Framework)
                                    <input class="form-control" type="text" id="risk_level" value="{{ $riskLevelOptions['not_applicable'] ?? trans('general.none') }}" readonly>
                                @else
                                    <select class="form-control select2" name="risk_level" id="risk_level" aria-label="risk_level">
                                        @foreach ($riskLevelOptions as $levelValue => $levelLabel)
                                            <option value="{{ $levelValue }}" @selected(old('risk_level', 'medium') === $levelValue)>{{ $levelLabel }}</option>
                                        @endforeach
                                    </select>
                                @endif
                                {!! $errors->first('risk_level', '<span class="alert-msg"><i class="fas fa-times" aria-hidden="true"></i> :message</span>') !!}
                            </div>
                            <div class="col-md-4">
                                <label class="form-control">
                                    <input type="checkbox" name="apply_risk_level" value="1" @checked(old('apply_risk_level'))>
                                    {{ trans('general.apply') }}
                                </label>
                            </div>
                        </div>

                        <div class="form-group {{ $errors->has('review_frequency_months') ? ' has-error' : '' }}">
                            <label for="review_frequency_months" class="col-md-3 control-label">{{ trans('admin/documentframeworkrequirements/table.review_frequency_months') }}</label>
                            <div class="col-md-2">
                                <input class="form-control" type="number" min="1" max="120" name="review_frequency_months" id="review_frequency_months" value="{{ old('review_frequency_months') }}">
                                {!! $errors->first('review_frequency_months', '<span class="alert-msg"><i class="fas fa-times" aria-hidden="true"></i> :message</span>') !!}
                            </div>
                            <div class="col-md-4 col-md-offset-3">
                                <label class="form-control">
                                    <input type="checkbox" name="apply_review_frequency_months" value="1" @checked(old('apply_review_frequency_months'))>
                                    {{ trans('general.apply') }}
                                </label>
                            </div>
                        </div>

                        <div class="form-group {{ $errors->has('minimum_required_documents') ? ' has-error' : '' }}">
                            <label for="minimum_required_documents" class="col-md-3 control-label">{{ trans('admin/documentframeworkrequirements/table.minimum_required_documents') }}</label>
                            <div class="col-md-2">
                                <input class="form-control" type="number" min="0" max="65535" name="minimum_required_documents" id="minimum_required_documents" value="{{ old('minimum_required_documents') }}">
                                {!! $errors->first('minimum_required_documents', '<span class="alert-msg"><i class="fas fa-times" aria-hidden="true"></i> :message</span>') !!}
                                <p class="help-block">{{ trans('admin/documentframeworkrequirements/general.minimum_required_documents_help') }}</p>
                            </div>
                            <div class="col-md-4 col-md-offset-3">
                                <label class="form-control">
                                    <input type="checkbox" name="apply_minimum_required_documents" value="1" @checked(old('apply_minimum_required_documents'))>
                                    {{ trans('general.apply') }}
                                </label>
                            </div>
                        </div>

                        @foreach (['description', 'evidence_guidance', 'applicability_notes'] as $textField)
                            <div class="form-group {{ $errors->has($textField) ? ' has-error' : '' }}">
                                <label for="{{ $textField }}" class="col-md-3 control-label">{{ trans('admin/documentframeworkrequirements/table.'.$textField) }}</label>
                                <div class="col-md-5">
                                    <textarea class="form-control" name="{{ $textField }}" id="{{ $textField }}" rows="3">{{ old($textField) }}</textarea>
                                    {!! $errors->first($textField, '<span class="alert-msg"><i class="fas fa-times" aria-hidden="true"></i> :message</span>') !!}
                                </div>
                                <div class="col-md-4">
                                    <label class="form-control">
                                        <input type="checkbox" name="apply_{{ $textField }}" value="1" @checked(old('apply_'.$textField))>
                                        {{ trans('general.apply') }}
                                    </label>
                                </div>
                            </div>
                        @endforeach

                        <div class="form-group {{ $errors->has('official_reference') ? ' has-error' : '' }}">
                            <label for="official_reference" class="col-md-3 control-label">{{ trans('admin/documentframeworkrequirements/table.official_reference') }}</label>
                            <div class="col-md-5">
                                <input class="form-control" type="text" name="official_reference" id="official_reference" value="{{ old('official_reference') }}">
                                {!! $errors->first('official_reference', '<span class="alert-msg"><i class="fas fa-times" aria-hidden="true"></i> :message</span>') !!}
                            </div>
                            <div class="col-md-4">
                                <label class="form-control">
                                    <input type="checkbox" name="apply_official_reference" value="1" @checked(old('apply_official_reference'))>
                                    {{ trans('general.apply') }}
                                </label>
                            </div>
                        </div>

                        <div class="form-group {{ $errors->has('source_url') ? ' has-error' : '' }}">
                            <label for="source_url" class="col-md-3 control-label">{{ trans('admin/documentframeworkrequirements/table.source_url') }}</label>
                            <div class="col-md-5">
                                <input class="form-control" type="url" name="source_url" id="source_url" value="{{ old('source_url') }}">
                                {!! $errors->first('source_url', '<span class="alert-msg"><i class="fas fa-times" aria-hidden="true"></i> :message</span>') !!}
                            </div>
                            <div class="col-md-4">
                                <label class="form-control">
                                    <input type="checkbox" name="apply_source_url" value="1" @checked(old('apply_source_url'))>
                                    {{ trans('general.apply') }}
                                </label>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="col-md-3 control-label">{{ trans('admin/documentframeworkrequirements/table.is_mandatory') }}</label>
                            <div class="col-md-7">
                                <label class="form-control">
                                    <input type="radio" name="is_mandatory_state" value="" @checked(old('is_mandatory_state', '') === '')>
                                    {{ trans('general.do_not_change') }}
                                </label>
                                <label class="form-control">
                                    <input type="radio" name="is_mandatory_state" value="1" @checked(old('is_mandatory_state') === '1')>
                                    {{ trans('general.yes') }}
                                </label>
                                <label class="form-control">
                                    <input type="radio" name="is_mandatory_state" value="0" @checked(old('is_mandatory_state') === '0')>
                                    {{ trans('general.no') }}
                                </label>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="col-md-3 control-label">{{ trans('admin/documentframeworkrequirements/table.is_active') }}</label>
                            <div class="col-md-7">
                                <label class="form-control">
                                    <input type="radio" name="is_active_state" value="" @checked(old('is_active_state', '') === '')>
                                    {{ trans('general.do_not_change') }}
                                </label>
                                <label class="form-control">
                                    <input type="radio" name="is_active_state" value="1" @checked(old('is_active_state') === '1')>
                                    {{ trans('general.yes') }}
                                </label>
                                <label class="form-control">
                                    <input type="radio" name="is_active_state" value="0" @checked(old('is_active_state') === '0')>
                                    {{ trans('general.no') }}
                                </label>
                            </div>
                        </div>

                        {!! $errors->first('bulk_actions', '<div class="col-md-8 col-md-offset-3"><span class="alert-msg"><i class="fas fa-times" aria-hidden="true"></i> :message</span></div>') !!}
                    </div>

                    <div class="box-footer text-right">
                        <a class="btn btn-link pull-left" href="{{ route('documentframeworks.show', $framework) }}">{{ trans('button.cancel') }}</a>
                        <button type="submit" class="btn btn-success" id="submit-button">
                            <x-icon type="checkmark" />
                            {{ trans('general.save') }}
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
@stop

@section('moar_scripts')
    <script nonce="{{ csrf_token() }}">
        $(function () {
            var bulkApplyFields = {
                domain: 'apply_domain',
                obligation_type: 'apply_obligation_type',
                parent_ids: 'apply_parent_ids',
                owner_id: 'apply_owner_id',
                default_document_type_id: 'apply_default_document_type_id',
                evidence_type: 'apply_evidence_type',
                delegation_level: 'apply_delegation_level',
                risk_level: 'apply_risk_level',
                review_frequency_months: 'apply_review_frequency_months',
                minimum_required_documents: 'apply_minimum_required_documents',
                official_reference: 'apply_official_reference',
                source_url: 'apply_source_url',
                description: 'apply_description',
                evidence_guidance: 'apply_evidence_guidance',
                applicability_notes: 'apply_applicability_notes'
            };

            var markApplyField = function (fieldName) {
                var applyFieldName = bulkApplyFields[fieldName];

                if (applyFieldName) {
                    $('input[name="' + applyFieldName + '"]').prop('checked', true);
                }
            };

            $.each(bulkApplyFields, function (fieldName) {
                var selector = fieldName === 'parent_ids'
                    ? 'select[name="parent_ids[]"]'
                    : '[name="' + fieldName + '"]';

                $(selector).on('input change select2:select select2:unselect select2:clear', function () {
                    markApplyField(fieldName);
                });
            });
        });
    </script>
@stop
