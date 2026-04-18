@extends('layouts/edit-form', [
    'createText' => trans('admin/tenants/general.helpdesk.edit'),
    'updateText' => trans('admin/tenants/general.helpdesk.edit'),
    'formAction' => route('tenants.helpdesk.update', $tenant),
    'index_route' => route('tenants.show', $tenant),
    'item' => $rootCompany,
])

@section('inputFields')
    @method('PUT')

    <div class="form-group">
        <label class="col-md-3 control-label">{{ trans('admin/tenants/general.helpdesk.public_url') }}</label>
        <div class="col-md-8">
            <input class="form-control" type="text" readonly value="{{ $tenant->publicHelpdeskUrl() }}">
            <p class="help-block">{{ trans('admin/tenants/general.helpdesk.public_url_help') }}</p>
        </div>
    </div>

    <div class="form-group {{ $errors->has('helpdesk_slug') ? ' has-error' : '' }}">
        <label for="helpdesk_slug" class="col-md-3 control-label">{{ trans('admin/tenants/general.helpdesk.slug') }}</label>
        <div class="col-md-6">
            <input class="form-control" type="text" name="helpdesk_slug" id="helpdesk_slug" value="{{ old('helpdesk_slug', $rootCompany->helpdesk_slug) }}" autocomplete="off">
            <p class="help-block">{{ trans('admin/tenants/general.helpdesk.slug_help') }}</p>
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

    <div class="form-group {{ $errors->has('helpdesk_contact_email') ? ' has-error' : '' }}">
        <label for="helpdesk_contact_email" class="col-md-3 control-label">{{ trans('admin/tenants/general.helpdesk.contact_email') }}</label>
        <div class="col-md-6">
            <input class="form-control" type="email" name="helpdesk_contact_email" id="helpdesk_contact_email" value="{{ old('helpdesk_contact_email', $rootCompany->helpdesk_contact_email ?: $rootCompany->email) }}">
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
        <div class="col-md-8">
            <textarea class="form-control" name="helpdesk_intro" id="helpdesk_intro" rows="5">{{ old('helpdesk_intro', $rootCompany->helpdesk_intro) }}</textarea>
        </div>
    </div>

    <div class="form-group {{ $errors->has('helpdesk_privacy_note') ? ' has-error' : '' }}">
        <label for="helpdesk_privacy_note" class="col-md-3 control-label">{{ trans('admin/tenants/general.helpdesk.privacy_note') }}</label>
        <div class="col-md-8">
            <textarea class="form-control" name="helpdesk_privacy_note" id="helpdesk_privacy_note" rows="5">{{ old('helpdesk_privacy_note', $rootCompany->helpdesk_privacy_note) }}</textarea>
        </div>
    </div>

    <div class="form-group {{ $errors->has('public_ticket_type_ids') ? ' has-error' : '' }}">
        <label class="col-md-3 control-label">{{ trans('admin/tenants/general.helpdesk.public_ticket_types') }}</label>
        <div class="col-md-8">
            @forelse ($availableTicketTypes as $ticketType)
                <label class="form-control" style="margin-bottom: 8px;">
                    <input
                        type="checkbox"
                        name="public_ticket_type_ids[]"
                        value="{{ $ticketType->id }}"
                        {{ in_array($ticketType->id, old('public_ticket_type_ids', $selectedTicketTypeIds), true) ? 'checked="checked"' : '' }}>
                    <span>
                        <strong>{{ $ticketType->name }}</strong>
                        @if ($ticketType->description)
                            <br><span class="text-muted">{{ $ticketType->description }}</span>
                        @endif
                    </span>
                </label>
            @empty
                <p class="text-muted">{{ trans('admin/tenants/general.helpdesk.no_ticket_types_available') }}</p>
            @endforelse
        </div>
    </div>
@stop
