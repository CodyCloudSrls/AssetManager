@php
    $customerSelectId = $select_id ?? 'customer_select';
    $customerWrapperId = $wrapper_id ?? 'assigned_customer';
    $customerSelectedId = $selected_id ?? null;
@endphp

<div id="{{ $customerWrapperId }}" class="form-group{{ $errors->has($fieldname) ? ' has-error' : '' }}" style="{{ $style ?? '' }}">
    <label for="{{ $customerSelectId }}" class="col-md-3 control-label">{{ $translated_name }}</label>

    <div class="col-md-7">
        <select class="js-data-ajax" data-endpoint="customers" data-placeholder="{{ trans('general.select_customer') }}" name="{{ $fieldname }}" style="width: 100%" id="{{ $customerSelectId }}" aria-label="{{ $fieldname }}" @isset($company_id) data-company-id="{{ $company_id }}" @endisset{{ (isset($multiple) && ($multiple == 'true')) ? " multiple='multiple'" : '' }}{{ (isset($item) && (Helper::checkIfRequired($item, $fieldname))) ? ' required' : '' }}>
            @isset ($selected)
                @foreach ($selected as $customer_id)
                    <option value="{{ $customer_id }}" selected="selected" role="option" aria-selected="true">
                        {{ \App\Models\Customer::find($customer_id)?->name }}
                    </option>
                @endforeach
            @endisset
            @if ($customer_id = old($fieldname, $customerSelectedId ?: ((isset($item)) ? $item->{$fieldname} : '')))
                <option value="{{ $customer_id }}" selected="selected" role="option" aria-selected="true">
                    {{ \App\Models\Customer::find($customer_id)?->name }}
                </option>
            @endif
        </select>
    </div>

    <div class="col-md-1 col-sm-1 text-left">
        @can('create', \App\Models\Customer::class)
            @if ((! isset($hide_new)) || ($hide_new != 'true'))
                <a href="{{ route('customers.create') }}" class="btn btn-sm btn-theme">{{ trans('button.new') }}</a>
            @endif
        @endcan
    </div>

    {!! $errors->first($fieldname, '<div class="col-md-8 col-md-offset-3"><span class="alert-msg" aria-hidden="true"><i class="fas fa-times" aria-hidden="true"></i> :message</span></div>') !!}
</div>
