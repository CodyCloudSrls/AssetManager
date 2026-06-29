@extends('layouts/edit-form', [
    'createText' => trans('admin/customers/table.create'),
    'updateText' => trans('admin/customers/table.update'),
    'helpTitle' => trans('admin/customers/table.about_customers_title'),
    'helpText' => trans('admin/customers/table.about_customers_text'),
    'formAction' => isset($item->id) ? route('customers.update', ['customer' => $item->id]) : route('customers.store'),
])

@section('inputFields')

@include('partials.forms.edit.name', ['translated_name' => trans('admin/customers/table.name')])
@include('partials.forms.edit.company-select', ['translated_name' => trans('general.company'), 'fieldname' => 'company_id', 'item' => $item])

<div class="form-group {{ $errors->has('customer_number') ? ' has-error' : '' }}">
    <label for="customer_number" class="col-md-3 control-label">{{ trans('admin/customers/table.customer_number') }}</label>
    <div class="col-md-4">
        <input class="form-control" name="customer_number" type="text" id="customer_number" value="{{ old('customer_number', $item->customer_number) }}">
        {!! $errors->first('customer_number', '<span class="alert-msg" aria-hidden="true"><i class="fas fa-times" aria-hidden="true"></i> :message</span>') !!}
    </div>
</div>

<div class="form-group {{ $errors->has('status') ? ' has-error' : '' }}">
    <label for="status" class="col-md-3 control-label">{{ trans('general.status') }}</label>
    <div class="col-md-4">
        <select class="form-control select2" name="status" id="status" aria-label="status">
            @foreach (\App\Models\Customer::statusOptions() as $value => $label)
                <option value="{{ $value }}" @selected(old('status', $item->status ?: \App\Models\Customer::STATUS_ACTIVE) === $value)>{{ $label }}</option>
            @endforeach
        </select>
        {!! $errors->first('status', '<span class="alert-msg" aria-hidden="true"><i class="fas fa-times" aria-hidden="true"></i> :message</span>') !!}
    </div>
</div>

<div class="form-group {{ $errors->has('vat_number') ? ' has-error' : '' }}">
    <label for="vat_number" class="col-md-3 control-label">{{ trans('admin/customers/table.vat_number') }}</label>
    <div class="col-md-4">
        <input class="form-control" name="vat_number" type="text" id="vat_number" value="{{ old('vat_number', $item->vat_number) }}">
        {!! $errors->first('vat_number', '<span class="alert-msg" aria-hidden="true"><i class="fas fa-times" aria-hidden="true"></i> :message</span>') !!}
    </div>
</div>

<div class="form-group {{ $errors->has('tax_code') ? ' has-error' : '' }}">
    <label for="tax_code" class="col-md-3 control-label">{{ trans('admin/customers/table.tax_code') }}</label>
    <div class="col-md-4">
        <input class="form-control" name="tax_code" type="text" id="tax_code" value="{{ old('tax_code', $item->tax_code) }}">
        {!! $errors->first('tax_code', '<span class="alert-msg" aria-hidden="true"><i class="fas fa-times" aria-hidden="true"></i> :message</span>') !!}
    </div>
</div>

{{-- Italian electronic invoicing (fatturazione elettronica) recipient details --}}
<div class="form-group {{ $errors->has('sdi_code') ? ' has-error' : '' }}">
    <label for="sdi_code" class="col-md-3 control-label">{{ trans('admin/customers/table.sdi_code') }}</label>
    <div class="col-md-4">
        <input class="form-control" name="sdi_code" type="text" id="sdi_code" maxlength="7" value="{{ old('sdi_code', $item->sdi_code) }}" style="text-transform:uppercase;">
        <p class="help-block">{{ trans('admin/customers/table.sdi_code_help') }}</p>
        {!! $errors->first('sdi_code', '<span class="alert-msg" aria-hidden="true"><i class="fas fa-times" aria-hidden="true"></i> :message</span>') !!}
    </div>
</div>

<div class="form-group {{ $errors->has('pec') ? ' has-error' : '' }}">
    <label for="pec" class="col-md-3 control-label">{{ trans('admin/customers/table.pec') }}</label>
    <div class="col-md-4">
        <input class="form-control" name="pec" type="email" id="pec" value="{{ old('pec', $item->pec) }}">
        <p class="help-block">{{ trans('admin/customers/table.pec_help') }}</p>
        {!! $errors->first('pec', '<span class="alert-msg" aria-hidden="true"><i class="fas fa-times" aria-hidden="true"></i> :message</span>') !!}
    </div>
</div>

@include('partials.forms.edit.address')

