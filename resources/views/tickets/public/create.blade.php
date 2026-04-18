<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ trans('admin/tickets/general.public_open') }} - {{ $rootCompany?->name ?? $tenant->display_name }}</title>
    <link rel="stylesheet" href="{{ asset('css/dist/all.css') }}">
</head>
<body class="hold-transition login-page" style="background:#1f2428;">
<div class="login-box" style="width: 720px; max-width: 94%;">
    <div class="login-logo" style="color:#fff;">
        <strong>{{ $rootCompany?->name ?? $tenant->display_name }}</strong><br>
        <small>{{ trans('admin/tickets/general.public_open') }}</small>
    </div>
    <div class="login-box-body" style="border-radius: 8px;">
        @if ($errors->any())
            <div class="alert alert-danger">
                <ul style="margin: 0; padding-left: 18px;">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif
        @if (session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        @if ($tenant->publicHelpdeskIntro())
            <div class="alert alert-info">{!! \App\Helpers\Helper::parseEscapedMarkedown($tenant->publicHelpdeskIntro()) !!}</div>
        @endif
        @if ($tenant->publicHelpdeskContactEmail() || $tenant->publicHelpdeskContactPhone())
            <div class="well well-sm">
                @if ($tenant->publicHelpdeskContactEmail())
                    <div><strong>{{ trans('admin/tenants/general.helpdesk.contact_email') }}:</strong> {{ $tenant->publicHelpdeskContactEmail() }}</div>
                @endif
                @if ($tenant->publicHelpdeskContactPhone())
                    <div><strong>{{ trans('admin/tenants/general.helpdesk.contact_phone') }}:</strong> {{ $tenant->publicHelpdeskContactPhone() }}</div>
                @endif
            </div>
        @endif
        <form action="{{ route('tickets.portal.store', ['tenantPortal' => $tenant->publicHelpdeskRouteKey()]) }}" method="POST" enctype="multipart/form-data">
            @csrf
            <div class="form-group">
                <label for="guest_name">{{ trans('admin/tickets/form.guest_name') }}</label>
                <input class="form-control" type="text" name="guest_name" id="guest_name" value="{{ old('guest_name') }}" required>
            </div>
            <div class="form-group">
                <label for="guest_email">{{ trans('admin/tickets/form.guest_email') }}</label>
                <input class="form-control" type="email" name="guest_email" id="guest_email" value="{{ old('guest_email') }}" required>
            </div>
            <div class="form-group">
                <label for="guest_phone">{{ trans('admin/tickets/form.guest_phone') }}</label>
                <input class="form-control" type="text" name="guest_phone" id="guest_phone" value="{{ old('guest_phone') }}">
            </div>
            <div class="form-group">
                <label for="ticket_type_id">{{ trans('admin/tickets/form.type') }}</label>
                <select class="form-control" name="ticket_type_id" id="ticket_type_id">
                    <option value="">{{ trans('admin/tickets/form.select_type') }}</option>
                    @foreach ($ticketTypes as $ticketType)
                        <option value="{{ $ticketType->id }}" @selected(old('ticket_type_id') == $ticketType->id)>{{ $ticketType->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group">
                <label for="subject">{{ trans('general.subject') }}</label>
                <input class="form-control" type="text" name="subject" id="subject" value="{{ old('subject') }}" required>
            </div>
            <div class="form-group">
                <label for="description">{{ trans('admin/tickets/form.description') }}</label>
                <textarea class="form-control" name="description" id="description" rows="7" required>{{ old('description') }}</textarea>
            </div>
            @if ($tenant->publicHelpdeskAllowsAttachments())
                <div class="form-group">
                    <label for="file">{{ trans('general.file_upload') }}</label>
                    <input class="form-control" type="file" name="file[]" id="file" multiple>
                    <p class="help-block">{{ trans('general.upload_filetypes_help', ['allowed_filetypes' => config('filesystems.allowed_upload_extensions'), 'size' => \App\Helpers\Helper::file_upload_max_size_readable()]) }}</p>
                </div>
            @endif
            @if ($tenant->publicHelpdeskPrivacyNote())
                <div class="alert alert-warning" style="margin-top: 15px;">
                    {!! \App\Helpers\Helper::parseEscapedMarkedown($tenant->publicHelpdeskPrivacyNote()) !!}
                </div>
            @endif
            <button class="btn btn-primary btn-block">{{ trans('admin/tickets/general.public_submit') }}</button>
        </form>
    </div>
</div>
</body>
</html>
