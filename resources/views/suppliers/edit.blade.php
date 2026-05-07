@extends('layouts/edit-form', [
    'createText' => trans('admin/suppliers/table.create') ,
    'updateText' => trans('admin/suppliers/table.update'),
    'helpTitle' => trans('admin/suppliers/table.about_suppliers_title'),
    'helpText' => trans('admin/suppliers/table.about_suppliers_text'),
    'formAction' => (isset($item->id)) ? route('suppliers.update', ['supplier' => $item->id]) : route('suppliers.store'),
])


{{-- Page content --}}
@section('inputFields')

@include ('partials.forms.edit.name', ['translated_name' => trans('admin/suppliers/table.name')])
@include ('partials.forms.edit.company-select', ['translated_name' => trans('general.company'), 'fieldname' => 'company_id', 'item' => $item])
@include ('partials.forms.edit.template-visibility-select', ['translated_name' => trans('general.template_visibility.label'), 'fieldname' => 'visibility_type', 'item' => $item])
@include ('partials.forms.edit.address')

<div class="form-group {{ $errors->has('contact') ? ' has-error' : '' }}">
    <label for="contact" class="col-md-3 control-label">{{ trans('admin/suppliers/table.contact') }}</label>
    <div class="col-md-7">
        <input class="form-control" name="contact" type="text" id="contact" value="{{ old('contact', $item->contact) }}">
        {!! $errors->first('contact', '<span class="alert-msg" aria-hidden="true"><i class="fas fa-times" aria-hidden="true"></i> :message</span>') !!}
    </div>
</div>

@include ('partials.forms.edit.phone')
@include ('partials.forms.edit.fax')
@include ('partials.forms.edit.email')

<div class="form-group {{ $errors->has('url') ? ' has-error' : '' }}">
    <label for="url" class="col-md-3 control-label">{{ trans('general.url') }}</label>
    <div class="col-md-7">
        <input class="form-control" name="url" type="url" id="url" value="{{ old('url', $item->url) }}">
        {!! $errors->first('url', '<span class="alert-msg" aria-hidden="true"><i class="fas fa-times" aria-hidden="true"></i> :message</span>') !!}
    </div>
</div>

