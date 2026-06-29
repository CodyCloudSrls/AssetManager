@extends('layouts/default')
@section('title'){{ trans('erp/previsionali.title') }} @parent @stop
@section('content')
<form class="form-horizontal" method="post" action="{{ $item->exists ? route('erp.previsionali.update', $item) : route('erp.previsionali.store') }}">
    @csrf
    @if ($item->exists) @method('PUT') @endif
    <div class="row"><div class="col-md-8 col-md-offset-2">
        <div class="box box-default">
            <div class="box-header with-border"><h2 class="box-title">{{ trans('erp/previsionali.title') }}</h2></div>
            <div class="box-body">
                <div class="form-group {{ $errors->has('anno') ? 'has-error' : '' }}">
                    <label class="col-md-3 control-label">{{ trans('erp/previsionali.anno') }} *</label>
                    <div class="col-md-3"><input type="number" min="2000" max="2100" class="form-control" name="anno" value="{{ old('anno', $item->anno) }}" required>{!! $errors->first('anno', '<span class="alert-msg">:message</span>') !!}</div>
                </div>
                @foreach (['ricavi', 'ricavi_ricorrente', 'cogs', 'opex', 'personale'] as $field)
                    <div class="form-group {{ $errors->has($field) ? 'has-error' : '' }}">
                        <label class="col-md-3 control-label">{{ trans('erp/previsionali.'.$field) }}</label>
                        <div class="col-md-4"><div class="input-group"><span class="input-group-addon">€</span><input type="number" step="0.01" class="form-control" name="{{ $field }}" value="{{ old($field, $item->$field) }}"></div></div>
                    </div>
                @endforeach
                <div class="form-group">
                    <label class="col-md-3 control-label">{{ trans('general.notes') }}</label>
                    <div class="col-md-7"><textarea class="form-control" name="notes" rows="2">{{ old('notes', $item->notes) }}</textarea></div>
                </div>
            </div>
            <div class="box-footer text-right">
                <a href="{{ route('erp.previsionali.index') }}" class="btn btn-link">{{ trans('button.cancel') }}</a>
                <button type="submit" class="btn btn-primary">{{ trans('general.save') }}</button>
            </div>
        </div>
    </div></div>
</form>
@stop