<div class="form-group {{ $errors->has('contact') ? ' has-error' : '' }}">
    <label for="contact" class="col-md-3 control-label">{{ trans('admin/customers/table.contact') }}</label>
    <div class="col-md-7">
        <input class="form-control" name="contact" type="text" id="contact" value="{{ old('contact', $item->contact) }}">
        <p class="help-block">{{ trans('admin/customers/table.contact_help') }}</p>
        {!! $errors->first('contact', '<span class="alert-msg" aria-hidden="true"><i class="fas fa-times" aria-hidden="true"></i> :message</span>') !!}
    </div>
</div>

<div class="form-group {{ $errors->has('phone') ? ' has-error' : '' }}">
    <label for="phone" class="col-md-3 control-label">{{ trans('admin/customers/table.phone') }}</label>
    <div class="col-md-7">
        <input class="form-control" name="phone" type="text" id="phone" value="{{ old('phone', $item->phone) }}">
        <p class="help-block">{{ trans('admin/customers/table.phone_help') }}</p>
        {!! $errors->first('phone', '<span class="alert-msg" aria-hidden="true"><i class="fas fa-times" aria-hidden="true"></i> :message</span>') !!}
    </div>
</div>

<div class="form-group {{ $errors->has('email') ? ' has-error' : '' }}">
    <label for="email" class="col-md-3 control-label">{{ trans('admin/customers/table.email') }}</label>
    <div class="col-md-7">
        <input class="form-control" name="email" type="email" id="email" value="{{ old('email', $item->email) }}">
        {!! $errors->first('email', '<span class="alert-msg" aria-hidden="true"><i class="fas fa-times" aria-hidden="true"></i> :message</span>') !!}
    </div>
</div>

<div class="form-group {{ $errors->has('url') ? ' has-error' : '' }}">
    <label for="url" class="col-md-3 control-label">{{ trans('general.url') }}</label>
    <div class="col-md-7">
        <input class="form-control" name="url" type="url" id="url" value="{{ old('url', $item->url) }}">
        {!! $errors->first('url', '<span class="alert-msg" aria-hidden="true"><i class="fas fa-times" aria-hidden="true"></i> :message</span>') !!}
    </div>
</div>

