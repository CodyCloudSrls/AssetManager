@extends('layouts/edit-form', [
    'createText' => trans('admin/tenants/general.mail.edit'),
    'updateText' => trans('admin/tenants/general.mail.save'),
    'formAction' => route('tenants.mail.update', $tenant),
    'index_route' => route('tenants.show', $tenant),
    'item' => $rootCompany,
])

@section('inputFields')
    @method('PUT')

    <div class="form-group">
        <div class="col-md-8 col-md-offset-3">
            <div class="alert alert-info" style="margin-bottom: 0;">
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
        <div class="col-md-6">
            <input class="form-control" type="email" name="tenant_mail_reply_to_email" id="tenant_mail_reply_to_email" value="{{ old('tenant_mail_reply_to_email', $rootCompany->tenant_mail_reply_to_email ?: $tenant->notificationReplyToEmail()) }}">
        </div>
    </div>

    <div class="form-group {{ $errors->has('tenant_mail_reply_to_name') ? ' has-error' : '' }}">
        <label for="tenant_mail_reply_to_name" class="col-md-3 control-label">{{ trans('admin/tenants/general.mail.reply_to_name') }}</label>
        <div class="col-md-6">
            <input class="form-control" type="text" name="tenant_mail_reply_to_name" id="tenant_mail_reply_to_name" value="{{ old('tenant_mail_reply_to_name', $rootCompany->tenant_mail_reply_to_name ?: $tenant->notificationReplyToName()) }}">
        </div>
    </div>

    <div class="form-group {{ $errors->has('helpdesk_contact_email') ? ' has-error' : '' }}">
        <label for="helpdesk_contact_email" class="col-md-3 control-label">{{ trans('admin/tenants/general.mail.helpdesk_contact_email') }}</label>
        <div class="col-md-6">
            <input class="form-control" type="email" name="helpdesk_contact_email" id="helpdesk_contact_email" value="{{ old('helpdesk_contact_email', $rootCompany->helpdesk_contact_email ?: $rootCompany->email) }}">
            <p class="help-block">{{ trans('admin/tenants/general.mail.helpdesk_contact_email_help') }}</p>
        </div>
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
                <label class="form-control" style="margin-bottom: 8px;">
                    <input
                        type="checkbox"
                        name="tenant_mail_notification_events[]"
                        value="{{ $eventKey }}"
                        {{ in_array($eventKey, old('tenant_mail_notification_events', $enabledEvents), true) ? 'checked="checked"' : '' }}>
                    <span>
                        <strong>{{ $eventLabel }}</strong>
                        <br><span class="text-muted">{{ trans('admin/tenants/general.mail.event_descriptions.'.$eventKey) }}</span>
                    </span>
                </label>
            @endforeach
            {!! $errors->first('tenant_mail_notification_events', '<span class="alert-msg">:message</span>') !!}
        </div>
    </div>
@stop