<fieldset name="nis-supplier">
    <x-form.legend>{{ trans('admin/suppliers/table.nis_section') }}</x-form.legend>

    <div class="form-group">
        <div class="col-md-7 col-md-offset-3">
            <label class="form-control">
                <input type="hidden" name="nis_relevant" value="0">
                <input type="checkbox" value="1" name="nis_relevant" {{ old('nis_relevant', $item->nis_relevant) ? ' checked="checked"' : '' }} aria-label="nis_relevant">
                {{ trans('admin/suppliers/table.nis_relevant') }}
            </label>
        </div>
    </div>

    <div class="form-group {{ $errors->has('nis_criticality') ? ' has-error' : '' }}">
        <label for="nis_criticality" class="col-md-3 control-label">{{ trans('admin/suppliers/table.nis_criticality') }}</label>
        <div class="col-md-4">
            <select class="form-control select2" name="nis_criticality" id="nis_criticality" aria-label="nis_criticality">
                @foreach (\App\Models\Supplier::nisCriticalityOptions() as $value => $label)
                    <option value="{{ $value }}" @selected(old('nis_criticality', $item->nis_criticality ?: 'not_assessed') === $value)>{{ $label }}</option>
                @endforeach
            </select>
            {!! $errors->first('nis_criticality', '<span class="alert-msg" aria-hidden="true"><i class="fas fa-times" aria-hidden="true"></i> :message</span>') !!}
        </div>
    </div>

    <div class="form-group {{ $errors->has('nis_relevance_type') ? ' has-error' : '' }}">
        <label for="nis_relevance_type" class="col-md-3 control-label">{{ trans('admin/suppliers/table.nis_relevance_type') }}</label>
        <div class="col-md-4">
            <select class="form-control select2" name="nis_relevance_type" id="nis_relevance_type" aria-label="nis_relevance_type">
                @foreach (\App\Models\Supplier::nisRelevanceTypeOptions() as $value => $label)
                    <option value="{{ $value }}" @selected(old('nis_relevance_type', $item->nis_relevance_type ?: 'not_assessed') === $value)>{{ $label }}</option>
                @endforeach
            </select>
            <p class="help-block">{{ trans('admin/suppliers/table.nis_relevance_type_help') }}</p>
            {!! $errors->first('nis_relevance_type', '<span class="alert-msg" aria-hidden="true"><i class="fas fa-times" aria-hidden="true"></i> :message</span>') !!}
        </div>
    </div>

    <div class="form-group {{ $errors->has('nis_assessment_status') ? ' has-error' : '' }}">
        <label for="nis_assessment_status" class="col-md-3 control-label">{{ trans('admin/suppliers/table.nis_assessment_status') }}</label>
        <div class="col-md-4">
            <select class="form-control select2" name="nis_assessment_status" id="nis_assessment_status" aria-label="nis_assessment_status">
                @foreach (\App\Models\Supplier::nisAssessmentStatusOptions() as $value => $label)
                    <option value="{{ $value }}" @selected(old('nis_assessment_status', $item->nis_assessment_status ?: 'not_started') === $value)>{{ $label }}</option>
                @endforeach
            </select>
            {!! $errors->first('nis_assessment_status', '<span class="alert-msg" aria-hidden="true"><i class="fas fa-times" aria-hidden="true"></i> :message</span>') !!}
        </div>
    </div>

    <div class="form-group {{ $errors->has('cpv_codes') ? ' has-error' : '' }}">
        <label for="cpv_codes" class="col-md-3 control-label">{{ trans('admin/suppliers/table.cpv_codes') }}</label>
        <div class="col-md-7">
            <textarea class="form-control" name="cpv_codes" id="cpv_codes" rows="2" placeholder="72000000-5, 72200000-7">{{ old('cpv_codes', $item->cpv_codes) }}</textarea>
            <p class="help-block">{{ trans('admin/suppliers/table.cpv_codes_help') }}</p>
            {!! $errors->first('cpv_codes', '<span class="alert-msg" aria-hidden="true"><i class="fas fa-times" aria-hidden="true"></i> :message</span>') !!}
        </div>
    </div>

    <div class="form-group {{ $errors->has('nis_relevance_criteria') ? ' has-error' : '' }}">
        <label for="nis_relevance_criteria" class="col-md-3 control-label">{{ trans('admin/suppliers/table.nis_relevance_criteria') }}</label>
        <div class="col-md-7">
            <textarea class="form-control" name="nis_relevance_criteria" id="nis_relevance_criteria" rows="3">{{ old('nis_relevance_criteria', $item->nis_relevance_criteria) }}</textarea>
            {!! $errors->first('nis_relevance_criteria', '<span class="alert-msg" aria-hidden="true"><i class="fas fa-times" aria-hidden="true"></i> :message</span>') !!}
        </div>
    </div>

    <div class="form-group {{ $errors->has('nis_last_assessment_at') ? ' has-error' : '' }}">
        <label for="nis_last_assessment_at" class="col-md-3 control-label">{{ trans('admin/suppliers/table.nis_last_assessment_at') }}</label>
        <div class="col-md-4">
            <x-input.datepicker name="nis_last_assessment_at" :value="old('nis_last_assessment_at', optional($item->nis_last_assessment_at)->format('Y-m-d'))" placeholder="{{ trans('general.select_date') }}"/>
            {!! $errors->first('nis_last_assessment_at', '<span class="alert-msg" aria-hidden="true"><i class="fas fa-times" aria-hidden="true"></i> :message</span>') !!}
        </div>
    </div>

    <div class="form-group {{ $errors->has('nis_next_review_at') ? ' has-error' : '' }}">
        <label for="nis_next_review_at" class="col-md-3 control-label">{{ trans('admin/suppliers/table.nis_next_review_at') }}</label>
        <div class="col-md-4">
            <x-input.datepicker name="nis_next_review_at" :value="old('nis_next_review_at', optional($item->nis_next_review_at)->format('Y-m-d'))" placeholder="{{ trans('general.select_date') }}"/>
            {!! $errors->first('nis_next_review_at', '<span class="alert-msg" aria-hidden="true"><i class="fas fa-times" aria-hidden="true"></i> :message</span>') !!}
        </div>
    </div>
</fieldset>

@include ('partials.forms.edit.notes')
@include ('partials.forms.edit.image-upload', ['image_path' => app('suppliers_upload_path')])

<fieldset name="color-preferences">
    <x-form.legend help_text="{{ trans('general.tag_color_help') }}">
        {{ trans('general.tag_color') }}
    </x-form.legend>
    <!--  color -->
    <div class="form-group {{ $errors->has('tag_color') ? 'error' : '' }}">
        <label for="tag_color" class="col-md-3 control-label">
            {{ trans('general.tag_color') }}
        </label>
        <div class="col-md-9">
            <x-input.colorpicker :item="$item" id="color" :value="old('color', ($item->color ?? '#f4f4f4'))" name="tag_color" id="tag_color" />
            {!! $errors->first('tag_color', '<span class="alert-msg" aria-hidden="true">:message</span>') !!}
        </div>
    </div>
</fieldset>

@stop
