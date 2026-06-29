@extends('layouts/default')
@section('title'){{ trans('erp/finanziamenti.title') }} @parent @stop
@section('content')
<form class="form-horizontal" method="post" action="{{ $item->exists ? route('erp.finanziamenti.update', $item) : route('erp.finanziamenti.store') }}">
    @csrf
    @if ($item->exists) @method('PUT') @endif
    <div class="row"><div class="col-md-8 col-md-offset-2">
        <div class="box box-default">
            <div class="box-header with-border"><h2 class="box-title">{{ trans('erp/finanziamenti.title') }}</h2></div>
            <div class="box-body">
                <div class="form-group {{ $errors->has('nome') ? 'has-error' : '' }}">
                    <label class="col-md-3 control-label">{{ trans('erp/finanziamenti.nome') }} *</label>
                    <div class="col-md-7"><input type="text" class="form-control" name="nome" value="{{ old('nome', $item->nome) }}" required>{!! $errors->first('nome', '<span class="alert-msg">:message</span>') !!}</div>
                </div>
                <div class="form-group {{ $errors->has('rata_mensile') ? 'has-error' : '' }}">
                    <label class="col-md-3 control-label">{{ trans('erp/finanziamenti.rata') }} *</label>
                    <div class="col-md-4"><div class="input-group"><span class="input-group-addon">€</span><input type="number" step="0.01" min="0" class="form-control" name="rata_mensile" value="{{ old('rata_mensile', $item->rata_mensile) }}" required></div></div>
                </div>
                <div class="form-group {{ $errors->has('rate_totali') ? 'has-error' : '' }}">
                    <label class="col-md-3 control-label">{{ trans('erp/finanziamenti.rate_totali') }} *</label>
                    <div class="col-md-3"><input type="number" min="0" max="600" class="form-control" name="rate_totali" value="{{ old('rate_totali', $item->rate_totali) }}" required></div>
                </div>
                <div class="form-group {{ $errors->has('rate_pagate') ? 'has-error' : '' }}">
                    <label class="col-md-3 control-label">{{ trans('erp/finanziamenti.rate_pagate') }} *</label>
                    <div class="col-md-3"><input type="number" min="0" max="600" class="form-control" name="rate_pagate" value="{{ old('rate_pagate', $item->rate_pagate) }}" required></div>
                </div>
                <div class="form-group">
                    <label class="col-md-3 control-label">{{ trans('erp/finanziamenti.stato') }}</label>
                    <div class="col-md-4"><select name="stato" class="form-control">
                        <option value="confermato" {{ old('stato', $item->stato) === 'confermato' ? 'selected' : '' }}>{{ trans('erp/finanziamenti.confermato') }}</option>
                        <option value="da_confermare" {{ old('stato', $item->stato) === 'da_confermare' ? 'selected' : '' }}>{{ trans('erp/finanziamenti.da_confermare') }}</option>
                    </select></div>
                </div>
                <div class="form-group">
                    <label class="col-md-3 control-label">{{ trans('general.notes') }}</label>
                    <div class="col-md-7"><textarea class="form-control" name="notes" rows="2">{{ old('notes', $item->notes) }}</textarea></div>
                </div>
            </div>
            <div class="box-footer text-right">
                <a href="{{ route('erp.finanziamenti.index') }}" class="btn btn-link">{{ trans('button.cancel') }}</a>
                <button type="submit" class="btn btn-primary">{{ trans('general.save') }}</button>
            </div>
        </div>
    </div></div>
</form>
@stop
