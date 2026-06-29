@extends('layouts/edit-form', [
    'createText' => trans('admin/tenants/general.config.title'),
    'updateText' => trans('admin/tenants/general.config.title'),
    'formAction' => route('tenants.config.update', $tenant),
    'index_route' => route('tenants.show', $tenant),
    'item' => $rootCompany,
])

@section('inputFields')
    @method('PUT')

    {{-- ============================ GENERALE ============================ --}}
    <fieldset name="tenant-general">
        <x-form.legend help_text="{{ trans('admin/tenants/general.config.section_general_help') }}">
            {{ trans('admin/tenants/general.config.section_general') }}
        </x-form.legend>

        <div class="form-group">
            <label class="col-md-3 control-label">{{ trans('admin/tenants/general.uuid') }}</label>
            <div class="col-md-8" style="padding-top:7px;"><code>{{ $tenant->uuid }}</code></div>
        </div>

        <div class="form-group {{ $errors->has('default_locale') ? ' has-error' : '' }}">
            <label for="default_locale" class="col-md-3 control-label">{{ trans('admin/tenants/general.settings.default_locale') }}</label>
            <div class="col-md-5">
                <x-input.select name="default_locale" id="default_locale" :options="$languageOptions" :selected="old('default_locale', $tenant->defaultLocale())" />
                <p class="help-block">{{ trans('admin/tenants/general.settings.default_locale_help') }}</p>
                {!! $errors->first('default_locale', '<span class="alert-msg">:message</span>') !!}
            </div>
        </div>

        <div class="form-group {{ $errors->has('default_compliance_jurisdiction') ? ' has-error' : '' }}">
            <label for="default_compliance_jurisdiction" class="col-md-3 control-label">{{ trans('admin/tenants/general.settings.default_compliance_jurisdiction') }}</label>
            <div class="col-md-5">
                <x-input.select name="default_compliance_jurisdiction" id="default_compliance_jurisdiction" :options="$jurisdictionOptions" :selected="old('default_compliance_jurisdiction', $tenant->defaultComplianceJurisdiction())" />
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
            </div>
        </div>
    </fieldset>

    {{-- ============================ BRANDING ============================ --}}
    <fieldset name="tenant-branding">
        <x-form.legend help_text="{{ trans('admin/tenants/general.config.section_branding_help') }}">
            {{ trans('admin/tenants/general.branding') }}
        </x-form.legend>

        <div class="form-group {{ $errors->has('brand') ? ' has-error' : '' }}">
            <label for="brand" class="col-md-3 control-label">{{ trans('admin/settings/general.web_brand') }}</label>
            <div class="col-md-8">
                <x-input.select name="brand" id="brand" :options="[
                    '1' => trans('admin/settings/general.logo_option_types.text'),
                    '2' => trans('admin/settings/general.logo_option_types.logo'),
                    '3' => trans('admin/settings/general.logo_option_types.logo_and_text'),
                ]" :selected="old('brand', $rootCompany->brand ?? 3)" />
            </div>
        </div>

        @include('partials.forms.edit.uploadLogo', [
            'item' => $rootCompany,
            'currentSettings' => $rootCompany,
            'logoVariable' => 'brand_logo',
            'logoId' => 'uploadTenantLogo',
            'logoLabel' => 'admin/tenants/general.brand_logo',
            'logoClearVariable' => 'clear_brand_logo',
            'helpBlock' => trans('general.logo_size') . trans('general.image_filetypes_help', ['size' => \App\Helpers\Helper::file_upload_max_size_readable()]),
        ])

        @include('partials.forms.edit.uploadLogo', [
            'item' => $rootCompany,
            'currentSettings' => $rootCompany,
            'logoVariable' => 'favicon',
            'logoId' => 'uploadTenantFavicon',
            'logoLabel' => 'admin/settings/general.logo_labels.favicon',
            'logoClearVariable' => 'clear_favicon',
            'helpBlock' => trans('admin/settings/general.favicon_size') . ' ' . trans('admin/settings/general.favicon_format'),
            'allowedTypes' => 'image/gif,image/jpeg,image/webp,image/png,image/svg,image/svg+xml,image/avif,image/vnd.microsoft.icon,image/x-icon,.ico',
        ])

        <div class="form-group {{ $errors->has('header_color') ? ' has-error' : '' }}">
            <label for="header_color" class="col-md-3 control-label">{{ trans('admin/settings/general.header_color') }}</label>
            <div class="col-md-8"><x-input.colorpicker :item="$rootCompany" id="header_color" :value="old('header_color', ($rootCompany->header_color ?? '#2082be'))" name="header_color" /></div>
        </div>
        <div class="form-group {{ $errors->has('nav_link_color') ? ' has-error' : '' }}">
            <label for="nav_link_color" class="col-md-3 control-label">{{ trans('admin/settings/general.nav_link_color') }}</label>
            <div class="col-md-8"><x-input.colorpicker :item="$rootCompany" id="nav_link_color" :value="old('nav_link_color', ($rootCompany->nav_link_color ?? '#ffffff'))" name="nav_link_color" /></div>
        </div>
        <div class="form-group {{ $errors->has('link_light_color') ? ' has-error' : '' }}">
            <label for="link_light_color" class="col-md-3 control-label">{{ trans('admin/settings/general.link_light_color') }}</label>
            <div class="col-md-8"><x-input.colorpicker :item="$rootCompany" id="link_light_color" :value="old('link_light_color', ($rootCompany->link_light_color ?? '#296282'))" name="link_light_color" /></div>
        </div>
        <div class="form-group {{ $errors->has('link_dark_color') ? ' has-error' : '' }}">
            <label for="link_dark_color" class="col-md-3 control-label">{{ trans('admin/settings/general.link_dark_color') }}</label>
            <div class="col-md-8"><x-input.colorpicker :item="$rootCompany" id="link_dark_color" :value="old('link_dark_color', ($rootCompany->link_dark_color ?? '#5fa4cc'))" name="link_dark_color" /></div>
        </div>

        <div class="form-group {{ $errors->has('privacy_policy_link') ? ' has-error' : '' }}">
            <label for="privacy_policy_link" class="col-md-3 control-label">{{ trans('admin/settings/general.privacy_policy') }}</label>
            <div class="col-md-8">
                <input type="url" class="form-control" name="privacy_policy_link" id="privacy_policy_link" value="{{ old('privacy_policy_link', $rootCompany->privacy_policy_link) }}">
                {!! $errors->first('privacy_policy_link', '<span class="alert-msg">:message</span>') !!}
            </div>
        </div>
        <div class="form-group {{ $errors->has('footer_text') ? ' has-error' : '' }}">
            <label for="footer_text" class="col-md-3 control-label">{{ trans('admin/settings/general.footer_text') }}</label>
            <div class="col-md-8"><x-input.textarea name="footer_text" id="footer_text" :value="old('footer_text', $rootCompany->footer_text)" rows="3" /></div>
        </div>
        <div class="form-group {{ $errors->has('custom_css') ? ' has-error' : '' }}">
            <label for="custom_css" class="col-md-3 control-label">{{ trans('admin/settings/general.custom_css') }}</label>
            <div class="col-md-8"><x-input.textarea name="custom_css" id="custom_css" :value="old('custom_css', $rootCompany->custom_css)" rows="6" /></div>
        </div>
    </fieldset>

    {{-- ============================ HELPDESK ============================ --}}
    <fieldset name="tenant-helpdesk">
        <x-form.legend help_text="{{ trans('admin/tenants/general.config.section_helpdesk_help') }}">
            {{ trans('admin/tenants/general.helpdesk.title') }}
        </x-form.legend>

        <div class="form-group">
            <label class="col-md-3 control-label">{{ trans('admin/tenants/general.helpdesk.public_url') }}</label>
            <div class="col-md-8">
                <input class="form-control" type="text" readonly value="{{ $tenant->publicHelpdeskUrl() }}">
                <p class="help-block">{{ trans('admin/tenants/general.helpdesk.public_url_help') }}</p>
            </div>
        </div>

        <div class="form-group">
            <div class="col-md-8 col-md-offset-3">
                <input type="hidden" name="helpdesk_enabled" value="0">
                <label class="form-control">
                    <input type="checkbox" value="1" name="helpdesk_enabled" {{ old('helpdesk_enabled', $rootCompany->helpdesk_enabled) ? ' checked="checked"' : '' }}>
                    {{ trans('admin/tenants/general.helpdesk.enabled') }}
                </label>
            </div>
        </div>
        <div class="form-group">
            <div class="col-md-8 col-md-offset-3">
                <input type="hidden" name="helpdesk_allow_attachments" value="0">
                <label class="form-control">
                    <input type="checkbox" value="1" name="helpdesk_allow_attachments" {{ old('helpdesk_allow_attachments', $rootCompany->helpdesk_allow_attachments ?? true) ? ' checked="checked"' : '' }}>
                    {{ trans('admin/tenants/general.helpdesk.allow_attachments') }}
                </label>
            </div>
        </div>

        <div class="form-group {{ $errors->has('helpdesk_slug') ? ' has-error' : '' }}">
            <label for="helpdesk_slug" class="col-md-3 control-label">{{ trans('admin/tenants/general.helpdesk.slug') }}</label>
            <div class="col-md-6">
                <input class="form-control" type="text" name="helpdesk_slug" id="helpdesk_slug" value="{{ old('helpdesk_slug', $rootCompany->helpdesk_slug) }}" autocomplete="off">
                <p class="help-block">{{ trans('admin/tenants/general.helpdesk.slug_help') }}</p>
                {!! $errors->first('helpdesk_slug', '<span class="alert-msg">:message</span>') !!}
            </div>
        </div>
        <div class="form-group {{ $errors->has('helpdesk_contact_email') ? ' has-error' : '' }}">
            <label for="helpdesk_contact_email" class="col-md-3 control-label">{{ trans('admin/tenants/general.helpdesk.contact_email') }}</label>
            <div class="col-md-6">
                <input class="form-control" type="email" name="helpdesk_contact_email" id="helpdesk_contact_email" value="{{ old('helpdesk_contact_email', $rootCompany->helpdesk_contact_email ?: $rootCompany->email) }}">
                {!! $errors->first('helpdesk_contact_email', '<span class="alert-msg">:message</span>') !!}
            </div>
        </div>
        <div class="form-group {{ $errors->has('helpdesk_contact_phone') ? ' has-error' : '' }}">
            <label for="helpdesk_contact_phone" class="col-md-3 control-label">{{ trans('admin/tenants/general.helpdesk.contact_phone') }}</label>
            <div class="col-md-6">
                <input class="form-control" type="text" name="helpdesk_contact_phone" id="helpdesk_contact_phone" value="{{ old('helpdesk_contact_phone', $rootCompany->helpdesk_contact_phone ?: $rootCompany->phone) }}">
            </div>
        </div>
        <div class="form-group {{ $errors->has('helpdesk_intro') ? ' has-error' : '' }}">
            <label for="helpdesk_intro" class="col-md-3 control-label">{{ trans('admin/tenants/general.helpdesk.intro') }}</label>
            <div class="col-md-8"><textarea class="form-control" name="helpdesk_intro" id="helpdesk_intro" rows="4">{{ old('helpdesk_intro', $rootCompany->helpdesk_intro) }}</textarea></div>
        </div>
        <div class="form-group {{ $errors->has('helpdesk_privacy_note') ? ' has-error' : '' }}">
            <label for="helpdesk_privacy_note" class="col-md-3 control-label">{{ trans('admin/tenants/general.helpdesk.privacy_note') }}</label>
            <div class="col-md-8"><textarea class="form-control" name="helpdesk_privacy_note" id="helpdesk_privacy_note" rows="4">{{ old('helpdesk_privacy_note', $rootCompany->helpdesk_privacy_note) }}</textarea></div>
        </div>

        <div class="form-group {{ $errors->has('public_ticket_type_ids') ? ' has-error' : '' }}">
            <label class="col-md-3 control-label">{{ trans('admin/tenants/general.helpdesk.public_ticket_types') }}</label>
            <div class="col-md-8">
                @forelse ($availableTicketTypes as $ticketType)
                    <label class="form-control" style="margin-bottom:8px;">
                        <input type="checkbox" name="public_ticket_type_ids[]" value="{{ $ticketType->id }}"
                            {{ in_array($ticketType->id, old('public_ticket_type_ids', $selectedTicketTypeIds), true) ? 'checked="checked"' : '' }}>
                        <span><strong>{{ $ticketType->name }}</strong>
                            @if ($ticketType->description)<br><span class="text-muted">{{ $ticketType->description }}</span>@endif
                        </span>
                    </label>
                @empty
                    <p class="text-muted">{{ trans('admin/tenants/general.helpdesk.no_ticket_types_available') }}</p>
                @endforelse
                {!! $errors->first('public_ticket_type_ids', '<span class="alert-msg">:message</span>') !!}
            </div>
        </div>
    </fieldset>

    {{-- ======================= MAIL & NOTIFICHE ======================= --}}
    <fieldset name="tenant-mail">
        <x-form.legend help_text="{{ trans('admin/tenants/general.config.section_mail_help') }}">
            {{ trans('admin/tenants/general.mail.title') }}
        </x-form.legend>

        <div class="form-group">
            <div class="col-md-8 col-md-offset-3">
                <div class="alert alert-info" style="margin-bottom:0;">
                    <strong>{{ trans('admin/tenants/general.mail.smtp_title') }}</strong><br>
                    {{ trans('admin/tenants/general.mail.smtp_help') }}
                </div>
            </div>
        </div>

        <div class="form-group {{ $errors->has('tenant_notification_email') ? ' has-error' : '' }}">
            <label for="tenant_notification_email" class="col-md-3 control-label">{{ trans('admin/tenants/general.mail.notification_email') }}</label>
            <div class="col-md-8">
                <input class="form-control" type="text" name="tenant_notification_email" id="tenant_notification_email" value="{{ old('tenant_notification_email', $rootCompany->tenant_notification_email ?: $tenant->notificationEmail()) }}">
                <p class="help-block">{{ trans('admin/tenants/general.mail.notification_email_help') }}</p>
                {!! $errors->first('tenant_notification_email', '<span class="alert-msg">:message</span>') !!}
            </div>
        </div>
        <div class="form-group {{ $errors->has('tenant_mail_from_name') ? ' has-error' : '' }}">
            <label for="tenant_mail_from_name" class="col-md-3 control-label">{{ trans('admin/tenants/general.mail.from_name') }}</label>
            <div class="col-md-6">
                <input class="form-control" type="text" name="tenant_mail_from_name" id="tenant_mail_from_name" value="{{ old('tenant_mail_from_name', $rootCompany->tenant_mail_from_name ?: $rootCompany->name) }}">
                <p class="help-block">{{ trans('admin/tenants/general.mail.from_name_help') }}</p>
            </div>
        </div>
        <div class="form-group {{ $errors->has('tenant_mail_reply_to_email') ? ' has-error' : '' }}">
            <label for="tenant_mail_reply_to_email" class="col-md-3 control-label">{{ trans('admin/tenants/general.mail.reply_to_email') }}</label>
            <div class="col-md-6"><input class="form-control" type="email" name="tenant_mail_reply_to_email" id="tenant_mail_reply_to_email" value="{{ old('tenant_mail_reply_to_email', $rootCompany->tenant_mail_reply_to_email ?: $tenant->notificationReplyToEmail()) }}"></div>
        </div>
        <div class="form-group {{ $errors->has('tenant_mail_reply_to_name') ? ' has-error' : '' }}">
            <label for="tenant_mail_reply_to_name" class="col-md-3 control-label">{{ trans('admin/tenants/general.mail.reply_to_name') }}</label>
            <div class="col-md-6"><input class="form-control" type="text" name="tenant_mail_reply_to_name" id="tenant_mail_reply_to_name" value="{{ old('tenant_mail_reply_to_name', $rootCompany->tenant_mail_reply_to_name ?: $tenant->notificationReplyToName()) }}"></div>
        </div>
        <div class="form-group {{ $errors->has('tenant_document_review_warning_days') ? ' has-error' : '' }}">
            <label for="tenant_document_review_warning_days" class="col-md-3 control-label">{{ trans('admin/tenants/general.mail.document_review_warning_days') }}</label>
            <div class="col-md-3">
                <div class="input-group">
                    <input class="form-control" type="number" min="1" max="365" name="tenant_document_review_warning_days" id="tenant_document_review_warning_days" value="{{ old('tenant_document_review_warning_days', $rootCompany->tenant_document_review_warning_days ?: 30) }}">
                    <span class="input-group-addon">{{ trans('general.days') }}</span>
                </div>
                <p class="help-block">{{ trans('admin/tenants/general.mail.document_review_warning_days_help') }}</p>
            </div>
        </div>
        <div class="form-group {{ $errors->has('tenant_mail_notification_events') ? ' has-error' : '' }}">
            <label class="col-md-3 control-label">{{ trans('admin/tenants/general.mail.events_title') }}</label>
            <div class="col-md-8">
                @foreach ($mailEventOptions as $eventKey => $eventLabel)
                    <label class="form-control" style="margin-bottom:8px;">
                        <input type="checkbox" name="tenant_mail_notification_events[]" value="{{ $eventKey }}"
                            {{ in_array($eventKey, old('tenant_mail_notification_events', $enabledEvents), true) ? 'checked="checked"' : '' }}>
                        <span><strong>{{ $eventLabel }}</strong>
                            <br><span class="text-muted">{{ trans('admin/tenants/general.mail.event_descriptions.'.$eventKey) }}</span>
                        </span>
                    </label>
                @endforeach
                {!! $errors->first('tenant_mail_notification_events', '<span class="alert-msg">:message</span>') !!}
            </div>
        </div>
    </fieldset>
@stop
