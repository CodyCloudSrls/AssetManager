<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $ticket->ticket_number }} - {{ $rootCompany?->name ?? $tenant->display_name }}</title>
    <link rel="stylesheet" href="{{ asset('css/dist/all.css') }}">
    <style>
        body.public-ticket-portal {
            background: #1f2428;
            color: #e5e7eb;
        }
        .public-ticket-shell {
            max-width: 980px;
            margin-top: 30px;
            margin-bottom: 30px;
        }
        .public-ticket-card,
        .public-ticket-card .box-header,
        .public-ticket-card .box-body {
            background: #31373d;
            color: #e5e7eb;
            border-color: #454c53;
        }
        .public-ticket-card .box-title,
        .public-ticket-card strong,
        .public-ticket-card h4,
        .public-ticket-card label {
            color: #ffffff;
        }
        .public-ticket-card hr {
            border-top-color: #4f5861;
        }
        .public-ticket-card .form-control {
            background: #45403f;
            border-color: #69615f;
            color: #ffffff;
        }
        .public-ticket-card .form-control::placeholder {
            color: #c8c8c8;
        }
        .public-ticket-card a {
            color: #8ed0ff;
        }
        .public-ticket-comment {
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid #4f5861;
            border-radius: 4px;
            margin-bottom: 14px;
        }
        .public-ticket-comment .box-header,
        .public-ticket-comment .box-body {
            background: transparent;
            color: inherit;
        }
        .public-ticket-meta p,
        .public-ticket-description,
        .public-ticket-comment .box-body,
        .public-ticket-comment .box-header,
        .public-ticket-card ul,
        .public-ticket-card li,
        .public-ticket-card span {
            color: #e5e7eb;
        }
    </style>
</head>
<body class="public-ticket-portal">
<div class="container public-ticket-shell">
    <div class="row">
        <div class="col-md-12">
            <div class="box box-default public-ticket-card">
                <div class="box-header with-border">
                    <h3 class="box-title">{{ $ticket->ticket_number }} - {{ $ticket->subject }}</h3>
                </div>
                <div class="box-body">
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
                    <div class="row public-ticket-meta">
                        <div class="col-md-6">
                            <p><strong>{{ trans('general.status') }}:</strong> {{ $ticket->status?->name }}</p>
                            <p><strong>{{ trans('admin/tickets/form.priority') }}:</strong> {{ $ticket->priority?->name }}</p>
                            <p><strong>{{ trans('admin/tickets/form.type') }}:</strong> {{ $ticket->type?->name }}</p>
                        </div>
                        <div class="col-md-6">
                            <p><strong>{{ trans('admin/tickets/form.requester') }}:</strong> {{ $ticket->requester_display_name }}</p>
                            <p><strong>{{ trans('general.created_at') }}:</strong> {{ \App\Helpers\Helper::getFormattedDateObject($ticket->created_at, 'datetime', false) }}</p>
                            <p><strong>{{ trans('general.updated_at') }}:</strong> {{ \App\Helpers\Helper::getFormattedDateObject($ticket->updated_at, 'datetime', false) }}</p>
                        </div>
                    </div>
                    <hr>
                    <h4>{{ trans('admin/tickets/form.description') }}</h4>
                    <div class="public-ticket-description">{!! \App\Helpers\Helper::parseEscapedMarkedown($ticket->description) !!}</div>
                    <hr>
                    <h4>{{ trans('admin/tickets/general.messages') }}</h4>
                    @forelse ($publicReplies as $reply)
                        <div class="box box-default public-ticket-comment">
                            <div class="box-header with-border">
                                <strong>{{ $reply->ticket_actor_display_name }}</strong>
                                <span class="pull-right">{{ \App\Helpers\Helper::getFormattedDateObject($reply->created_at, 'datetime', false) }}</span>
                            </div>
                            <div class="box-body">
                                {!! \App\Helpers\Helper::parseEscapedMarkedown($reply->note) !!}
                            </div>
                        </div>
                    @empty
                        <p class="text-muted">{{ trans('admin/tickets/general.empty_comments') }}</p>
                    @endforelse

                    @if ($publicUploads->count() > 0)
                        <hr>
                        <h4>{{ trans('general.files') }}</h4>
                        <ul>
                            @foreach ($publicUploads as $upload)
                                <li>
                                    <a href="{{ route('tickets.portal.files.download', ['tenantPortal' => $tenant->publicHelpdeskRouteKey(), 'ticket' => $ticket, 'token' => request()->route('token'), 'fileId' => $upload->id]) }}">
                                        {{ $upload->filename }}
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    @endif

                    <hr>
                    <form action="{{ route('tickets.portal.reply', ['tenantPortal' => $tenant->publicHelpdeskRouteKey(), 'ticket' => $ticket, 'token' => request()->route('token')]) }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        <div class="form-group">
                            <label for="description">{{ trans('admin/tickets/general.public_reply') }}</label>
                            <textarea class="form-control" name="description" id="description" rows="5" required>{{ old('description') }}</textarea>
                        </div>
                        @if ($tenant->publicHelpdeskAllowsAttachments())
                            <div class="form-group">
                                <label for="file">{{ trans('general.file_upload') }}</label>
                                <input class="form-control" type="file" name="file[]" id="file" multiple>
                            </div>
                        @endif
                        <button class="btn btn-primary">{{ trans('general.save') }}</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>
