@extends('layouts/edit-form', [
    'createText' => trans('admin/compliancedomains/general.create'),
    'updateText' => trans('admin/compliancedomains/general.update'),
    'formAction' => (isset($item->id)) ? route('compliancedomains.update', ['compliancedomain' => $item->id]) : route('compliancedomains.store'),
    'index_route' => 'compliancedomains.index',
    'item' => $item,
])

@section('inputFields')
    @include('partials.forms.edit.name', ['translated_name' => trans('admin/compliancedomains/table.name'), 'item' => $item])

    <div class="form-group {{ $errors->has('key') ? ' has-error' : '' }}">
        <label for="key" class="col-md-3 control-label">{{ trans('admin/compliancedomains/table.key') }}</label>
        <div class="col-md-5">
            <input class="form-control" type="text" name="key" id="key" value="{{ old('key', $item->key) }}" {{ ($item->is_system || ($item->exists && \App\Models\DocumentFramework::where('compliance_domain', $item->key)->exists())) ? 'readonly' : '' }}>
            {!! $errors->first('key', '<span class="alert-msg"><i class="fas fa-times" aria-hidden="true"></i> :message</span>') !!}
        </div>
    </div>

    <div class="form-group {{ $errors->has('description') ? ' has-error' : '' }}">
        <label for="description" class="col-md-3 control-label">{{ trans('admin/compliancedomains/table.description') }}</label>
        <div class="col-md-8">
            <textarea class="form-control" name="description" id="description" rows="4">{{ old('description', $item->description) }}</textarea>
            {!! $errors->first('description', '<span class="alert-msg"><i class="fas fa-times" aria-hidden="true"></i> :message</span>') !!}
        </div>
    </div>

    <div class="form-group {{ $errors->has('sort_order') ? ' has-error' : '' }}">
        <label for="sort_order" class="col-md-3 control-label">{{ trans('admin/compliancedomains/table.sort_order') }}</label>
        <div class="col-md-2">
            <input class="form-control" type="number" min="0" name="sort_order" id="sort_order" value="{{ old('sort_order', $item->sort_order ?? 0) }}">
            {!! $errors->first('sort_order', '<span class="alert-msg"><i class="fas fa-times" aria-hidden="true"></i> :message</span>') !!}
        </div>
    </div>

    <div class="form-group">
        <div class="col-md-9 col-md-offset-3">
            <input type="hidden" name="is_active" value="0">
            <label class="form-control">
                <input type="checkbox" value="1" name="is_active" {{ old('is_active', $item->is_active ?? true) ? ' checked="checked"' : '' }} aria-label="is_active">
                {{ trans('admin/compliancedomains/table.is_active') }}
            </label>
        </div>
    </div>
@stop
