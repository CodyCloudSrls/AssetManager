@extends('layouts/default')

@section('title')
    {{ trans('erp/notule.title') }}
    @parent
@stop

@section('content')
<form class="form-horizontal" method="post" action="{{ $item->exists ? route('erp.notule.update', $item) : route('erp.notule.store') }}" autocomplete="off">
    @csrf
    @if ($item->exists)
        @method('PUT')
    @endif
    <div class="row">
        <div class="col-md-8 col-md-offset-2">
            <div class="box box-default">
                <div class="box-header with-border"><h2 class="box-title">{{ trans('erp/notule.title') }}</h2></div>
                <div class="box-body">

                    <div class="form-group {{ $errors->has('professional_name') ? 'has-error' : '' }}">
                        <label class="col-md-3 control-label">{{ trans('erp/notule.professional') }}</label>
                        <div class="col-md-7">
                            <input type="text" class="form-control" name="professional_name" value="{{ old('professional_name', $item->professional_name) }}" maxlength="191">
                            <p class="help-block">{{ trans('erp/notule.professional_help') }}</p>
                            {!! $errors->first('professional_name', '<span class="alert-msg">:message</span>') !!}
                        </div>
                    </div>

                    <div class="form-group {{ $errors->has('supplier_id') ? 'has-error' : '' }}">
                        <label class="col-md-3 control-label">{{ trans('erp/notule.supplier') }}</label>
                        <div class="col-md-7">
                            <select name="supplier_id" class="form-control select2" data-placeholder="{{ trans('general.select_supplier') }}">
                                <option value="">{{ trans('general.select_supplier') }}</option>
                                @foreach (\App\Models\Supplier::orderBy('name')->take(500)->get() as $supplier)
                                    <option value="{{ $supplier->id }}" {{ old('supplier_id', $item->supplier_id) == $supplier->id ? 'selected' : '' }}>{{ $supplier->name }}</option>
                                @endforeach
                            </select>
                            {!! $errors->first('supplier_id', '<span class="alert-msg">:message</span>') !!}
                        </div>
                    </div>

                    <div class="form-group {{ $errors->has('description') ? 'has-error' : '' }}">
                        <label class="col-md-3 control-label">{{ trans('erp/notule.description') }}</label>
                        <div class="col-md-7"><input type="text" class="form-control" name="description" value="{{ old('description', $item->description) }}" maxlength="191"></div>
                    </div>

                    <div class="form-group {{ $errors->has('amount') ? 'has-error' : '' }}">
                        <label class="col-md-3 control-label">{{ trans('erp/notule.amount') }} *</label>
                        <div class="col-md-4">
                            <div class="input-group"><span class="input-group-addon">€</span>
                                <input type="number" step="0.01" min="0" class="form-control" name="amount" value="{{ old('amount', $item->amount) }}" required>
                            </div>
                            {!! $errors->first('amount', '<span class="alert-msg">:message</span>') !!}
                        </div>
                    </div>

                    <div class="form-group {{ $errors->has('paid_amount') ? 'has-error' : '' }}">
                        <label class="col-md-3 control-label">{{ trans('erp/notule.paid') }}</label>
                        <div class="col-md-4">
                            <div class="input-group"><span class="input-group-addon">€</span>
                                <input type="number" step="0.01" min="0" class="form-control" name="paid_amount" value="{{ old('paid_amount', $item->paid_amount) }}">
                            </div>
                            <p class="help-block">{{ trans('erp/notule.paid_help') }}</p>
                            {!! $errors->first('paid_amount', '<span class="alert-msg">:message</span>') !!}
                        </div>
                    </div>

                    <div class="form-group {{ $errors->has('competence_date') ? 'has-error' : '' }}">
                        <label class="col-md-3 control-label">{{ trans('erp/notule.competence_date') }}</label>
                        <div class="col-md-4"><input type="date" class="form-control" name="competence_date" value="{{ old('competence_date', optional($item->competence_date)->format('Y-m-d')) }}"></div>
                    </div>

                    <div class="form-group {{ $errors->has('expected_invoice_date') ? 'has-error' : '' }}">
                        <label class="col-md-3 control-label">{{ trans('erp/notule.expected_invoice') }}</label>
                        <div class="col-md-4"><input type="date" class="form-control" name="expected_invoice_date" value="{{ old('expected_invoice_date', optional($item->expected_invoice_date)->format('Y-m-d')) }}"></div>
                    </div>

                    <div class="form-group {{ $errors->has('status') ? 'has-error' : '' }}">
                        <label class="col-md-3 control-label">{{ trans('erp/notule.status') }}</label>
                        <div class="col-md-4">
                            <select name="status" class="form-control">
                                @foreach (\App\Models\Notula::statusOptions() as $value => $label)
                                    <option value="{{ $value }}" {{ old('status', $item->status) == $value ? 'selected' : '' }}>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="form-group {{ $errors->has('paid_at') ? 'has-error' : '' }}">
                        <label class="col-md-3 control-label">{{ trans('erp/notule.paid_at') }}</label>
                        <div class="col-md-4"><input type="date" class="form-control" name="paid_at" value="{{ old('paid_at', optional($item->paid_at)->format('Y-m-d')) }}"></div>
                    </div>

                    <div class="form-group {{ $errors->has('invoice_received') ? 'has-error' : '' }}">
                        <label class="col-md-3 control-label">{{ trans('erp/notule.invoice_received') }}</label>
                        <div class="col-md-7">
                            <input type="hidden" name="invoice_received" value="0">
                            <label class="checkbox-inline" style="padding-left:0;">
                                <input type="checkbox" name="invoice_received" value="1" {{ old('invoice_received', $item->invoice_received) ? 'checked' : '' }}>
                                {{ trans('erp/notule.invoice_received_help') }}
                            </label>
                            {!! $errors->first('invoice_received', '<span class="alert-msg">:message</span>') !!}
                        </div>
                    </div>

                    <div class="form-group {{ $errors->has('notes') ? 'has-error' : '' }}">
                        <label class="col-md-3 control-label">{{ trans('general.notes') }}</label>
                        <div class="col-md-7"><textarea class="form-control" name="notes" rows="3">{{ old('notes', $item->notes) }}</textarea></div>
                    </div>
                </div>
                <div class="box-footer text-right">
                    <a href="{{ route('erp.notule.index') }}" class="btn btn-link">{{ trans('button.cancel') }}</a>
                    <button type="submit" class="btn btn-primary">{{ trans('general.save') }}</button>
                </div>
            </div>
        </div>
    </div>
</form>
@stop
