@php
    $assignmentCompanyId = $assignmentCompanyId ?? old('company_id', $document->company_id ?? null);
    $oldAssignableType = old('assignment_assignable_type', old('assignable_type', $assignableTypeToken ?? \App\Models\DocumentAssignment::ASSIGNABLE_USER));
    $currentAssignableType = \App\Models\DocumentAssignment::tokenForAssignableClass($oldAssignableType) ?: $oldAssignableType;
    $currentAssignableId = old('assignable_id', $documentAssignment->assignable_id ?? null);
    $selectedUserId = $currentAssignableType === \App\Models\DocumentAssignment::ASSIGNABLE_USER ? $currentAssignableId : null;
    $selectedAssetId = $currentAssignableType === \App\Models\DocumentAssignment::ASSIGNABLE_ASSET ? $currentAssignableId : null;
    $selectedLocationId = $currentAssignableType === \App\Models\DocumentAssignment::ASSIGNABLE_LOCATION ? $currentAssignableId : null;
    $selectedSupplierId = $currentAssignableType === \App\Models\DocumentAssignment::ASSIGNABLE_SUPPLIER ? $currentAssignableId : null;
    $showAssignmentAdvanced = $showAssignmentAdvanced
        ?? $errors->has('issued_at')
        || $errors->has('completed_at')
        || $errors->has('revoked_at')
        || $errors->has('notes');
@endphp

<div class="form-group {{ $errors->has('assignable_type') ? ' has-error' : '' }}">
    <label for="assignment_assignable_type_user" class="col-md-3 control-label">{{ trans('admin/documents/form.link_to') }}</label>
    <div class="col-md-8">
        <div class="btn-group" data-toggle="buttons" id="document_assignment_target_selector">
            @foreach (\App\Models\DocumentAssignment::assignableTypeOptions() as $assignableTypeValue => $assignableTypeLabel)
                <label class="btn btn-theme{{ $currentAssignableType === $assignableTypeValue ? ' active' : '' }}">
                    <input
                        type="radio"
                        name="assignment_assignable_type"
                        id="assignment_assignable_type_{{ $assignableTypeValue }}"
                        value="{{ $assignableTypeValue }}"
                        aria-label="assignment_assignable_type_{{ $assignableTypeValue }}"
                        {{ $currentAssignableType === $assignableTypeValue ? 'checked' : '' }}
                    >
                    @if ($assignableTypeValue === \App\Models\DocumentAssignment::ASSIGNABLE_USER)
                        <x-icon type="user" />
                    @elseif ($assignableTypeValue === \App\Models\DocumentAssignment::ASSIGNABLE_ASSET)
                        <x-icon type="asset" />
                    @elseif ($assignableTypeValue === \App\Models\DocumentAssignment::ASSIGNABLE_LOCATION)
                        <x-icon type="location" />
                    @else
                        <x-icon type="supplier" />
                    @endif
                    {{ $assignableTypeLabel }}
                </label>
            @endforeach
        </div>
        {!! $errors->first('assignable_type', '<span class="alert-msg" style="display:block; margin-top:8px;"><i class="fas fa-times" aria-hidden="true"></i> :message</span>') !!}
    </div>
</div>

@include('partials.forms.edit.user-select', [
    'translated_name' => trans('admin/documents/form.assignable_target'),
    'fieldname' => 'assignment_assignable_user_id',
    'selected_id' => $selectedUserId,
    'select_id' => 'assignable_user_id_select',
    'wrapper_id' => 'assignable_user_wrapper',
    'company_id' => $assignmentCompanyId,
    'hide_new' => 'true',
    'style' => $currentAssignableType === \App\Models\DocumentAssignment::ASSIGNABLE_USER ? '' : 'display:none;',
])

@include('partials.forms.edit.asset-select', [
    'translated_name' => trans('admin/documents/form.assignable_target'),
    'fieldname' => 'assignment_assignable_asset_id',
    'selected_id' => $selectedAssetId,
    'select_id' => 'assignable_asset_id_select',
    'asset_selector_div_id' => 'assignable_asset_wrapper',
    'company_id' => $assignmentCompanyId,
    'style' => $currentAssignableType === \App\Models\DocumentAssignment::ASSIGNABLE_ASSET ? '' : 'display:none;',
])

@include('partials.forms.edit.location-select', [
    'translated_name' => trans('admin/documents/form.assignable_target'),
    'fieldname' => 'assignment_assignable_location_id',
    'selected_id' => $selectedLocationId,
    'company_id' => $assignmentCompanyId,
    'hide_new' => 'true',
    'style' => $currentAssignableType === \App\Models\DocumentAssignment::ASSIGNABLE_LOCATION ? '' : 'display:none;',
])

