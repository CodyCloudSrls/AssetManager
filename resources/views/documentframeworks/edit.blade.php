@extends('layouts/edit-form', [
    'createText' => trans('admin/documentframeworks/general.create'),
    'updateText' => trans('admin/documentframeworks/general.update'),
    'formAction' => (isset($item->id)) ? route('documentframeworks.update', ['documentframework' => $item->id]) : route('documentframeworks.store'),
    'index_route' => 'documentframeworks.index',
    'item' => $item,
])

@section('inputFields')
    @include('partials.forms.edit.name', ['translated_name' => trans('admin/documentframeworks/table.name'), 'item' => $item])
    @include('partials.forms.edit.company-select', ['translated_name' => trans('general.company'), 'fieldname' => 'company_id', 'item' => $item])
    @include('partials.forms.edit.template-visibility-select', ['translated_name' => trans('general.template_visibility.label'), 'fieldname' => 'visibility_type', 'item' => $item])

    <div class="col-md-12 col-sm-12">
        <fieldset name="document-framework-metadata">
            <x-form.legend>{{ trans('admin/documentframeworks/general.metadata_section') }}</x-form.legend>

            <div class="form-group {{ $errors->has('framework_code') ? ' has-error' : '' }}">
                <label for="framework_code" class="col-md-3 control-label">{{ trans('admin/documentframeworks/table.framework_code') }}</label>
                <div class="col-md-3">
                    <input class="form-control" type="text" name="framework_code" id="framework_code" value="{{ old('framework_code', $item->framework_code) }}" placeholder="GDPR">
                    {!! $errors->first('framework_code', '<span class="alert-msg"><i class="fas fa-times" aria-hidden="true"></i> :message</span>') !!}
                </div>
            </div>

            <div class="form-group {{ $errors->has('authority_name') ? ' has-error' : '' }}">
                <label for="authority_name" class="col-md-3 control-label">{{ trans('admin/documentframeworks/table.authority_name') }}</label>
                <div class="col-md-6">
                    <input class="form-control" type="text" name="authority_name" id="authority_name" value="{{ old('authority_name', $item->authority_name) }}" placeholder="European Union">
                    {!! $errors->first('authority_name', '<span class="alert-msg"><i class="fas fa-times" aria-hidden="true"></i> :message</span>') !!}
                </div>
            </div>

            <div class="form-group {{ $errors->has('framework_type') ? ' has-error' : '' }}">
                <label for="framework_type" class="col-md-3 control-label">{{ trans('admin/documentframeworks/table.framework_type') }}</label>
                <div class="col-md-4">
                    <select class="form-control select2" name="framework_type" id="framework_type" aria-label="framework_type">
                        <option value="">{{ trans('general.none') }}</option>
                        @foreach ($frameworkTypeOptions as $typeValue => $typeLabel)
                            <option value="{{ $typeValue }}" @selected(old('framework_type', $item->framework_type) === $typeValue)>{{ $typeLabel }}</option>
                        @endforeach
                    </select>
                    {!! $errors->first('framework_type', '<span class="alert-msg"><i class="fas fa-times" aria-hidden="true"></i> :message</span>') !!}
                </div>
            </div>

            <div class="form-group {{ $errors->has('jurisdiction') ? ' has-error' : '' }}">
                <label for="jurisdiction" class="col-md-3 control-label">{{ trans('admin/documentframeworks/table.jurisdiction') }}</label>
                <div class="col-md-4">
                    <input class="form-control" type="text" name="jurisdiction" id="jurisdiction" value="{{ old('jurisdiction', $item->jurisdiction) }}" placeholder="EU / Italy">
                    {!! $errors->first('jurisdiction', '<span class="alert-msg"><i class="fas fa-times" aria-hidden="true"></i> :message</span>') !!}
                </div>
            </div>

            <div class="form-group {{ $errors->has('version') ? ' has-error' : '' }}">
                <label for="version" class="col-md-3 control-label">{{ trans('admin/documentframeworks/table.version') }}</label>
                <div class="col-md-3">
                    <input class="form-control" type="text" name="version" id="version" value="{{ old('version', $item->version) }}" placeholder="2024">
                    {!! $errors->first('version', '<span class="alert-msg"><i class="fas fa-times" aria-hidden="true"></i> :message</span>') !!}
                </div>
            </div>

            <div class="form-group {{ $errors->has('slug') ? ' has-error' : '' }}">
                <label for="slug" class="col-md-3 control-label">{{ trans('admin/documentframeworks/table.slug') }}</label>
                <div class="col-md-4">
                    <input class="form-control" type="text" name="slug" id="slug" value="{{ old('slug', $item->slug) }}" placeholder="gdpr">
                    {!! $errors->first('slug', '<span class="alert-msg"><i class="fas fa-times" aria-hidden="true"></i> :message</span>') !!}
                </div>
            </div>
        </fieldset>
    </div>

    <div class="col-md-12 col-sm-12">
        <fieldset name="document-framework-governance">
            <x-form.legend>{{ trans('admin/documentframeworks/general.governance_section') }}</x-form.legend>

            @include ('partials.forms.edit.user-select', ['translated_name' => trans('admin/documentframeworks/table.owner'), 'fieldname' => 'owner_id', 'item' => $item, 'required' => 'false', 'hide_new' => 'true'])

            <div class="form-group {{ $errors->has('status') ? ' has-error' : '' }}">
                <label for="status" class="col-md-3 control-label">{{ trans('admin/documentframeworks/table.status') }}</label>
                <div class="col-md-4">
                    <select class="form-control select2" name="status" id="status" aria-label="status">
                        @foreach ($statusOptions as $statusValue => $statusLabel)
                            <option value="{{ $statusValue }}" @selected(old('status', $item->status ?: 'active') === $statusValue)>{{ $statusLabel }}</option>
                        @endforeach
                    </select>
                    {!! $errors->first('status', '<span class="alert-msg"><i class="fas fa-times" aria-hidden="true"></i> :message</span>') !!}
                </div>
            </div>

            <div class="form-group {{ $errors->has('review_cadence_months') ? ' has-error' : '' }}">
                <label for="review_cadence_months" class="col-md-3 control-label">{{ trans('admin/documentframeworks/table.review_cadence_months') }}</label>
                <div class="col-md-2">
                    <input class="form-control" type="number" min="1" max="120" name="review_cadence_months" id="review_cadence_months" value="{{ old('review_cadence_months', $item->review_cadence_months) }}">
                    {!! $errors->first('review_cadence_months', '<span class="alert-msg"><i class="fas fa-times" aria-hidden="true"></i> :message</span>') !!}
                </div>
            </div>

            <div class="form-group {{ $errors->has('effective_from') ? ' has-error' : '' }}">
                <label for="effective_from" class="col-md-3 control-label">{{ trans('admin/documentframeworks/table.effective_from') }}</label>
                <div class="col-md-4">
                    <x-input.datepicker name="effective_from" :value="old('effective_from', optional($item->effective_from)->format('Y-m-d'))" placeholder="{{ trans('general.select_date') }}"/>
                    {!! $errors->first('effective_from', '<span class="alert-msg"><i class="fas fa-times" aria-hidden="true"></i> :message</span>') !!}
                </div>
            </div>

            <div class="form-group {{ $errors->has('effective_to') ? ' has-error' : '' }}">
                <label for="effective_to" class="col-md-3 control-label">{{ trans('admin/documentframeworks/table.effective_to') }}</label>
                <div class="col-md-4">
                    <x-input.datepicker name="effective_to" :value="old('effective_to', optional($item->effective_to)->format('Y-m-d'))" placeholder="{{ trans('general.select_date') }}"/>
                    {!! $errors->first('effective_to', '<span class="alert-msg"><i class="fas fa-times" aria-hidden="true"></i> :message</span>') !!}
                </div>
            </div>

            <div class="form-group {{ $errors->has('external_reference_url') ? ' has-error' : '' }}">
                <label for="external_reference_url" class="col-md-3 control-label">{{ trans('admin/documentframeworks/table.external_reference_url') }}</label>
                <div class="col-md-7">
                    <input class="form-control" type="url" name="external_reference_url" id="external_reference_url" value="{{ old('external_reference_url', $item->external_reference_url) }}" placeholder="https://eur-lex.europa.eu/...">
                    {!! $errors->first('external_reference_url', '<span class="alert-msg"><i class="fas fa-times" aria-hidden="true"></i> :message</span>') !!}
                </div>
            </div>

            <div class="form-group {{ $errors->has('description') ? ' has-error' : '' }}">
                <label for="description" class="col-md-3 control-label">{{ trans('admin/documentframeworks/table.description') }}</label>
                <div class="col-md-9">
                    <textarea class="form-control" name="description" id="description" rows="4">{{ old('description', $item->description) }}</textarea>
                    {!! $errors->first('description', '<span class="alert-msg"><i class="fas fa-times" aria-hidden="true"></i> :message</span>') !!}
                </div>
            </div>

            <div class="form-group {{ $errors->has('sort_order') ? ' has-error' : '' }}">
                <label for="sort_order" class="col-md-3 control-label">{{ trans('admin/documentframeworks/table.sort_order') }}</label>
                <div class="col-md-2">
                    <input class="form-control" type="number" min="0" name="sort_order" id="sort_order" value="{{ old('sort_order', $item->sort_order ?? 0) }}">
                    {!! $errors->first('sort_order', '<span class="alert-msg"><i class="fas fa-times" aria-hidden="true"></i> :message</span>') !!}
                </div>
            </div>

            <div class="form-group">
                <div class="col-md-9 col-md-offset-3">
                    <input type="hidden" name="is_active" value="0">
                    <label class="form-control">
                        <input type="checkbox" value="1" name="is_active" {{ old('is_active', $item->is_active ?? true) ? ' checked="checked"' : '' }} aria-label="is_active">
                        {{ trans('admin/documentframeworks/table.is_active') }}
                    </label>
                </div>
            </div>
        </fieldset>
    </div>
@stop
