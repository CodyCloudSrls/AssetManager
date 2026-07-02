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

{{-- Allegati bilancio: PDF ufficiale del bilancio depositato. Meccanismo upload condiviso
     (private_uploads/bilanci/ + Actionlog + integrità). Il pulsante "Estrai dati dal PDF"
     legge il Conto Economico e pre-compila il form (da rivedere prima di salvare). --}}
<div class="row" id="files"><div class="col-md-8 col-md-offset-2">
    <div class="box box-default">
        <div class="box-header with-border"><h2 class="box-title">{{ trans('erp/bilanci.attachments') }}</h2></div>
        <div class="box-body">
            @if ($item->exists)
                @can('files', $item)
                    <form method="POST" action="{{ route('ui.files.store', ['object_type' => 'bilanci', 'id' => $item->id]) }}" enctype="multipart/form-data" style="margin-bottom:14px;">
                        @csrf
                        <div class="form-group">
                            <input type="file" name="file[]" multiple accept=".pdf,.p7m,.p7c,application/pdf" required>
                        </div>
                        <div class="form-group">
                            <input type="text" name="notes" class="form-control" placeholder="{{ trans('general.notes') }}" style="max-width:320px;display:inline-block;">
                            <button type="submit" class="btn btn-primary btn-sm"><i class="fa-solid fa-upload" aria-hidden="true"></i> {{ trans('erp/bilanci.upload') }}</button>
                        </div>
                        <p class="help-block">{{ trans('erp/bilanci.upload_help') }}</p>
                    </form>
                @endcan

                @php($uploads = $item->uploads()->orderByDesc('created_at')->get())
                @if ($uploads->isEmpty())
                    <p class="text-muted">{{ trans('erp/bilanci.no_files') }}</p>
                @else
                    <table class="table table-striped">
                        <thead><tr>
                            <th>{{ trans('general.file_name') }}</th>
                            <th>{{ trans('general.notes') }}</th>
                            <th>{{ trans('general.created_at') }}</th>
                            <th></th>
                        </tr></thead>
                        <tbody>
                        @foreach ($uploads as $file)
                            <tr>
                                <td><a href="{{ route('ui.files.show', ['object_type' => 'bilanci', 'id' => $item->id, 'file_id' => $file->id]) }}"><i class="far fa-file-pdf" aria-hidden="true"></i> {{ $file->filename }}</a></td>
                                <td>{{ $file->note }}</td>
                                <td>{{ \App\Helpers\Helper::getFormattedDateObject($file->created_at, 'datetime', false) }}</td>
                                <td class="text-right">
                                    @can('files', $item)
                                        <form method="POST" action="{{ route('ui.files.destroy', ['object_type' => 'bilanci', 'id' => $item->id, 'file_id' => $file->id]) }}" onsubmit="return confirm('{{ trans('general.sure_to_delete') }}');" style="display:inline;">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="btn btn-danger btn-xs"><i class="fa-solid fa-trash" aria-hidden="true"></i></button>
                                        </form>
                                    @endcan
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>

                    @can('files', $item)
                        {{-- Estrai i numeri del Conto Economico dal PDF depositato e pre-compila il
                             form (da rivedere prima di salvare — nessuna sovrascrittura diretta). --}}
                        <form method="POST" action="{{ route('erp.bilanci.extract', $item) }}" style="display:inline;" onsubmit="return confirm('{{ trans('erp/bilanci.extract_confirm') }}');">
                            @csrf
                            <button type="submit" class="btn btn-default btn-sm"><i class="fa-solid fa-wand-magic-sparkles" aria-hidden="true"></i> {{ trans('erp/bilanci.extract') }}</button>
                        </form>
                        <p class="help-block">{{ trans('erp/bilanci.extract_help') }}</p>
                    @endcan
                @endif
            @else
                <p class="help-block">{{ trans('erp/bilanci.save_first_to_upload') }}</p>
            @endif
        </div>
    </div>
</div></div>
@stop