@include('partials.forms.edit.supplier-select', [
    'translated_name' => trans('admin/documents/form.assignable_target'),
    'fieldname' => 'assignment_assignable_supplier_id',
    'selected_id' => $selectedSupplierId,
    'select_id' => 'assignable_supplier_id_select',
    'wrapper_id' => 'assignable_supplier_wrapper',
    'company_id' => $assignmentCompanyId,
    'hide_new' => 'true',
    'style' => $currentAssignableType === \App\Models\DocumentAssignment::ASSIGNABLE_SUPPLIER ? '' : 'display:none;',
])

<div class="form-group">
    <label for="assignment_relation_type" class="col-md-3 control-label">{{ trans('admin/documents/form.assignment_relation') }}</label>
    <div class="col-md-3 {{ $errors->has('relation_type') ? ' has-error' : '' }}">
        <select class="form-control select2" name="assignment_relation_type" id="assignment_relation_type" aria-label="assignment_relation_type" required>
            @foreach (\App\Models\DocumentAssignment::relationTypeOptions() as $relationTypeValue => $relationTypeLabel)
                <option value="{{ $relationTypeValue }}" @selected(old('assignment_relation_type', old('relation_type', $documentAssignment->relation_type ?? \App\Models\DocumentAssignment::RELATION_ISSUED_TO)) === $relationTypeValue)>{{ $relationTypeLabel }}</option>
            @endforeach
        </select>
        {!! $errors->first('relation_type', '<span class="alert-msg"><i class="fas fa-times" aria-hidden="true"></i> :message</span>') !!}
    </div>

    <label for="assignment_status" class="col-md-1 control-label">{{ trans('admin/documents/form.assignment_status') }}</label>
    <div class="col-md-3 {{ $errors->has('status') ? ' has-error' : '' }}">
        <select class="form-control select2" name="assignment_status" id="assignment_status" aria-label="assignment_status" required>
            @foreach (\App\Models\DocumentAssignment::statusOptions() as $assignmentStatusValue => $assignmentStatusLabel)
                <option value="{{ $assignmentStatusValue }}" @selected(old('assignment_status', old('status', $documentAssignment->status ?? \App\Models\DocumentAssignment::STATUS_ACTIVE)) === $assignmentStatusValue)>{{ $assignmentStatusLabel }}</option>
            @endforeach
        </select>
        {!! $errors->first('status', '<span class="alert-msg"><i class="fas fa-times" aria-hidden="true"></i> :message</span>') !!}
    </div>
</div>

<div class="form-group">
    <label for="assignment_reference_number" class="col-md-3 control-label">{{ trans('admin/documents/form.assignment_reference_number') }}</label>
    <div class="col-md-3 {{ $errors->has('reference_number') ? ' has-error' : '' }}">
        <input class="form-control" type="text" name="assignment_reference_number" id="assignment_reference_number" value="{{ old('assignment_reference_number', old('reference_number', $documentAssignment->reference_number)) }}">
        {!! $errors->first('reference_number', '<span class="alert-msg"><i class="fas fa-times" aria-hidden="true"></i> :message</span>') !!}
    </div>

    <label for="issuer_id_select" class="col-md-1 control-label">{{ trans('admin/documents/form.assignment_issuer') }}</label>
    <div class="col-md-4">
        <select class="js-data-ajax" data-endpoint="users" data-placeholder="{{ trans('general.select_user') }}" name="assignment_issuer_id" style="width: 100%" id="issuer_id_select" aria-label="issuer_id" data-company-id="{{ $assignmentCompanyId }}">
            @if ($issuerId = old('assignment_issuer_id', old('issuer_id', $documentAssignment->issuer_id ?? '')))
                <option value="{{ $issuerId }}" selected="selected" role="option" aria-selected="true">
                    {{ (\App\Models\User::find($issuerId)) ? \App\Models\User::find($issuerId)->present()->fullName : '' }}
                </option>
            @else
                <option value="" role="option">{{ trans('general.select_user') }}</option>
            @endif
        </select>
        {!! $errors->first('issuer_id', '<span class="alert-msg"><i class="fas fa-times" aria-hidden="true"></i> :message</span>') !!}
    </div>
</div>

<div class="form-group {{ $errors->has('effective_at') ? ' has-error' : '' }}">
    <label for="assignment_effective_at" class="col-md-3 control-label">{{ trans('admin/documents/form.assignment_effective_at') }}</label>
    <div class="col-md-4">
        <x-input.datepicker name="assignment_effective_at" :value="old('assignment_effective_at', old('effective_at', optional($documentAssignment->effective_at)->format('Y-m-d')))" placeholder="{{ trans('general.select_date') }}"/>
        {!! $errors->first('effective_at', '<span class="alert-msg"><i class="fas fa-times" aria-hidden="true"></i> :message</span>') !!}
    </div>
