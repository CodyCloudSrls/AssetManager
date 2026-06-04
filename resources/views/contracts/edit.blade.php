@extends('layouts/edit-form', [
    'createText' => trans('admin/contracts/general.create'),
    'updateText' => trans('admin/contracts/general.update'),
    'helpTitle' => trans('admin/contracts/general.about_contracts_title'),
    'helpText' => trans('admin/contracts/general.about_contracts_text'),
    'formAction' => isset($item->id) ? route('contracts.update', ['contract' => $item->id]) : route('contracts.store'),
])

@section('inputFields')
@php
    $subscriptionRows = old('subscriptions');
    if (is_null($subscriptionRows)) {
        $subscriptionRows = $contract->exists
            ? $contract->subscriptions->mapWithKeys(function ($subscription) {
                $costLine = $subscription->costLines->first();

                return [$subscription->id => [
                    'name' => $subscription->name,
                    'service_code' => $subscription->service_code,
                    'description' => $subscription->description,
                    'quantity' => $subscription->quantity,
                    'unit_price' => $subscription->unit_price,
                    'billing_frequency' => $subscription->billing_frequency,
                    'starts_at' => optional($subscription->starts_at)->format('Y-m-d'),
                    'ends_at' => optional($subscription->ends_at)->format('Y-m-d'),
                    'is_active' => $subscription->is_active ? 1 : 0,
                    'cost_supplier_id' => $costLine?->supplier_id,
                    'cost_description' => $costLine?->description,
                    'cost_quantity' => $costLine?->quantity,
                    'unit_cost' => $costLine?->unit_cost,
                    'cost_frequency' => $costLine?->cost_frequency,
                    'cost_starts_at' => optional($costLine?->starts_at)->format('Y-m-d'),
                    'cost_ends_at' => optional($costLine?->ends_at)->format('Y-m-d'),
                    'cost_is_active' => $costLine?->is_active ?? 1,
                ]];
            })->all()
            : [];
    }
    if (count($subscriptionRows) === 0) {
        $subscriptionRows = ['new_1' => [
            'name' => '',
            'service_code' => '',
            'description' => '',
            'quantity' => 1,
            'unit_price' => '',
            'billing_frequency' => \App\Models\ContractSubscription::FREQUENCY_MONTHLY,
            'starts_at' => optional($contract->starts_at)->format('Y-m-d'),
            'ends_at' => optional($contract->ends_at)->format('Y-m-d'),
            'is_active' => 1,
            'cost_supplier_id' => '',
            'cost_description' => '',
            'cost_quantity' => 1,
            'unit_cost' => '',
            'cost_frequency' => \App\Models\ContractSubscription::FREQUENCY_MONTHLY,
            'cost_starts_at' => '',
            'cost_ends_at' => '',
            'cost_is_active' => 1,
        ]];
    }
@endphp

@include('partials.forms.edit.name', ['translated_name' => trans('general.name')])
@include('partials.forms.edit.company-select', ['translated_name' => trans('general.company'), 'fieldname' => 'company_id', 'item' => $item])
@include('partials.forms.edit.customer-select', [
    'translated_name' => trans('general.customer'),
    'fieldname' => 'customer_id',
    'item' => $item,
    'selected_id' => old('customer_id', $contract->customer_id),
    'company_id' => old('company_id', $contract->company_id),
])
@include('partials.forms.edit.user-select', ['translated_name' => trans('admin/contracts/general.owner'), 'fieldname' => 'owner_id', 'item' => $item, 'required' => 'false'])

<div class="form-group {{ $errors->has('document_id') ? ' has-error' : '' }}">
    <label for="document_id" class="col-md-3 control-label">{{ trans('general.document') }}</label>
    <div class="col-md-7">
        <select class="form-control select2" name="document_id" id="document_id" aria-label="document_id">
            <option value="">{{ trans('general.select_document') }}</option>
            @foreach ($documents as $document)
                <option value="{{ $document->id }}" @selected((int) old('document_id', $contract->document_id) === (int) $document->id)>
                    {{ $document->name }}{{ $document->document_number ? ' - '.$document->document_number : '' }}
                </option>
            @endforeach
        </select>
        {!! $errors->first('document_id', '<span class="alert-msg" aria-hidden="true"><i class="fas fa-times" aria-hidden="true"></i> :message</span>') !!}
    </div>
</div>

<div class="form-group {{ $errors->has('tenant_service_ids') ? ' has-error' : '' }}">
    <label for="tenant_service_ids" class="col-md-3 control-label">{{ trans('admin/tenantservices/general.field_label') }}</label>
    <div class="col-md-7">
        @php($selectedTenantServiceIdsForForm = array_map('intval', old('tenant_service_ids', $selectedTenantServiceIds ?? [])))
        <input type="hidden" name="tenant_service_ids_present" value="1">
        <select class="form-control select2" multiple name="tenant_service_ids[]" id="tenant_service_ids" aria-label="tenant_service_ids">
            @foreach ($tenantServices as $tenantService)
                <option value="{{ $tenantService->id }}" @selected(in_array((int) $tenantService->id, $selectedTenantServiceIdsForForm, true))>
                    {{ $tenantService->macro_area_label }} - {{ $tenantService->name }}
                </option>
            @endforeach
        </select>
        <p class="help-block">{{ trans('admin/tenantservices/general.contract_field_help') }}</p>
        {!! $errors->first('tenant_service_ids', '<span class="alert-msg" aria-hidden="true"><i class="fas fa-times" aria-hidden="true"></i> :message</span>') !!}
    </div>
</div>

