@extends('layouts/default')

@section('title'){{ trans('erp/bilanci.title') }} @parent @stop

@section('content')
<form class="form-horizontal" method="post" action="{{ $item->exists ? route('erp.bilanci.update', $item) : route('erp.bilanci.store') }}">
    @csrf
    @if ($item->exists) @method('PUT') @endif
    <div class="row"><div class="col-md-8 col-md-offset-2">
        <div class="box box-default">
            <div class="box-header with-border"><h2 class="box-title">{{ trans('erp/bilanci.title') }}</h2></div>
            <div class="box-body">
                <div class="form-group {{ $errors->has('anno') ? 'has-error' : '' }}">
                    <label class="col-md-3 control-label">{{ trans('erp/bilanci.anno') }} *</label>
                    <div class="col-md-3"><input type="number" min="2000" max="2100" class="form-control" name="anno" value="{{ old('anno', $item->anno) }}" required>{!! $errors->first('anno', '<span class="alert-msg">:message</span>') !!}</div>
                </div>
                @foreach (['ricavi', 'costi', 'costo_personale', 'ammortamenti', 'utile', 'imposte'] as $field)
                    <div class="form-group {{ $errors->has($field) ? 'has-error' : '' }}">
                        <label class="col-md-3 control-label">{{ trans('erp/bilanci.'.$field) }}</label>
                        <div class="col-md-4"><div class="input-group"><span class="input-group-addon">€</span><input type="number" step="0.01" class="form-control" name="{{ $field }}" value="{{ old($field, $item->$field) }}"></div></div>
                    </div>
                @endforeach
                <div class="form-group">
                    <label class="col-md-3 control-label">{{ trans('erp/bilanci.is_deposited') }}</label>
                    <div class="col-md-7"><label><input type="hidden" name="is_deposited" value="0"><input type="checkbox" name="is_deposited" value="1" {{ old('is_deposited', $item->is_deposited ?? true) ? 'checked' : '' }}> {{ trans('erp/bilanci.is_deposited_help') }}</label></div>
                </div>
                <div class="form-group">
                    <label class="col-md-3 control-label">{{ trans('general.notes') }}</label>
                    <div class="col-md-7"><textarea class="form-control" name="notes" rows="2">{{ old('notes', $item->notes) }}</textarea></div>
                </div>
            </div>
            <div class="box-footer text-right">
                <a href="{{ route('erp.bilanci.index') }}" class="btn btn-link">{{ trans('button.cancel') }}</a>
                <button type="submit" class="btn btn-primary">{{ trans('general.save') }}</button>
            </div>
        </div>
    </div></div>
</form>
@stop
