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
                <strong>{{ trans('admin/tenants/general.mail.intro_title') }}</strong><br>
                {{ trans('admin/tenants/general.mail.intro_help') }}
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

    {{-- ── SMTP dedicato del tenant (opzionale; vuoto = usa il mailer di piattaforma) ── --}}
    <hr>
    <div class="form-group"><div class="col-md-offset-3 col-md-8">
        <h4 style="margin-top:0;">{{ trans('admin/tenants/general.mail.smtp_title') }}</h4>
        <p class="help-block">{{ trans('admin/tenants/general.mail.smtp_help') }}</p>
    </div></div>

    <div class="form-group {{ $errors->has('tenant_mail_host') ? ' has-error' : '' }}">
        <label for="tenant_mail_host" class="col-md-3 control-label">{{ trans('admin/tenants/general.mail.smtp_host') }}</label>
        <div class="col-md-6">
            <input class="form-control" type="text" name="tenant_mail_host" id="tenant_mail_host" value="{{ old('tenant_mail_host', $rootCompany->tenant_mail_host) }}" placeholder="smtp.example.com">
            {!! $errors->first('tenant_mail_host', '<span class="alert-msg">:message</span>') !!}
        </div>
    </div>
    <div class="form-group {{ $errors->has('tenant_mail_port') ? ' has-error' : '' }}">
        <label for="tenant_mail_port" class="col-md-3 control-label">{{ trans('admin/tenants/general.mail.smtp_port') }}</label>
        <div class="col-md-3">
            <input class="form-control" type="number" min="1" max="65535" name="tenant_mail_port" id="tenant_mail_port" value="{{ old('tenant_mail_port', $rootCompany->tenant_mail_port) }}" placeholder="587">
            {!! $errors->first('tenant_mail_port', '<span class="alert-msg">:message</span>') !!}
        </div>
    </div>
    <div class="form-group">
        <label for="tenant_mail_encryption" class="col-md-3 control-label">{{ trans('admin/tenants/general.mail.smtp_encryption') }}</label>
        <div class="col-md-3">
            <select class="form-control" name="tenant_mail_encryption" id="tenant_mail_encryption">
                <option value="">—</option>
                <option value="tls" {{ old('tenant_mail_encryption', $rootCompany->tenant_mail_encryption) === 'tls' ? 'selected' : '' }}>TLS</option>
                <option value="ssl" {{ old('tenant_mail_encryption', $rootCompany->tenant_mail_encryption) === 'ssl' ? 'selected' : '' }}>SSL</option>
            </select>
        </div>
    </div>
    <div class="form-group">
        <label for="tenant_mail_username" class="col-md-3 control-label">{{ trans('admin/tenants/general.mail.smtp_username') }}</label>
        <div class="col-md-6">
            <input class="form-control" type="text" name="tenant_mail_username" id="tenant_mail_username" value="{{ old('tenant_mail_username', $rootCompany->tenant_mail_username) }}" autocomplete="off">
        </div>
    </div>
    <div class="form-group {{ $errors->has('tenant_mail_password') ? ' has-error' : '' }}">
        <label for="tenant_mail_password" class="col-md-3 control-label">{{ trans('admin/tenants/general.mail.smtp_password') }}</label>
        <div class="col-md-6">
            <input class="form-control" type="password" name="tenant_mail_password" id="tenant_mail_password" value="" autocomplete="new-password" placeholder="{{ $rootCompany->tenant_mail_password ? '••••••••' : '' }}">
            <p class="help-block">{{ trans('admin/tenants/general.mail.smtp_password_help') }}</p>
        </div>
    </div>
    <div class="form-group {{ $errors->has('tenant_mail_from_email') ? ' has-error' : '' }}">
        <label for="tenant_mail_from_email" class="col-md-3 control-label">{{ trans('admin/tenants/general.mail.from_email') }}</label>
        <div class="col-md-6">
            <input class="form-control" type="email" name="tenant_mail_from_email" id="tenant_mail_from_email" value="{{ old('tenant_mail_from_email', $rootCompany->tenant_mail_from_email) }}">
            <p class="help-block">{{ trans('admin/tenants/general.mail.from_email_help') }}</p>
            {!! $errors->first('tenant_mail_from_email', '<span class="alert-msg">:message</span>') !!}
        </div>
    </div>
    <div class="form-group">
        <div class="col-md-offset-3 col-md-8">
            <button type="button" class="btn btn-default" id="cc-send-test-mail"><i class="fa-regular fa-paper-plane fa-fw" aria-hidden="true"></i> {{ trans('admin/tenants/general.mail.send_test') }}</button>
            <p class="help-block">{{ trans('admin/tenants/general.mail.send_test_help') }}</p>
        </div>
    </div>
    @push('js')
    <script>
        document.getElementById('cc-send-test-mail')?.addEventListener('click', function () {
            if (!confirm('{{ trans('admin/tenants/general.mail.send_test_confirm') }}')) return;
            var f = document.createElement('form');
            f.method = 'POST';
            f.action = '{{ route('tenants.mail.test', $tenant) }}';
            f.innerHTML = '<input type="hidden" name="_token" value="{{ csrf_token() }}">';
            document.body.appendChild(f);
            f.submit();
        });
    </script>
    @endpush

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