<div class="form-group {{ $errors->has('contract_number') ? ' has-error' : '' }}">
    <label for="contract_number" class="col-md-3 control-label">{{ trans('admin/contracts/general.contract_number') }}</label>
    <div class="col-md-4">
        <input class="form-control" name="contract_number" type="text" id="contract_number" value="{{ old('contract_number', $contract->contract_number) }}">
        {!! $errors->first('contract_number', '<span class="alert-msg" aria-hidden="true"><i class="fas fa-times" aria-hidden="true"></i> :message</span>') !!}
    </div>
</div>

<div class="form-group {{ $errors->has('status') ? ' has-error' : '' }}">
    <label for="status" class="col-md-3 control-label">{{ trans('general.status') }}</label>
    <div class="col-md-4">
        <select class="form-control select2" name="status" id="status" aria-label="status">
            @foreach ($statusOptions as $value => $label)
                <option value="{{ $value }}" @selected(old('status', $contract->status ?: \App\Models\CustomerContract::STATUS_DRAFT) === $value)>{{ $label }}</option>
            @endforeach
        </select>
        {!! $errors->first('status', '<span class="alert-msg" aria-hidden="true"><i class="fas fa-times" aria-hidden="true"></i> :message</span>') !!}
    </div>
</div>

<div class="form-group {{ $errors->has('currency') ? ' has-error' : '' }}">
    <label for="currency" class="col-md-3 control-label">{{ trans('admin/contracts/general.currency') }}</label>
    <div class="col-md-2">
        <input class="form-control" name="currency" type="text" id="currency" maxlength="3" value="{{ old('currency', $contract->currency ?: 'EUR') }}">
        {!! $errors->first('currency', '<span class="alert-msg" aria-hidden="true"><i class="fas fa-times" aria-hidden="true"></i> :message</span>') !!}
    </div>
</div>

@foreach (['signed_at', 'starts_at', 'ends_at', 'renewal_due_at', 'notice_due_at'] as $dateField)
    <div class="form-group {{ $errors->has($dateField) ? ' has-error' : '' }}">
        <label for="{{ $dateField }}" class="col-md-3 control-label">{{ trans('admin/contracts/general.'.$dateField) }}</label>
        <div class="col-md-4">
            <x-input.datepicker name="{{ $dateField }}" :value="old($dateField, optional($contract->{$dateField})->format('Y-m-d'))" placeholder="{{ trans('general.select_date') }}"/>
            {!! $errors->first($dateField, '<span class="alert-msg" aria-hidden="true"><i class="fas fa-times" aria-hidden="true"></i> :message</span>') !!}
        </div>
    </div>
@endforeach

<div class="form-group {{ $errors->has('scope') ? ' has-error' : '' }}">
    <label for="scope" class="col-md-3 control-label">{{ trans('admin/contracts/general.scope') }}</label>
    <div class="col-md-7">
        <textarea class="form-control" name="scope" id="scope" rows="3">{{ old('scope', $contract->scope) }}</textarea>
        {!! $errors->first('scope', '<span class="alert-msg" aria-hidden="true"><i class="fas fa-times" aria-hidden="true"></i> :message</span>') !!}
    </div>
</div>

<fieldset name="contract-subscriptions">
    <x-form.legend>{{ trans('admin/contracts/general.subscriptions') }}</x-form.legend>

    <div class="col-md-10 col-md-offset-1">
        <div id="contract-subscription-rows">
            @foreach ($subscriptionRows as $key => $row)
                @include('contracts.partials.subscription-row', ['rowKey' => $key, 'row' => $row, 'frequencyOptions' => $frequencyOptions, 'suppliers' => $suppliers])
            @endforeach
        </div>
        <button type="button" class="btn btn-sm btn-theme" id="add-subscription-row">
            <i class="fa fa-plus" aria-hidden="true"></i>
            {{ trans('admin/contracts/general.add_subscription') }}
        </button>
    </div>
</fieldset>

@include('partials.forms.edit.notes')

<template id="subscription-row-template">
    @include('contracts.partials.subscription-row', [
        'rowKey' => '__KEY__',
        'row' => [
            'name' => '',
            'service_code' => '',
            'description' => '',
            'quantity' => 1,
            'unit_price' => '',
            'billing_frequency' => \App\Models\ContractSubscription::FREQUENCY_MONTHLY,
            'starts_at' => '',
            'ends_at' => '',
            'is_active' => 1,
            'cost_supplier_id' => '',
            'cost_description' => '',
            'cost_quantity' => 1,
            'unit_cost' => '',
            'cost_frequency' => \App\Models\ContractSubscription::FREQUENCY_MONTHLY,
            'cost_starts_at' => '',
            'cost_ends_at' => '',
            'cost_is_active' => 1,
        ],
        'frequencyOptions' => $frequencyOptions,
        'suppliers' => $suppliers,
    ])
</template>
@stop

@section('moar_scripts')
@parent
<script nonce="{{ csrf_token() }}">
    $(function () {
        $('#add-subscription-row').on('click', function () {
            const key = 'new_' + Date.now();
            const template = $('#subscription-row-template').html().replaceAll('__KEY__', key);
            $('#contract-subscription-rows').append(template);
            $('#contract-subscription-rows .select2').select2();
        });

        $(document).on('click', '.remove-subscription-row', function () {
            const $row = $(this).closest('.contract-subscription-row');
            $row.find('.subscription-delete-flag').val('1');
            $row.hide();
        });

        $('select[name="company_id"]').on('change', function () {
            $('#customer_select').attr('data-company-id', $(this).val()).val(null).trigger('change');
            $('#tenant_service_ids').val(null).trigger('change');
        });
    });
</script>
@stop
