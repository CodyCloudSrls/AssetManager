@extends('layouts/edit-form', [
    'createText' => trans('admin/tenants/general.settings.edit'),
    'updateText' => trans('admin/tenants/general.settings.save'),
    'formAction' => route('tenants.settings.update', $tenant),
    'index_route' => route('tenants.show', $tenant),
    'item' => $rootCompany,
])

@section('inputFields')
    @method('PUT')

    <div class="form-group {{ $errors->has('default_locale') ? ' has-error' : '' }}">
        <label for="default_locale" class="col-md-3 control-label">{{ trans('admin/tenants/general.settings.default_locale') }}</label>
        <div class="col-md-5">
            <x-input.select
                name="default_locale"
                id="default_locale"
                :options="$languageOptions"
                :selected="old('default_locale', $tenant->defaultLocale())"
            />
            <p class="help-block">{{ trans('admin/tenants/general.settings.default_locale_help') }}</p>
            {!! $errors->first('default_locale', '<span class="alert-msg">:message</span>') !!}
        </div>
    </div>

    <div class="form-group {{ $errors->has('default_compliance_jurisdiction') ? ' has-error' : '' }}">
        <label for="default_compliance_jurisdiction" class="col-md-3 control-label">{{ trans('admin/tenants/general.settings.default_compliance_jurisdiction') }}</label>
        <div class="col-md-5">
            <x-input.select
                name="default_compliance_jurisdiction"
                id="default_compliance_jurisdiction"
                :options="$jurisdictionOptions"
                :selected="old('default_compliance_jurisdiction', $tenant->defaultComplianceJurisdiction())"
            />
            <p class="help-block">{{ trans('admin/tenants/general.settings.default_compliance_jurisdiction_help') }}</p>
            {!! $errors->first('default_compliance_jurisdiction', '<span class="alert-msg">:message</span>') !!}
        </div>
    </div>

    <div class="form-group {{ $errors->has('bootstrap_compliance_frameworks') ? ' has-error' : '' }}">
        <div class="col-md-8 col-md-offset-3">
            <label class="form-control">
                <input type="checkbox" name="bootstrap_compliance_frameworks" value="1" {{ old('bootstrap_compliance_frameworks') ? 'checked="checked"' : '' }}>
                <span>{{ trans('admin/tenants/general.settings.bootstrap_compliance_frameworks') }}</span>
            </label>
            <p class="help-block">{{ trans('admin/tenants/general.settings.bootstrap_compliance_frameworks_help') }}</p>
            {!! $errors->first('bootstrap_compliance_frameworks', '<span class="alert-msg">:message</span>') !!}
        </div>
    </div>
@stop
