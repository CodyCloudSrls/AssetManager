@php
    $row = $row ?? [];
    $rowKey = (string) $rowKey;
@endphp

<div class="contract-subscription-row box box-solid" style="margin-bottom: 12px;">
    <div class="box-header with-border">
        <h3 class="box-title">{{ trans('admin/contracts/general.subscription') }}</h3>
        <div class="box-tools pull-right">
            <button type="button" class="btn btn-box-tool remove-subscription-row" title="{{ trans('general.delete') }}">
                <x-icon type="delete" />
            </button>
        </div>
    </div>
    <div class="box-body">
        <input type="hidden" class="subscription-delete-flag" name="subscriptions[{{ $rowKey }}][_delete]" value="{{ $row['_delete'] ?? 0 }}">

        <div class="row">
            <div class="col-md-5 {{ $errors->has('subscriptions.'.$rowKey.'.name') ? 'has-error' : '' }}">
                <label>{{ trans('admin/contracts/general.subscription_name') }}</label>
                <input class="form-control" name="subscriptions[{{ $rowKey }}][name]" type="text" value="{{ $row['name'] ?? '' }}">
                {!! $errors->first('subscriptions.'.$rowKey.'.name', '<span class="help-block cc-field-error"><i class="fas fa-times" aria-hidden="true"></i> :message</span>') !!}
            </div>
            <div class="col-md-3">
                <label>{{ trans('admin/contracts/general.service_code') }}</label>
                <input class="form-control" name="subscriptions[{{ $rowKey }}][service_code]" type="text" value="{{ $row['service_code'] ?? '' }}">
            </div>
            <div class="col-md-2 {{ $errors->has('subscriptions.'.$rowKey.'.quantity') ? 'has-error' : '' }}">
                <label>{{ trans('admin/contracts/general.quantity') }}</label>
                <input class="form-control" name="subscriptions[{{ $rowKey }}][quantity]" type="number" min="0" step="0.0001" value="{{ $row['quantity'] ?? 1 }}">
                {!! $errors->first('subscriptions.'.$rowKey.'.quantity', '<span class="help-block cc-field-error"><i class="fas fa-times" aria-hidden="true"></i> :message</span>') !!}
            </div>
            <div class="col-md-2 {{ $errors->has('subscriptions.'.$rowKey.'.unit_price') ? 'has-error' : '' }}">
                <label>{{ trans('admin/contracts/general.unit_price') }}</label>
                <input class="form-control" name="subscriptions[{{ $rowKey }}][unit_price]" type="number" min="0" step="0.0001" value="{{ $row['unit_price'] ?? '' }}">
                {!! $errors->first('subscriptions.'.$rowKey.'.unit_price', '<span class="help-block cc-field-error"><i class="fas fa-times" aria-hidden="true"></i> :message</span>') !!}
            </div>
        </div>

        <div class="row" style="margin-top: 10px;">
            <div class="col-md-3">
                <label>{{ trans('admin/contracts/general.billing_frequency') }}</label>
                <select class="form-control select2" name="subscriptions[{{ $rowKey }}][billing_frequency]">
                    @foreach ($frequencyOptions as $value => $label)
                        <option value="{{ $value }}" @selected(($row['billing_frequency'] ?? \App\Models\ContractSubscription::FREQUENCY_MONTHLY) === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label>{{ trans('admin/contracts/general.starts_at') }}</label>
                <x-input.datepicker name="subscriptions[{{ $rowKey }}][starts_at]" :value="$row['starts_at'] ?? ''" />
            </div>
            <div class="col-md-3">
                <label>{{ trans('admin/contracts/general.ends_at') }}</label>
                <x-input.datepicker name="subscriptions[{{ $rowKey }}][ends_at]" :value="$row['ends_at'] ?? ''" />
            </div>
            <div class="col-md-3">
                <label style="display:block;">{{ trans('general.active') }}</label>
                <input type="hidden" name="subscriptions[{{ $rowKey }}][is_active]" value="0">
                <label class="form-control">
                    <input type="checkbox" name="subscriptions[{{ $rowKey }}][is_active]" value="1" @checked((bool) ($row['is_active'] ?? true))>
                    {{ trans('general.yes') }}
                </label>
            </div>
        </div>

        <div class="row" style="margin-top: 10px;">
            <div class="col-md-12">
                <label>{{ trans('admin/contracts/general.description') }}</label>
                <textarea class="form-control" name="subscriptions[{{ $rowKey }}][description]" rows="2">{{ $row['description'] ?? '' }}</textarea>
            </div>
        </div>

        <hr>
        <div class="row">
            <div class="col-md-4">
                <label>{{ trans('admin/contracts/general.cost_supplier') }}</label>
                <select class="form-control select2" name="subscriptions[{{ $rowKey }}][cost_supplier_id]">
                    <option value="">{{ trans('general.select_supplier') }}</option>
                    @foreach ($suppliers as $supplier)
                        <option value="{{ $supplier->id }}" @selected((int) ($row['cost_supplier_id'] ?? 0) === (int) $supplier->id)>{{ $supplier->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4">
                <label>{{ trans('admin/contracts/general.cost_description') }}</label>
                <input class="form-control" name="subscriptions[{{ $rowKey }}][cost_description]" type="text" value="{{ $row['cost_description'] ?? '' }}">
            </div>
            <div class="col-md-2">
                <label>{{ trans('admin/contracts/general.cost_quantity') }}</label>
                <input class="form-control" name="subscriptions[{{ $rowKey }}][cost_quantity]" type="number" min="0" step="0.0001" value="{{ $row['cost_quantity'] ?? 1 }}">
            </div>
            <div class="col-md-2">
                <label>{{ trans('admin/contracts/general.unit_cost') }}</label>
                <input class="form-control" name="subscriptions[{{ $rowKey }}][unit_cost]" type="number" min="0" step="0.0001" value="{{ $row['unit_cost'] ?? '' }}">
            </div>
        </div>

        <div class="row" style="margin-top: 10px;">
            <div class="col-md-3">
                <label>{{ trans('admin/contracts/general.cost_frequency') }}</label>
                <select class="form-control select2" name="subscriptions[{{ $rowKey }}][cost_frequency]">
                    @foreach ($frequencyOptions as $value => $label)
                        <option value="{{ $value }}" @selected(($row['cost_frequency'] ?? \App\Models\ContractSubscription::FREQUENCY_MONTHLY) === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label>{{ trans('admin/contracts/general.cost_starts_at') }}</label>
                <x-input.datepicker name="subscriptions[{{ $rowKey }}][cost_starts_at]" :value="$row['cost_starts_at'] ?? ''" />
            </div>
            <div class="col-md-3">
                <label>{{ trans('admin/contracts/general.cost_ends_at') }}</label>
                <x-input.datepicker name="subscriptions[{{ $rowKey }}][cost_ends_at]" :value="$row['cost_ends_at'] ?? ''" />
            </div>
            <div class="col-md-3">
                <label style="display:block;">{{ trans('admin/contracts/general.cost_active') }}</label>
                <input type="hidden" name="subscriptions[{{ $rowKey }}][cost_is_active]" value="0">
                <label class="form-control">
                    <input type="checkbox" name="subscriptions[{{ $rowKey }}][cost_is_active]" value="1" @checked((bool) ($row['cost_is_active'] ?? true))>
                    {{ trans('general.yes') }}
                </label>
            </div>
        </div>
    </div>
</div>
