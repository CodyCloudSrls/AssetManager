@extends('layouts/edit-form', [
    'createText' => trans('admin/tickets/form.create'),
    'updateText' => trans('admin/tickets/form.update'),
    'topSubmit' => true,
    'formAction' => $ticket->id ? route('tickets.update', $ticket) : route('tickets.store'),
    'index_route' => 'tickets.index',
    'item' => $ticket,
    'container_classes' => 'col-lg-10 col-lg-offset-1 col-md-12 col-md-offset-0 col-sm-12 col-sm-offset-0',
    'options' => [
        'back' => trans('admin/hardware/form.redirect_to_type', ['type' => trans('general.previous_page')]),
        'index' => trans('admin/hardware/form.redirect_to_all', ['type' => trans('general.tickets')]),
        'item' => trans('admin/hardware/form.redirect_to_type', ['type' => trans('general.ticket')]),
    ],
])

@section('inputFields')

    @include ('partials.forms.edit.company-select', ['translated_name' => trans('general.company'), 'fieldname' => 'company_id', 'item' => $ticket])

    <div class="form-group {{ $errors->has('subject') ? ' has-error' : '' }}">
        <label for="subject" class="col-md-3 control-label">{{ trans('general.subject') }}</label>
        <div class="col-md-7">
            <input class="form-control" type="text" name="subject" id="subject" value="{{ old('subject', $ticket->subject) }}" required>
            {!! $errors->first('subject', '<span class="alert-msg"><i class="fas fa-times" aria-hidden="true"></i> :message</span>') !!}
        </div>
    </div>

    <div class="form-group {{ $errors->has('ticket_type_id') ? ' has-error' : '' }}">
        <label for="ticket_type_id" class="col-md-3 control-label">{{ trans('admin/tickets/form.type') }}</label>
        <div class="col-md-4">
            <select class="form-control select2" name="ticket_type_id" id="ticket_type_id" aria-label="ticket_type_id">
                <option value="">{{ trans('admin/tickets/form.select_type') }}</option>
                @foreach ($ticketTypes as $ticketType)
                    <option value="{{ $ticketType->id }}" @selected(old('ticket_type_id', $ticket->ticket_type_id) == $ticketType->id)>{{ $ticketType->name }}</option>
                @endforeach
            </select>
            {!! $errors->first('ticket_type_id', '<span class="alert-msg"><i class="fas fa-times" aria-hidden="true"></i> :message</span>') !!}
        </div>
    </div>

    <div class="form-group {{ $errors->has('ticket_priority_id') ? ' has-error' : '' }}">
        <label for="ticket_priority_id" class="col-md-3 control-label">{{ trans('admin/tickets/form.priority') }}</label>
        <div class="col-md-4">
            <select class="form-control select2" name="ticket_priority_id" id="ticket_priority_id" aria-label="ticket_priority_id">
                <option value="">{{ trans('admin/tickets/form.select_priority') }}</option>
                @foreach ($ticketPriorities as $ticketPriority)
                    <option value="{{ $ticketPriority->id }}" @selected(old('ticket_priority_id', $ticket->ticket_priority_id) == $ticketPriority->id)>{{ $ticketPriority->name }}</option>
                @endforeach
            </select>
            {!! $errors->first('ticket_priority_id', '<span class="alert-msg"><i class="fas fa-times" aria-hidden="true"></i> :message</span>') !!}
        </div>
    </div>

    <div class="form-group {{ $errors->has('ticket_status_id') ? ' has-error' : '' }}">
        <label for="ticket_status_id" class="col-md-3 control-label">{{ trans('general.status') }}</label>
        <div class="col-md-4">
            <select class="form-control select2" name="ticket_status_id" id="ticket_status_id" aria-label="ticket_status_id">
                <option value="">{{ trans('admin/tickets/form.select_status') }}</option>
                @foreach ($ticketStatuses as $ticketStatus)
                    <option value="{{ $ticketStatus->id }}" @selected(old('ticket_status_id', $ticket->ticket_status_id) == $ticketStatus->id)>{{ $ticketStatus->name }}</option>
                @endforeach
            </select>
            {!! $errors->first('ticket_status_id', '<span class="alert-msg"><i class="fas fa-times" aria-hidden="true"></i> :message</span>') !!}
        </div>
    </div>

    <div class="form-group {{ $errors->has('source') ? ' has-error' : '' }}">
        <label for="source" class="col-md-3 control-label">{{ trans('admin/tickets/form.source') }}</label>
        <div class="col-md-4">
            <select class="form-control select2" name="source" id="source" aria-label="source">
                @foreach ($sources as $sourceValue => $sourceLabel)
                    <option value="{{ $sourceValue }}" @selected(old('source', $ticket->source ?: \App\Models\Ticket::SOURCE_INTERNAL) == $sourceValue)>{{ $sourceLabel }}</option>
                @endforeach
            </select>
            {!! $errors->first('source', '<span class="alert-msg"><i class="fas fa-times" aria-hidden="true"></i> :message</span>') !!}
        </div>
    </div>

    <div class="form-group {{ $errors->has('description') ? ' has-error' : '' }}">
        <label for="description" class="col-md-3 control-label">{{ trans('admin/tickets/form.description') }}</label>
        <div class="col-md-7">
            <textarea class="form-control" name="description" id="description" rows="6" required>{{ old('description', $ticket->description) }}</textarea>
            <p class="help-block">{!! trans('general.markdown') !!}</p>
            {!! $errors->first('description', '<span class="alert-msg"><i class="fas fa-times" aria-hidden="true"></i> :message</span>') !!}
        </div>
    </div>

    <div class="col-md-12 col-sm-12">
        <fieldset name="ticket-ownership">
            <x-form.legend>
                <a id="ticket_ownership_toggle">
                    <x-icon type="caret-right" class="fa-fw" id="ticket_ownership_icon" />
                    {{ trans('admin/tickets/form.ownership_section') }}
                </a>
            </x-form.legend>

            <div id="ticket_ownership_details" class="col-md-12" style="display:none">
                <div class="form-group {{ $errors->has('requester_id') ? ' has-error' : '' }}">
                    <label for="requester_id" class="col-md-3 control-label">{{ trans('admin/tickets/form.requester') }}</label>
                    <div class="col-md-7">
                        <select class="js-data-ajax" data-endpoint="users" data-company-id="{{ old('company_id', $ticket->company_id) }}" data-placeholder="{{ trans('general.select_user') }}" name="requester_id" style="width: 100%" id="requester_id" aria-label="requester_id">
                            @if ($linkedRequester)
                                <option value="{{ $linkedRequester->id }}" selected="selected">{{ $linkedRequester->display_name }}</option>
                            @else
                                <option value="">{{ trans('general.select_user') }}</option>
                            @endif
                        </select>
                        {!! $errors->first('requester_id', '<span class="alert-msg"><i class="fas fa-times" aria-hidden="true"></i> :message</span>') !!}
                    </div>
                </div>

                <div class="form-group {{ $errors->has('assignee_id') ? ' has-error' : '' }}">
                    <label for="assignee_id" class="col-md-3 control-label">{{ trans('admin/tickets/form.assignee') }}</label>
                    <div class="col-md-7">
                        <select class="js-data-ajax" data-endpoint="users" data-company-id="{{ old('company_id', $ticket->company_id) }}" data-placeholder="{{ trans('general.select_user') }}" name="assignee_id" style="width: 100%" id="assignee_id" aria-label="assignee_id">
                            @if ($linkedAssignee)
                                <option value="{{ $linkedAssignee->id }}" selected="selected">{{ $linkedAssignee->display_name }}</option>
                            @else
                                <option value="">{{ trans('general.select_user') }}</option>
                            @endif
                        </select>
                        {!! $errors->first('assignee_id', '<span class="alert-msg"><i class="fas fa-times" aria-hidden="true"></i> :message</span>') !!}
                    </div>
                </div>

                <div class="form-group {{ $errors->has('related_user_id') ? ' has-error' : '' }}">
                    <label for="related_user_id" class="col-md-3 control-label">{{ trans('admin/tickets/form.related_user') }}</label>
                    <div class="col-md-7">
                        <select class="js-data-ajax" data-endpoint="users" data-company-id="{{ old('company_id', $ticket->company_id) }}" data-placeholder="{{ trans('general.select_user') }}" name="related_user_id" style="width: 100%" id="related_user_id" aria-label="related_user_id">
                            @if ($linkedRelatedUser)
                                <option value="{{ $linkedRelatedUser->id }}" selected="selected">{{ $linkedRelatedUser->display_name }}</option>
                            @else
                                <option value="">{{ trans('general.select_user') }}</option>
                            @endif
                        </select>
                        {!! $errors->first('related_user_id', '<span class="alert-msg"><i class="fas fa-times" aria-hidden="true"></i> :message</span>') !!}
                    </div>
                </div>
            </div>
        </fieldset>
    </div>

    <div class="col-md-12 col-sm-12">
        <fieldset name="ticket-links">
            <x-form.legend>
                <a id="ticket_links_toggle">
                    <x-icon type="caret-right" class="fa-fw" id="ticket_links_icon" />
                    {{ trans('admin/tickets/form.links_section') }}
                </a>
            </x-form.legend>

            <div id="ticket_links_details" class="col-md-12" style="display:none">
                @include ('partials.forms.edit.asset-select', ['translated_name' => trans('admin/tickets/form.asset'), 'fieldname' => 'asset_id', 'asset' => $linkedAsset, 'company_id' => old('company_id', $ticket->company_id)])

                <div class="form-group {{ $errors->has('document_id') ? ' has-error' : '' }}">
                    <label for="document_id" class="col-md-3 control-label">{{ trans('admin/tickets/form.document') }}</label>
                    <div class="col-md-7">
                        <select class="form-control select2" name="document_id" id="document_id" aria-label="document_id">
                            <option value="">{{ trans('admin/tickets/form.select_document') }}</option>
                            @foreach ($documents as $document)
                                <option value="{{ $document->id }}" @selected(old('document_id', $ticket->document_id) == $document->id)>{{ $document->name }}</option>
                            @endforeach
                        </select>
                        {!! $errors->first('document_id', '<span class="alert-msg"><i class="fas fa-times" aria-hidden="true"></i> :message</span>') !!}
                    </div>
                </div>
            </div>
        </fieldset>
    </div>

@stop

@section('moar_scripts')
<script nonce="{{ csrf_token() }}">
    $(function () {
        function bindToggle(toggleId, contentId, iconId) {
            $(toggleId).on('click', function () {
                $(contentId).slideToggle('fast');
                $(iconId).toggleClass('fa-caret-right fa-caret-down');
            });
        }

        bindToggle('#ticket_ownership_toggle', '#ticket_ownership_details', '#ticket_ownership_icon');
        bindToggle('#ticket_links_toggle', '#ticket_links_details', '#ticket_links_icon');

        $('select[name="company_id"]').on('change', function () {
            var companyId = $(this).val() || '';
            $('#requester_id, #assignee_id, #related_user_id, #assigned_asset_select').attr('data-company-id', companyId).val(null).trigger('change');
        });

        @if ($errors->any())
            $('#ticket_ownership_details, #ticket_links_details').show();
            $('#ticket_ownership_icon, #ticket_links_icon').removeClass('fa-caret-right').addClass('fa-caret-down');
        @endif
    });
</script>
@stop
