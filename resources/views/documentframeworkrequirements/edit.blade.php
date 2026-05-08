@extends('layouts/edit-form', [
    'createText' => trans('admin/documentframeworkrequirements/general.create'),
    'updateText' => trans('admin/documentframeworkrequirements/general.update'),
    'formAction' => (isset($item->id))
        ? route('documentframeworkrequirements.update', ['documentframeworkrequirement' => $item->id])
        : route('documentframeworkrequirements.store', ['documentframework' => $framework->id]),
    'index_route' => route('documentframeworks.show', $framework),
    'item' => $item,
])

@section('inputFields')
    <input type="hidden" name="document_framework_id" value="{{ $framework->id }}">

    <div class="form-group">
        <label class="col-md-3 control-label">{{ trans('admin/documentframeworkrequirements/table.framework') }}</label>
        <div class="col-md-7">
            <input class="form-control" type="text" readonly value="{{ $framework->name }}">
        </div>
    </div>

    <div class="form-group {{ $errors->has('code') ? ' has-error' : '' }}">
        <label for="code" class="col-md-3 control-label">{{ trans('admin/documentframeworkrequirements/table.code') }}</label>
        <div class="col-md-3">
            <input class="form-control" type="text" name="code" id="code" value="{{ old('code', $item->code) }}" required>
            {!! $errors->first('code', '<span class="alert-msg"><i class="fas fa-times" aria-hidden="true"></i> :message</span>') !!}
        </div>
    </div>

    <div class="form-group {{ $errors->has('title') ? ' has-error' : '' }}">
        <label for="title" class="col-md-3 control-label">{{ trans('admin/documentframeworkrequirements/table.title') }}</label>
        <div class="col-md-7">
            <input class="form-control" type="text" name="title" id="title" value="{{ old('title', $item->title) }}" required>
            {!! $errors->first('title', '<span class="alert-msg"><i class="fas fa-times" aria-hidden="true"></i> :message</span>') !!}
        </div>
    </div>

    <div class="form-group {{ $errors->has('domain') ? ' has-error' : '' }}">
        <label for="domain" class="col-md-3 control-label">{{ trans('admin/documentframeworkrequirements/table.domain') }}</label>
        <div class="col-md-4">
            <input class="form-control" type="text" name="domain" id="domain" value="{{ old('domain', $item->domain) }}">
            {!! $errors->first('domain', '<span class="alert-msg"><i class="fas fa-times" aria-hidden="true"></i> :message</span>') !!}
        </div>
    </div>

    <div class="form-group {{ $errors->has('obligation_type') ? ' has-error' : '' }}">
        <label for="obligation_type" class="col-md-3 control-label">{{ trans('admin/documentframeworkrequirements/table.obligation_type') }}</label>
        <div class="col-md-4">
            <select class="form-control select2" name="obligation_type" id="obligation_type" aria-label="obligation_type">
                <option value="">{{ trans('general.none') }}</option>
                @foreach ($obligationTypeOptions as $typeValue => $typeLabel)
                    <option value="{{ $typeValue }}" @selected(old('obligation_type', $item->obligation_type) === $typeValue)>{{ $typeLabel }}</option>
                @endforeach
            </select>
            {!! $errors->first('obligation_type', '<span class="alert-msg"><i class="fas fa-times" aria-hidden="true"></i> :message</span>') !!}
        </div>
    </div>

    <div class="form-group {{ $errors->has('parent_id') ? ' has-error' : '' }}">
        <label for="parent_id" class="col-md-3 control-label">{{ trans('admin/documentframeworkrequirements/table.parent') }}</label>
        <div class="col-md-5">
            <select class="form-control select2" name="parent_id" id="parent_id">
                <option value="">{{ trans('general.none') }}</option>
                @foreach ($parentOptions as $parentOption)
                    <option value="{{ $parentOption->id }}" @selected(old('parent_id', $item->parent_id) == $parentOption->id)>{{ $parentOption->code }} - {{ $parentOption->title }}</option>
                @endforeach
            </select>
            {!! $errors->first('parent_id', '<span class="alert-msg"><i class="fas fa-times" aria-hidden="true"></i> :message</span>') !!}
        </div>
    </div>

    @include ('partials.forms.edit.user-select', ['translated_name' => trans('admin/documentframeworkrequirements/table.owner'), 'fieldname' => 'owner_id', 'item' => $item, 'required' => 'false'])
    @include ('partials.forms.edit.document-type-select', ['translated_name' => trans('admin/documentframeworkrequirements/table.default_document_type'), 'fieldname' => 'default_document_type_id', 'item' => $item, 'required' => 'false', 'hide_new' => 'true'])

    <div class="form-group {{ $errors->has('evidence_type') ? ' has-error' : '' }}">
        <label for="evidence_type" class="col-md-3 control-label">{{ trans('admin/documentframeworkrequirements/table.evidence_type') }}</label>
        <div class="col-md-4">
            <select class="form-control select2" name="evidence_type" id="evidence_type" aria-label="evidence_type">
                <option value="">{{ trans('general.none') }}</option>
                @foreach ($evidenceTypeOptions as $typeValue => $typeLabel)
                    <option value="{{ $typeValue }}" @selected(old('evidence_type', $item->evidence_type) === $typeValue)>{{ $typeLabel }}</option>
                @endforeach
            </select>
            {!! $errors->first('evidence_type', '<span class="alert-msg"><i class="fas fa-times" aria-hidden="true"></i> :message</span>') !!}
        </div>
    </div>

    <div class="form-group {{ $errors->has('delegation_level') ? ' has-error' : '' }}">
        <label for="delegation_level" class="col-md-3 control-label">{{ trans('admin/documentframeworkrequirements/table.delegation_level') }}</label>
        <div class="col-md-4">
            <select class="form-control select2" name="delegation_level" id="delegation_level" aria-label="delegation_level">
                @foreach ($delegationLevelOptions as $levelValue => $levelLabel)
                    <option value="{{ $levelValue }}" @selected(old('delegation_level', $item->delegation_level ?: 'owner_review') === $levelValue)>{{ $levelLabel }}</option>
                @endforeach
            </select>
            {!! $errors->first('delegation_level', '<span class="alert-msg"><i class="fas fa-times" aria-hidden="true"></i> :message</span>') !!}
        </div>
    </div>

    <div class="form-group {{ $errors->has('risk_level') ? ' has-error' : '' }}">
        <label for="risk_level" class="col-md-3 control-label">{{ trans('admin/documentframeworkrequirements/table.risk_level') }}</label>
        <div class="col-md-4">
            @if ($framework->compliance_domain === 'nis2')
                <input type="hidden" name="risk_level" value="not_applicable">
                <input class="form-control" type="text" id="risk_level" value="{{ $riskLevelOptions['not_applicable'] ?? trans('general.none') }}" readonly>
            @else
                <select class="form-control select2" name="risk_level" id="risk_level" aria-label="risk_level">
                    @foreach ($riskLevelOptions as $levelValue => $levelLabel)
                        <option value="{{ $levelValue }}" @selected(old('risk_level', $item->risk_level ?: 'medium') === $levelValue)>{{ $levelLabel }}</option>
                    @endforeach
                </select>
            @endif
            {!! $errors->first('risk_level', '<span class="alert-msg"><i class="fas fa-times" aria-hidden="true"></i> :message</span>') !!}
        </div>
    </div>

    <div class="form-group {{ $errors->has('review_frequency_months') ? ' has-error' : '' }}">
        <label for="review_frequency_months" class="col-md-3 control-label">{{ trans('admin/documentframeworkrequirements/table.review_frequency_months') }}</label>
        <div class="col-md-2">
            <input class="form-control" type="number" min="1" max="120" name="review_frequency_months" id="review_frequency_months" value="{{ old('review_frequency_months', $item->review_frequency_months) }}">
            {!! $errors->first('review_frequency_months', '<span class="alert-msg"><i class="fas fa-times" aria-hidden="true"></i> :message</span>') !!}
        </div>
    </div>

    <div class="form-group {{ $errors->has('sort_order') ? ' has-error' : '' }}">
        <label for="sort_order" class="col-md-3 control-label">{{ trans('admin/documentframeworkrequirements/table.sort_order') }}</label>
        <div class="col-md-2">
            <input class="form-control" type="number" min="0" name="sort_order" id="sort_order" value="{{ old('sort_order', $item->sort_order ?? 0) }}">
            {!! $errors->first('sort_order', '<span class="alert-msg"><i class="fas fa-times" aria-hidden="true"></i> :message</span>') !!}
        </div>
    </div>

    <div class="form-group {{ $errors->has('description') ? ' has-error' : '' }}">
        <label for="description" class="col-md-3 control-label">{{ trans('admin/documentframeworkrequirements/table.description') }}</label>
        <div class="col-md-8">
            <textarea class="form-control" name="description" id="description" rows="4">{{ old('description', $item->description) }}</textarea>
        </div>
    </div>

    <div class="form-group {{ $errors->has('evidence_guidance') ? ' has-error' : '' }}">
        <label for="evidence_guidance" class="col-md-3 control-label">{{ trans('admin/documentframeworkrequirements/table.evidence_guidance') }}</label>
        <div class="col-md-8">
            <textarea class="form-control" name="evidence_guidance" id="evidence_guidance" rows="4">{{ old('evidence_guidance', $item->evidence_guidance) }}</textarea>
        </div>
    </div>

    <div class="form-group {{ $errors->has('applicability_notes') ? ' has-error' : '' }}">
        <label for="applicability_notes" class="col-md-3 control-label">{{ trans('admin/documentframeworkrequirements/table.applicability_notes') }}</label>
        <div class="col-md-8">
            <textarea class="form-control" name="applicability_notes" id="applicability_notes" rows="4">{{ old('applicability_notes', $item->applicability_notes) }}</textarea>
        </div>
    </div>

    <div class="form-group {{ $errors->has('official_reference') ? ' has-error' : '' }}">
        <label for="official_reference" class="col-md-3 control-label">{{ trans('admin/documentframeworkrequirements/table.official_reference') }}</label>
        <div class="col-md-7">
            <input class="form-control" type="text" name="official_reference" id="official_reference" value="{{ old('official_reference', $item->official_reference) }}">
            {!! $errors->first('official_reference', '<span class="alert-msg"><i class="fas fa-times" aria-hidden="true"></i> :message</span>') !!}
        </div>
    </div>

    <div class="form-group {{ $errors->has('source_url') ? ' has-error' : '' }}">
        <label for="source_url" class="col-md-3 control-label">{{ trans('admin/documentframeworkrequirements/table.source_url') }}</label>
        <div class="col-md-7">
            <input class="form-control" type="url" name="source_url" id="source_url" value="{{ old('source_url', $item->source_url) }}">
            {!! $errors->first('source_url', '<span class="alert-msg"><i class="fas fa-times" aria-hidden="true"></i> :message</span>') !!}
        </div>
    </div>

    <div class="form-group">
        <div class="col-md-8 col-md-offset-3">
            <label class="form-control" style="margin-bottom: 8px;">
                <input type="hidden" name="is_mandatory" value="0">
                <input type="checkbox" name="is_mandatory" value="1" {{ old('is_mandatory', $item->is_mandatory ?? true) ? ' checked="checked"' : '' }}>
                {{ trans('admin/documentframeworkrequirements/table.is_mandatory') }}
            </label>
            <label class="form-control">
                <input type="hidden" name="is_active" value="0">
                <input type="checkbox" name="is_active" value="1" {{ old('is_active', $item->is_active ?? true) ? ' checked="checked"' : '' }}>
                {{ trans('admin/documentframeworkrequirements/table.is_active') }}
            </label>
        </div>
    </div>
@stop