<fieldset name="nis-customer">
    <x-form.legend>{{ trans('admin/customers/table.nis_section') }}</x-form.legend>

    <div class="form-group {{ $errors->has('sector') ? ' has-error' : '' }}">
        <label for="sector" class="col-md-3 control-label">{{ trans('admin/customers/table.sector') }}</label>
        <div class="col-md-7">
            <input class="form-control" name="sector" type="text" id="sector" value="{{ old('sector', $item->sector) }}">
            {!! $errors->first('sector', '<span class="alert-msg" aria-hidden="true"><i class="fas fa-times" aria-hidden="true"></i> :message</span>') !!}
        </div>
    </div>

    <div class="form-group {{ $errors->has('nis_profile') ? ' has-error' : '' }}">
        <label for="nis_profile" class="col-md-3 control-label">{{ trans('admin/customers/table.nis_profile') }}</label>
        <div class="col-md-4">
            <select class="form-control select2" name="nis_profile" id="nis_profile" aria-label="nis_profile">
                @foreach (\App\Models\Customer::nisProfileOptions() as $value => $label)
                    <option value="{{ $value }}" @selected(old('nis_profile', $item->nis_profile ?: \App\Models\Customer::NIS_PROFILE_NOT_ASSESSED) === $value)>{{ $label }}</option>
                @endforeach
            </select>
            {!! $errors->first('nis_profile', '<span class="alert-msg" aria-hidden="true"><i class="fas fa-times" aria-hidden="true"></i> :message</span>') !!}
        </div>
    </div>

    <div class="form-group {{ $errors->has('nis_service_role') ? ' has-error' : '' }}">
        <label for="nis_service_role" class="col-md-3 control-label">{{ trans('admin/customers/table.nis_service_role') }}</label>
        <div class="col-md-4">
            <select class="form-control select2" name="nis_service_role" id="nis_service_role" aria-label="nis_service_role">
                @foreach (\App\Models\Customer::nisServiceRoleOptions() as $value => $label)
                    <option value="{{ $value }}" @selected(old('nis_service_role', $item->nis_service_role ?: \App\Models\Customer::NIS_ROLE_NOT_ASSESSED) === $value)>{{ $label }}</option>
                @endforeach
            </select>
            {!! $errors->first('nis_service_role', '<span class="alert-msg" aria-hidden="true"><i class="fas fa-times" aria-hidden="true"></i> :message</span>') !!}
        </div>
    </div>

    <div class="form-group {{ $errors->has('nis_criticality') ? ' has-error' : '' }}">
        <label for="nis_criticality" class="col-md-3 control-label">{{ trans('admin/customers/table.nis_criticality') }}</label>
        <div class="col-md-4">
            <select class="form-control select2" name="nis_criticality" id="nis_criticality" aria-label="nis_criticality">
                @foreach (\App\Models\Customer::nisCriticalityOptions() as $value => $label)
                    <option value="{{ $value }}" @selected(old('nis_criticality', $item->nis_criticality ?: 'not_assessed') === $value)>{{ $label }}</option>
                @endforeach
            </select>
            {!! $errors->first('nis_criticality', '<span class="alert-msg" aria-hidden="true"><i class="fas fa-times" aria-hidden="true"></i> :message</span>') !!}
        </div>
    </div>

    <div class="form-group {{ $errors->has('security_contact') ? ' has-error' : '' }}">
        <label for="security_contact" class="col-md-3 control-label">{{ trans('admin/customers/table.security_contact') }}</label>
        <div class="col-md-7">
            <input class="form-control" name="security_contact" type="text" id="security_contact" value="{{ old('security_contact', $item->security_contact) }}">
            {!! $errors->first('security_contact', '<span class="alert-msg" aria-hidden="true"><i class="fas fa-times" aria-hidden="true"></i> :message</span>') !!}
        </div>
    </div>

    <div class="form-group {{ $errors->has('security_email') ? ' has-error' : '' }}">
        <label for="security_email" class="col-md-3 control-label">{{ trans('admin/customers/table.security_email') }}</label>
        <div class="col-md-7">
            <input class="form-control" name="security_email" type="email" id="security_email" value="{{ old('security_email', $item->security_email) }}">
            {!! $errors->first('security_email', '<span class="alert-msg" aria-hidden="true"><i class="fas fa-times" aria-hidden="true"></i> :message</span>') !!}
        </div>
    </div>

    @foreach (['nis_obligations', 'incident_notification_terms', 'sla_terms', 'audit_rights'] as $textField)
        <div class="form-group {{ $errors->has($textField) ? ' has-error' : '' }}">
            <label for="{{ $textField }}" class="col-md-3 control-label">{{ trans('admin/customers/table.'.$textField) }}</label>
            <div class="col-md-7">
                <textarea class="form-control" name="{{ $textField }}" id="{{ $textField }}" rows="3">{{ old($textField, $item->{$textField}) }}</textarea>
                {!! $errors->first($textField, '<span class="alert-msg" aria-hidden="true"><i class="fas fa-times" aria-hidden="true"></i> :message</span>') !!}
            </div>
        </div>
    @endforeach

    <div class="form-group {{ $errors->has('nis_last_assessment_at') ? ' has-error' : '' }}">
        <label for="nis_last_assessment_at" class="col-md-3 control-label">{{ trans('admin/customers/table.nis_last_assessment_at') }}</label>
        <div class="col-md-4">
            <x-input.datepicker name="nis_last_assessment_at" :value="old('nis_last_assessment_at', optional($item->nis_last_assessment_at)->format('Y-m-d'))" placeholder="{{ trans('general.select_date') }}"/>
            {!! $errors->first('nis_last_assessment_at', '<span class="alert-msg" aria-hidden="true"><i class="fas fa-times" aria-hidden="true"></i> :message</span>') !!}
        </div>
    </div>

    <div class="form-group {{ $errors->has('nis_next_review_at') ? ' has-error' : '' }}">
        <label for="nis_next_review_at" class="col-md-3 control-label">{{ trans('admin/customers/table.nis_next_review_at') }}</label>
        <div class="col-md-4">
            <x-input.datepicker name="nis_next_review_at" :value="old('nis_next_review_at', optional($item->nis_next_review_at)->format('Y-m-d'))" placeholder="{{ trans('general.select_date') }}"/>
            {!! $errors->first('nis_next_review_at', '<span class="alert-msg" aria-hidden="true"><i class="fas fa-times" aria-hidden="true"></i> :message</span>') !!}
        </div>
    </div>
</fieldset>

@include('partials.forms.edit.notes')
@include('partials.forms.edit.image-upload', ['image_path' => app('customers_upload_path')])

<fieldset name="color-preferences">
    <x-form.legend help_text="{{ trans('general.tag_color_help') }}">{{ trans('general.tag_color') }}</x-form.legend>
    <div class="form-group {{ $errors->has('tag_color') ? 'error' : '' }}">
        <label for="tag_color" class="col-md-3 control-label">{{ trans('general.tag_color') }}</label>
        <div class="col-md-9">
            <x-input.colorpicker :item="$item" id="color" :value="old('color', ($item->color ?? '#f4f4f4'))" name="tag_color" id="tag_color" />
            {!! $errors->first('tag_color', '<span class="alert-msg" aria-hidden="true">:message</span>') !!}
        </div>
    </div>
</fieldset>

@stop
