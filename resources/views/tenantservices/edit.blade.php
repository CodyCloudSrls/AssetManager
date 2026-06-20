@extends('layouts/edit-form', [
    'createText' => trans('admin/tenantservices/general.create'),
    'updateText' => trans('admin/tenantservices/general.update'),
    'helpTitle' => trans('admin/tenantservices/general.about_title'),
    'helpText' => trans('admin/tenantservices/general.about_text'),
    'formAction' => $service->exists ? route('tenants.services.update', [$tenant, $service]) : route('tenants.services.store', $tenant),
    'index_route' => route('tenants.services.index', $tenant),
    'item' => $service,
])

@section('inputFields')
    <div class="form-group {{ $errors->has('macro_area') ? ' has-error' : '' }}">
        <label for="macro_area" class="col-md-3 control-label">{{ trans('admin/tenantservices/general.macro_area') }}</label>
        <div class="col-md-7">
            <x-input.select
                name="macro_area"
                id="macro_area"
                :options="$macroAreaOptions"
                :selected="old('macro_area', $service->macro_area)"
            />
            <p class="help-block">{{ trans('admin/tenantservices/general.macro_area_help') }}</p>
            {!! $errors->first('macro_area', '<span class="alert-msg">:message</span>') !!}
        </div>
    </div>

    @if (!empty($companyOptions) && count($companyOptions) > 1)
    <div class="form-group {{ $errors->has('company_id') ? ' has-error' : '' }}">
        <label for="company_id" class="col-md-3 control-label">{{ trans('admin/tenantservices/general.company') }}</label>
        <div class="col-md-7">
            <select class="form-control select2" name="company_id" id="company_id" aria-label="company_id">
                <option value="">{{ trans('admin/tenantservices/general.company_tenant_wide') }}</option>
                @foreach ($companyOptions as $companyId => $companyName)
                    <option value="{{ $companyId }}" @selected((string) old('company_id', $service->company_id) === (string) $companyId)>{{ $companyName }}</option>
                @endforeach
            </select>
            <p class="help-block">{{ trans('admin/tenantservices/general.company_help') }}</p>
            {!! $errors->first('company_id', '<span class="alert-msg">:message</span>') !!}
        </div>
    </div>
    @endif

    <div class="form-group {{ $errors->has('name') ? ' has-error' : '' }}">
        <label for="name" class="col-md-3 control-label">{{ trans('admin/tenantservices/general.name') }}</label>
        <div class="col-md-7">
            <input class="form-control" type="text" name="name" id="name" value="{{ old('name', $service->name) }}" required>
            <p class="help-block">{{ trans('admin/tenantservices/general.name_help') }}</p>
            {!! $errors->first('name', '<span class="alert-msg">:message</span>') !!}
        </div>
    </div>

    <div class="form-group {{ $errors->has('description') ? ' has-error' : '' }}">
        <label for="description" class="col-md-3 control-label">{{ trans('admin/tenantservices/general.description') }}</label>
        <div class="col-md-7">
            <textarea class="form-control" name="description" id="description" rows="4">{{ old('description', $service->description) }}</textarea>
            {!! $errors->first('description', '<span class="alert-msg">:message</span>') !!}
        </div>
    </div>

    <div class="form-group {{ $errors->has('acn_subject_basis') ? ' has-error' : '' }}">
        <label for="acn_subject_basis" class="col-md-3 control-label">{{ trans('admin/tenantservices/general.acn_subject_basis') }}</label>
        <div class="col-md-7">
            <textarea class="form-control" name="acn_subject_basis" id="acn_subject_basis" rows="3">{{ old('acn_subject_basis', $service->acn_subject_basis) }}</textarea>
            <p class="help-block">{{ trans('admin/tenantservices/general.acn_subject_basis_help') }}</p>
            {!! $errors->first('acn_subject_basis', '<span class="alert-msg">:message</span>') !!}
        </div>
    </div>

    <div class="form-group {{ $errors->has('relevance_override') ? ' has-error' : '' }}">
        <label for="relevance_override" class="col-md-3 control-label">{{ trans('admin/tenantservices/general.relevance_override') }}</label>
        <div class="col-md-5">
            <select class="form-control select2" name="relevance_override" id="relevance_override" aria-label="relevance_override">
                <option value="">{{ trans('admin/tenantservices/general.none_override') }}</option>
                @foreach ($impactOptions as $value => $label)
                    <option value="{{ $value }}" @selected(old('relevance_override', $service->relevance_override) === $value)>{{ $label }}</option>
                @endforeach
            </select>
            <p class="help-block">{{ trans('admin/tenantservices/general.relevance_override_help') }}</p>
            {!! $errors->first('relevance_override', '<span class="alert-msg">:message</span>') !!}
        </div>
    </div>

    <div class="form-group {{ $errors->has('is_active') ? ' has-error' : '' }}">
        <div class="col-md-7 col-md-offset-3">
            <input type="hidden" name="is_active" value="0">
            <label class="form-control">
                <input type="checkbox" name="is_active" value="1" {{ old('is_active', $service->is_active ?? true) ? 'checked="checked"' : '' }}>
                {{ trans('admin/tenantservices/general.active') }}
            </label>
            {!! $errors->first('is_active', '<span class="alert-msg">:message</span>') !!}
        </div>
    </div>
@stop
