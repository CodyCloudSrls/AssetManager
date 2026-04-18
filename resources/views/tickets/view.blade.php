@extends('layouts/default')

@section('title')
    {{ trans('admin/tickets/general.view') }} {{ $ticket->ticket_number }}
    @parent
@stop

@section('header_right')
    <x-button.info-panel-toggle/>
@endsection

@section('content')
    <style>
        .ticket-workflow-form {
            margin-bottom: 25px;
        }
        .ticket-workflow-card {
            background: rgba(255, 255, 255, 0.02);
            border: 1px solid var(--table-border-row-color);
            border-radius: 4px;
            margin-bottom: 18px;
            padding: 18px 18px 4px;
        }
        .ticket-workflow-card h4 {
            color: var(--color-fg);
            font-size: 15px;
            font-weight: 600;
            margin: 0 0 14px;
        }
        .ticket-workflow-form .form-group {
            margin-bottom: 16px;
        }
        .ticket-workflow-form label {
            display: block;
            margin-bottom: 6px;
        }
        .ticket-workflow-form .ticket-workflow-actions {
            display: flex;
            align-items: flex-end;
            min-height: 100%;
        }
        .ticket-workflow-form .ticket-workflow-actions .btn {
            min-height: 40px;
        }
        .ticket-workflow-form .ticket-billable {
            align-items: center;
            display: flex;
            gap: 8px;
            min-height: 40px;
            padding-top: 28px;
        }
        .ticket-workflow-form .ticket-billable input {
            margin: 0;
        }
        .ticket-workflow-form .ticket-workflow-notes {
            min-height: 110px;
        }
        .ticket-workflow-form .select2-container {
            width: 100% !important;
        }
        .ticket-workflow-history-table {
            margin-bottom: 0;
        }
        @media (max-width: 991px) {
            .ticket-workflow-form .ticket-billable {
                padding-top: 0;
            }
            .ticket-workflow-form .ticket-workflow-actions {
                margin-top: 4px;
            }
        }
    </style>
    <x-container columns="2">
        <x-page-column class="col-md-9 main-panel">
            <x-tabs>
                <x-slot:tabnav>
                    <x-tabs.details-tab/>
                    <x-tabs.nav-item name="comments" :label="trans('general.notes')" :count="$ticket->comments()->count()" icon_type="support"/>
                    <x-tabs.nav-item name="worklogs" :label="trans('admin/tickets/general.worklogs')" :count="$ticket->worklogs()->count()" icon_type="history"/>
                    <x-tabs.files-tab :item="$ticket" count="{{ $ticket->uploads()->count() }}"/>
                    <x-tabs.history-tab count="{{ $ticket->history()->count() }}" :model="$ticket"/>
                    <x-tabs.upload-tab :item="$ticket"/>
                </x-slot:tabnav>

                <x-slot:tabpanes>
                    <x-tabs.pane name="details">
                        <div class="clearfix visible-lg-block" style="padding: 6px;"></div>
                        <x-page-column class="col-md-8 col-sm-12">
                            <x-page-data>
                                <x-data-row :label="trans('admin/tickets/form.ticket_number')">{{ $ticket->ticket_number }}</x-data-row>
                                <x-data-row :label="trans('general.subject')">{{ $ticket->subject }}</x-data-row>
                                <x-data-row :label="trans('general.company')">{{ $ticket->company?->name }}</x-data-row>
                                <x-data-row :label="trans('general.status')">{{ $ticket->status?->name }}</x-data-row>
                                <x-data-row :label="trans('admin/tickets/form.priority')">{{ $ticket->priority?->name }}</x-data-row>
                                <x-data-row :label="trans('admin/tickets/form.type')">{{ $ticket->type?->name }}</x-data-row>
                                <x-data-row :label="trans('admin/tickets/form.source')">{{ \App\Models\Ticket::sourceOptions()[$ticket->source] ?? $ticket->source }}</x-data-row>
                                <x-data-row :label="trans('admin/tickets/form.requester')">{{ $ticket->requester_display_name }}</x-data-row>
                                <x-data-row :label="trans('admin/tickets/form.assignee')">{{ $ticket->assignee?->display_name }}</x-data-row>
                                <x-data-row :label="trans('admin/tickets/form.related_user')">{{ $ticket->relatedUser?->display_name }}</x-data-row>
                                <x-data-row :label="trans('admin/tickets/form.asset')">
                                    @if ($ticket->asset)
                                        <a href="{{ route('hardware.show', $ticket->asset) }}">{{ $ticket->asset->display_name }}</a>
                                    @endif
                                </x-data-row>
                                <x-data-row :label="trans('admin/tickets/form.document')">
                                    @if ($ticket->document)
                                        <a href="{{ route('documents.show', $ticket->document) }}">{{ $ticket->document->name }}</a>
                                    @endif
                                </x-data-row>
                                <x-data-row :label="trans('admin/tickets/form.description')">
                                    {!! \App\Helpers\Helper::parseEscapedMarkedown($ticket->description) !!}
                                </x-data-row>
                            </x-page-data>
                        </x-page-column>
                    </x-tabs.pane>

                    <x-tabs.pane name="comments">
                        <div class="row">
                            <div class="col-md-12">
                                @can('operate', $ticket)
                                    <form method="POST" action="{{ route('tickets.comments.store', $ticket) }}" class="form-horizontal" style="margin-bottom: 20px;">
                                        @csrf
                                        <div class="form-group {{ $errors->has('note') ? ' has-error' : '' }}">
                                            <label for="note" class="col-md-2 control-label">{{ trans('general.add_note') }}</label>
                                            <div class="col-md-8">
                                                <textarea class="form-control" name="note" id="note" rows="4" required>{{ old('note') }}</textarea>
                                            </div>
                                            <div class="col-md-2">
                                                <button class="btn btn-theme btn-block">{{ trans('general.save') }}</button>
                                            </div>
                                        </div>
                                    </form>
                                @endcan

                                @forelse ($ticket->comments()->with('adminuser')->get() as $comment)
                                    <div class="box box-default">
                                        <div class="box-header with-border">
                                            <h3 class="box-title">
                                                {{ $comment->ticket_actor_display_name }}
                                            </h3>
                                            <div class="box-tools pull-right">
                                                {{ \App\Helpers\Helper::getFormattedDateObject($comment->created_at, 'datetime', false) }}
                                            </div>
                                        </div>
                                        <div class="box-body">
                                            {!! \App\Helpers\Helper::parseEscapedMarkedown($comment->note) !!}
                                        </div>
                                    </div>
                                @empty
                                    <p class="text-muted">{{ trans('admin/tickets/general.empty_comments') }}</p>
                                @endforelse
                            </div>
                        </div>
                    </x-tabs.pane>

                    <x-tabs.pane name="worklogs">
                        <div class="row">
                            <div class="col-md-12">
                                @can('operate', $ticket)
                                    <form method="POST" action="{{ route('tickets.worklogs.store', $ticket) }}" class="ticket-workflow-form">
                                        @csrf
                                        <div class="ticket-workflow-card">
                                            <h4>{{ trans('general.status') }} / {{ trans('admin/tickets/form.assignee') }}</h4>
                                            <div class="row">
                                                <div class="col-md-3 form-group {{ $errors->has('ticket_status_id') ? 'has-error' : '' }}">
                                                    <label for="ticket_status_id">{{ trans('general.status') }}</label>
                                                    <select class="form-control select2" name="ticket_status_id" id="ticket_status_id" required>
                                                        @foreach ($ticketStatuses as $status)
                                                            <option value="{{ $status->id }}" @selected(old('ticket_status_id', $ticket->ticket_status_id) == $status->id)>{{ $status->name }}</option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                                <div class="col-md-3 form-group {{ $errors->has('ticket_priority_id') ? 'has-error' : '' }}">
                                                    <label for="ticket_priority_id">{{ trans('admin/tickets/form.priority') }}</label>
                                                    <select class="form-control select2" name="ticket_priority_id" id="ticket_priority_id">
                                                        <option value="">{{ trans('admin/settings/general.none') }}</option>
                                                        @foreach ($ticketPriorities as $priority)
                                                            <option value="{{ $priority->id }}" @selected(old('ticket_priority_id', $ticket->ticket_priority_id) == $priority->id)>{{ $priority->name }}</option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                                <div class="col-md-3 form-group {{ $errors->has('ticket_type_id') ? 'has-error' : '' }}">
                                                    <label for="ticket_type_id">{{ trans('admin/tickets/form.type') }}</label>
                                                    <select class="form-control select2" name="ticket_type_id" id="ticket_type_id">
                                                        <option value="">{{ trans('admin/settings/general.none') }}</option>
                                                        @foreach ($ticketTypes as $type)
                                                            <option value="{{ $type->id }}" @selected(old('ticket_type_id', $ticket->ticket_type_id) == $type->id)>{{ $type->name }}</option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                                <div class="col-md-3 form-group {{ $errors->has('assignee_id') ? 'has-error' : '' }}">
                                                    <label for="assignee_id">{{ trans('admin/tickets/form.assignee') }}</label>
                                                    <select class="form-control select2" name="assignee_id" id="assignee_id">
                                                        <option value="">{{ trans('general.unassigned') }}</option>
                                                        @foreach ($operationalUsers as $operationalUser)
                                                            <option value="{{ $operationalUser->id }}" @selected(old('assignee_id', $ticket->assignee_id) == $operationalUser->id)>{{ $operationalUser->display_name }}</option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="ticket-workflow-card">
                                            <h4>{{ trans('admin/tickets/form.worklog_category') }} / SLA</h4>
                                            <div class="row">
                                                <div class="col-md-3 form-group {{ $errors->has('first_response_due_at') ? 'has-error' : '' }}">
                                                    <label for="first_response_due_at">{{ trans('admin/tickets/form.first_response_due_at') }}</label>
                                                    <input type="datetime-local" class="form-control" name="first_response_due_at" id="first_response_due_at" value="{{ old('first_response_due_at', optional($ticket->first_response_due_at)->format('Y-m-d\\TH:i')) }}">
                                                </div>
                                                <div class="col-md-3 form-group {{ $errors->has('resolution_due_at') ? 'has-error' : '' }}">
                                                    <label for="resolution_due_at">{{ trans('admin/tickets/form.resolution_due_at') }}</label>
                                                    <input type="datetime-local" class="form-control" name="resolution_due_at" id="resolution_due_at" value="{{ old('resolution_due_at', optional($ticket->resolution_due_at)->format('Y-m-d\\TH:i')) }}">
                                                </div>
                                                <div class="col-md-2 form-group {{ $errors->has('minutes') ? 'has-error' : '' }}">
                                                    <label for="minutes">{{ trans('admin/tickets/form.minutes') }}</label>
                                                    <input type="number" min="1" class="form-control" name="minutes" id="minutes" value="{{ old('minutes') }}">
                                                </div>
                                                <div class="col-md-2 form-group {{ $errors->has('category') ? 'has-error' : '' }}">
                                                    <label for="category">{{ trans('admin/tickets/form.worklog_category') }}</label>
                                                    <select class="form-control select2" name="category" id="category" aria-label="category">
                                                        <option value="">{{ trans('admin/settings/general.none') }}</option>
                                                        @foreach ($worklogCategories as $categoryValue => $categoryLabel)
                                                            <option value="{{ $categoryValue }}" @selected(old('category', \App\Models\TicketWorklog::CATEGORY_ANALYSIS) == $categoryValue)>{{ $categoryLabel }}</option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                                <div class="col-md-2 form-group">
                                                    <label class="ticket-billable">
                                                        <input type="checkbox" name="is_billable" value="1" @checked(old('is_billable'))>
                                                        <span>{{ trans('admin/tickets/form.is_billable') }}</span>
                                                    </label>
                                                </div>
                                            </div>
                                            <div class="row">
                                                <div class="col-md-4 form-group {{ $errors->has('started_at') ? 'has-error' : '' }}">
                                                    <label for="started_at">{{ trans('admin/tickets/form.started_at') }}</label>
                                                    <input type="datetime-local" class="form-control" name="started_at" id="started_at" value="{{ old('started_at') }}">
                                                </div>
                                                <div class="col-md-4 form-group {{ $errors->has('ended_at') ? 'has-error' : '' }}">
                                                    <label for="ended_at">{{ trans('admin/tickets/form.ended_at') }}</label>
                                                    <input type="datetime-local" class="form-control" name="ended_at" id="ended_at" value="{{ old('ended_at') }}">
                                                </div>
                                            </div>
                                        </div>

                                        <div class="ticket-workflow-card">
                                            <h4>{{ trans('general.notes') }}</h4>
                                            <div class="row">
                                                <div class="col-md-10 form-group {{ $errors->has('notes') ? 'has-error' : '' }}">
                                                    <label for="worklog_notes">{{ trans('general.notes') }}</label>
                                                    <textarea class="form-control ticket-workflow-notes" name="notes" id="worklog_notes" rows="4">{{ old('notes') }}</textarea>
                                                </div>
                                                <div class="col-md-2 form-group ticket-workflow-actions">
                                                    <button class="btn btn-theme btn-block">{{ trans('admin/tickets/form.save_update') }}</button>
                                                </div>
                                            </div>
                                        </div>
                                    </form>
                                @endcan

                                <div class="table-responsive">
                                    <table class="table table-striped ticket-workflow-history-table">
                                        <thead>
                                            <tr>
                                                <th>{{ trans('admin/tickets/form.started_at') }}</th>
                                                <th>{{ trans('admin/tickets/form.minutes') }}</th>
                                                <th>{{ trans('admin/tickets/form.worklog_category') }}</th>
                                                <th>{{ trans('general.created_by') }}</th>
                                                <th>{{ trans('general.notes') }}</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @forelse ($ticket->worklogs as $worklog)
                                                <tr>
                                                    <td>{{ \App\Helpers\Helper::getFormattedDateObject($worklog->started_at ?: $worklog->created_at, 'datetime', false) }}</td>
                                                    <td>{{ $worklog->minutes }}</td>
                                                    <td>{{ \App\Models\TicketWorklog::categoryOptions()[$worklog->category] ?? $worklog->category }}</td>
                                                    <td>{{ $worklog->user?->display_name ?? ($worklog->user_id ? trans('admin/tickets/general.internal_user') : trans('general.no_value')) }}</td>
                                                    <td>{!! \App\Helpers\Helper::parseEscapedMarkedownInline($worklog->notes) !!}</td>
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td colspan="5" class="text-muted">{{ trans('admin/tickets/general.empty_worklogs') }}</td>
                                                </tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </x-tabs.pane>

                    <x-tabs.pane name="files">
                        <x-table.files object_type="tickets" :object="$ticket"/>
                    </x-tabs.pane>

                    <x-tabs.pane name="history">
                        <x-table.history :route="route('api.tickets.history', $ticket)" :model="$ticket"/>
                    </x-tabs.pane>
                </x-slot:tabpanes>
            </x-tabs>
        </x-page-column>

        <x-page-column class="col-md-3">
            <x-box class="side-box expanded">
                <div class="box-body">
                    <div class="text-right" style="margin-bottom: 15px;">
                        @can('update', $ticket)
                            <x-button.edit :item="$ticket" :route="route('tickets.edit', $ticket)" />
                        @endcan
                        @can('delete', $ticket)
                            @if ($ticket->isDeletable())
                                <a href="{{ route('tickets.destroy', $ticket) }}"
                                   class="pull-right btn btn-sm btn-danger delete-asset"
                                   style="margin-right: 8px;"
                                   data-toggle="modal"
                                   data-title="{{ trans('general.delete') }}"
                                   data-content="{{ trans('general.sure_to_delete_var', ['item' => $ticket->display_name]) }}"
                                   data-target="#dataConfirmModal"
                                   data-tooltip="true"
                                   data-icon="fa fa-trash"
                                   data-placement="top"
                                   onClick="return false;">
                                    <x-icon type="delete" class="fa-fw" />
                                </a>
                            @endif
                        @endcan
                    </div>

                    <x-page-data>
                        <x-data-row :label="trans('general.created_by')">{{ $ticket->created_by_display_name }}</x-data-row>
                        <x-data-row :label="trans('general.created_at')">{{ \App\Helpers\Helper::getFormattedDateObject($ticket->created_at, 'datetime', false) }}</x-data-row>
                        <x-data-row :label="trans('general.updated_at')">{{ \App\Helpers\Helper::getFormattedDateObject($ticket->updated_at, 'datetime', false) }}</x-data-row>
                        <x-data-row :label="trans('admin/tickets/form.first_response_due_at')">{{ \App\Helpers\Helper::getFormattedDateObject($ticket->first_response_due_at, 'datetime', false) }}</x-data-row>
                        <x-data-row :label="trans('admin/tickets/form.resolution_due_at')">{{ \App\Helpers\Helper::getFormattedDateObject($ticket->resolution_due_at, 'datetime', false) }}</x-data-row>
                    </x-page-data>
                </div>
            </x-box>
        </x-page-column>
    </x-container>
@endsection

@section('moar_scripts')
    @can('files', $ticket)
        @include ('modals.upload-file', ['item_type' => 'tickets', 'item_id' => $ticket->id])
    @endcan
    @include ('partials.bootstrap-table')
@endsection