</div>

<div class="form-group {{ $errors->has('expires_at') ? ' has-error' : '' }}">
    <label for="assignment_expires_at" class="col-md-3 control-label">{{ trans('admin/documents/form.assignment_expires_at') }}</label>
    <div class="col-md-4">
        <x-input.datepicker name="assignment_expires_at" :value="old('assignment_expires_at', old('expires_at', optional($documentAssignment->expires_at)->format('Y-m-d')))" placeholder="{{ trans('general.select_date') }}"/>
        {!! $errors->first('expires_at', '<span class="alert-msg"><i class="fas fa-times" aria-hidden="true"></i> :message</span>') !!}
    </div>
</div>

<div class="form-group {{ $errors->has('renewal_due_at') ? ' has-error' : '' }}">
    <label for="assignment_renewal_due_at" class="col-md-3 control-label">{{ trans('admin/documents/form.assignment_renewal_due_at') }}</label>
    <div class="col-md-4">
        <x-input.datepicker name="assignment_renewal_due_at" :value="old('assignment_renewal_due_at', old('renewal_due_at', optional($documentAssignment->renewal_due_at)->format('Y-m-d')))" placeholder="{{ trans('general.select_date') }}"/>
        {!! $errors->first('renewal_due_at', '<span class="alert-msg"><i class="fas fa-times" aria-hidden="true"></i> :message</span>') !!}
    </div>
</div>

<div class="col-md-12 col-sm-12">
    <fieldset name="assignment-advanced-details">
        <x-form.legend>
            <a id="document_assignment_advanced_toggle">
                <x-icon type="caret-right" class="fa-fw" id="document_assignment_advanced_icon" />
                {{ trans('admin/documents/form.assignment_optional_section') }}
            </a>
        </x-form.legend>

        <div id="document_assignment_advanced_details" class="col-md-12" style="{{ $showAssignmentAdvanced ? '' : 'display:none' }}">
            <div class="form-group {{ $errors->has('issued_at') ? ' has-error' : '' }}">
                <label for="assignment_issued_at" class="col-md-3 control-label">{{ trans('admin/documents/form.assignment_issued_at') }}</label>
                <div class="col-md-4">
                    <x-input.datepicker name="assignment_issued_at" :value="old('assignment_issued_at', old('issued_at', optional($documentAssignment->issued_at)->format('Y-m-d')))" placeholder="{{ trans('general.select_date') }}"/>
                    {!! $errors->first('issued_at', '<span class="alert-msg"><i class="fas fa-times" aria-hidden="true"></i> :message</span>') !!}
                </div>
            </div>

            <div class="form-group {{ $errors->has('completed_at') ? ' has-error' : '' }}">
                <label for="assignment_completed_at" class="col-md-3 control-label">{{ trans('admin/documents/form.assignment_completed_at') }}</label>
                <div class="col-md-4">
                    <x-input.datepicker name="assignment_completed_at" :value="old('assignment_completed_at', old('completed_at', optional($documentAssignment->completed_at)->format('Y-m-d')))" placeholder="{{ trans('general.select_date') }}"/>
                    {!! $errors->first('completed_at', '<span class="alert-msg"><i class="fas fa-times" aria-hidden="true"></i> :message</span>') !!}
                </div>
            </div>

            <div class="form-group {{ $errors->has('revoked_at') ? ' has-error' : '' }}">
                <label for="assignment_revoked_at" class="col-md-3 control-label">{{ trans('admin/documents/form.assignment_revoked_at') }}</label>
                <div class="col-md-4">
                    <x-input.datepicker name="assignment_revoked_at" :value="old('assignment_revoked_at', old('revoked_at', optional($documentAssignment->revoked_at)->format('Y-m-d')))" placeholder="{{ trans('general.select_date') }}"/>
                    {!! $errors->first('revoked_at', '<span class="alert-msg"><i class="fas fa-times" aria-hidden="true"></i> :message</span>') !!}
                </div>
            </div>

            <div class="form-group {{ $errors->has('notes') ? ' has-error' : '' }}">
                <label for="assignment_notes" class="col-md-3 control-label">{{ trans('admin/documents/form.assignment_notes') }}</label>
                <div class="col-md-7">
                    <textarea class="form-control" name="assignment_notes" id="assignment_notes" rows="4">{{ old('assignment_notes', old('notes', $documentAssignment->notes)) }}</textarea>
                    <p class="help-block">{!! trans('general.markdown') !!}</p>
                    {!! $errors->first('notes', '<span class="alert-msg"><i class="fas fa-times" aria-hidden="true"></i> :message</span>') !!}
                </div>
            </div>
        </div>
    </fieldset>
</div>
