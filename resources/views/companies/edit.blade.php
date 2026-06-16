@extends('layouts/edit-form', [
    'createText' => trans('admin/companies/table.create') ,
    'updateText' => trans('admin/companies/table.update'),
    'helpPosition'  => 'right',
    'helpText' => trans('help.companies'),
    'formAction' => (isset($item->id)) ? route('companies.update', ['company' => $item->id]) : route('companies.store'),
])

{{-- Page content --}}
@section('inputFields')
@include ('partials.forms.edit.name', ['translated_name' => trans('admin/companies/table.name')])
@include ('partials.forms.edit.company-select', ['translated_name' => trans('admin/companies/table.parent'), 'fieldname' => 'parent_id', 'item' => $item, 'allow_empty' => true])
<div class="form-group">
    <div class="col-md-8 col-md-offset-3">
        <p class="help-block" style="margin-top:-8px;">{{ trans('admin/companies/table.parent_help') }}</p>
    </div>
</div>
@include ('partials.forms.edit.phone')
@include ('partials.forms.edit.fax')
@include ('partials.forms.edit.email')
@include ('partials.forms.edit.image-upload', ['image_path' => app('companies_upload_path')])

@php
    $isTenantRoot = is_null(old('parent_id', $item->parent_id));
@endphp

@if ($isTenantRoot)
<fieldset name="tenant-branding">
    <x-form.legend>
        {{ trans('admin/tenants/general.branding') }}
    </x-form.legend>

    @if ($item->tenant)
        <div class="form-group">
            <label class="col-md-3 control-label">{{ trans('admin/tenants/general.uuid') }}</label>
            <div class="col-md-8" style="padding-top: 7px;">
                <code>{{ $item->tenant->uuid }}</code>
            </div>
        </div>
    @endif

    <div class="form-group {{ $errors->has('brand') ? 'error' : '' }}">
        <label for="brand" class="col-md-3 control-label">{{ trans('admin/settings/general.web_brand') }}</label>
        <div class="col-md-8">
            <x-input.select
                name="brand"
                id="brand"
                :options="[
                    '1' => trans('admin/settings/general.logo_option_types.text'),
                    '2' => trans('admin/settings/general.logo_option_types.logo'),
                    '3' => trans('admin/settings/general.logo_option_types.logo_and_text'),
                ]"
                :selected="old('brand', $item->brand ?? 3)"
            />
        </div>
    </div>

    @include('partials.forms.edit.uploadLogo', [
        'item' => $item,
        'currentSettings' => $item,
        'logoVariable' => 'brand_logo',
        'logoId' => 'uploadTenantLogo',
        'logoLabel' => 'admin/tenants/general.brand_logo',
        'logoClearVariable' => 'clear_brand_logo',
        'helpBlock' => trans('general.logo_size') . trans('general.image_filetypes_help', ['size' => \App\Helpers\Helper::file_upload_max_size_readable()]),
    ])

    @include('partials.forms.edit.uploadLogo', [
        'item' => $item,
        'currentSettings' => $item,
        'logoVariable' => 'favicon',
        'logoId' => 'uploadTenantFavicon',
        'logoLabel' => 'admin/settings/general.logo_labels.favicon',
        'logoClearVariable' => 'clear_favicon',
        'helpBlock' => trans('admin/settings/general.favicon_size') . ' ' . trans('admin/settings/general.favicon_format'),
        'allowedTypes' => 'image/gif,image/jpeg,image/webp,image/png,image/svg,image/svg+xml,image/avif,image/vnd.microsoft.icon,image/x-icon,.ico',
    ])

    <div class="form-group {{ $errors->has('header_color') ? 'error' : '' }}">
        <label for="header_color" class="col-md-3 control-label">{{ trans('admin/settings/general.header_color') }}</label>
        <div class="col-md-8">
            <x-input.colorpicker :item="$item" id="header_color" :value="old('header_color', ($item->header_color ?? '#2082be'))" name="header_color" />
        </div>
    </div>

    <div class="form-group {{ $errors->has('nav_link_color') ? 'error' : '' }}">
        <label for="nav_link_color" class="col-md-3 control-label">{{ trans('admin/settings/general.nav_link_color') }}</label>
        <div class="col-md-8">
            <x-input.colorpicker :item="$item" id="nav_link_color" :value="old('nav_link_color', ($item->nav_link_color ?? '#ffffff'))" name="nav_link_color" />
        </div>
    </div>

    <div class="form-group {{ $errors->has('link_light_color') ? 'error' : '' }}">
        <label for="link_light_color" class="col-md-3 control-label">{{ trans('admin/settings/general.link_light_color') }}</label>
        <div class="col-md-8">
            <x-input.colorpicker :item="$item" id="link_light_color" :value="old('link_light_color', ($item->link_light_color ?? '#296282'))" name="link_light_color" />
        </div>
    </div>

    <div class="form-group {{ $errors->has('link_dark_color') ? 'error' : '' }}">
        <label for="link_dark_color" class="col-md-3 control-label">{{ trans('admin/settings/general.link_dark_color') }}</label>
        <div class="col-md-8">
            <x-input.colorpicker :item="$item" id="link_dark_color" :value="old('link_dark_color', ($item->link_dark_color ?? '#5fa4cc'))" name="link_dark_color" />
        </div>
    </div>

    <div class="form-group {{ $errors->has('privacy_policy_link') ? 'error' : '' }}">
        <label for="privacy_policy_link" class="col-md-3 control-label">{{ trans('admin/settings/general.privacy_policy') }}</label>
        <div class="col-md-8">
            <input type="url" class="form-control" name="privacy_policy_link" id="privacy_policy_link" value="{{ old('privacy_policy_link', $item->privacy_policy_link) }}">
        </div>
    </div>

    <div class="form-group {{ $errors->has('footer_text') ? 'error' : '' }}">
        <label for="footer_text" class="col-md-3 control-label">{{ trans('admin/settings/general.footer_text') }}</label>
        <div class="col-md-8">
            <x-input.textarea name="footer_text" id="footer_text" :value="old('footer_text', $item->footer_text)" rows="3" />
        </div>
    </div>

    <div class="form-group {{ $errors->has('custom_css') ? 'error' : '' }}">
        <label for="custom_css" class="col-md-3 control-label">{{ trans('admin/settings/general.custom_css') }}</label>
        <div class="col-md-8">
            <x-input.textarea name="custom_css" id="custom_css" :value="old('custom_css', $item->custom_css)" rows="6" />
        </div>
    </div>
</fieldset>
@endif

<div class="form-group{!! $errors->has('notes') ? ' has-error' : '' !!}">
    <label for="notes" class="col-md-3 control-label">{{ trans('general.notes') }}</label>
    <div class="col-md-8">

        <x-input.textarea
                name="notes"
                id="notes"
                :value="old('notes', $item->notes)"
                placeholder="{{ trans('general.placeholders.notes') }}"
                aria-label="notes"
                rows="5"
        />

    </div>
</div>

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
            <x-input.colorpicker :item="$item" id="tag_color" :value="old('tag_color', ($item->tag_color ?? '#f4f4f4'))" name="tag_color" id="tag_color" />
            {!! $errors->first('tag_color', '<span class="alert-msg" aria-hidden="true">:message</span>') !!}
        </div>
    </div>
</fieldset>


@stop
