@extends('layouts/edit-form', [
    'createText' => trans('admin/documentframeworks/general.create'),
    'updateText' => trans('admin/documentframeworks/general.update'),
    'formAction' => (isset($item->id)) ? route('documentframeworks.update', ['documentframework' => $item->id]) : route('documentframeworks.store'),
    'index_route' => 'documentframeworks.index',
    'item' => $item,
])

@section('inputFields')
    @include('partials.forms.edit.name', ['translated_name' => trans('admin/documentframeworks/table.name'), 'item' => $item])
    @include('partials.forms.edit.company-select', ['translated_name' => trans('general.company'), 'fieldname' => 'company_id', 'item' => $item])
    @include('partials.forms.edit.template-visibility-select', ['translated_name' => trans('general.template_visibility.label'), 'fieldname' => 'visibility_type', 'item' => $item])

    <div class="form-group {{ $errors->has('slug') ? ' has-error' : '' }}">
        <label for="slug" class="col-md-3 control-label">{{ trans('admin/documentframeworks/table.slug') }}</label>
        <div class="col-md-6">
            <input class="form-control" type="text" name="slug" id="slug" value="{{ old('slug', $item->slug) }}" placeholder="gdpr">
            {!! $errors->first('slug', '<span class="alert-msg"><i class="fas fa-times" aria-hidden="true"></i> :message</span>') !!}
        </div>
    </div>

    <div class="form-group {{ $errors->has('description') ? ' has-error' : '' }}">
        <label for="description" class="col-md-3 control-label">{{ trans('admin/documentframeworks/table.description') }}</label>
        <div class="col-md-9">
            <textarea class="form-control" name="description" id="description" rows="4">{{ old('description', $item->description) }}</textarea>
            {!! $errors->first('description', '<span class="alert-msg"><i class="fas fa-times" aria-hidden="true"></i> :message</span>') !!}
        </div>
    </div>

    <div class="form-group {{ $errors->has('sort_order') ? ' has-error' : '' }}">
        <label for="sort_order" class="col-md-3 control-label">{{ trans('admin/documentframeworks/table.sort_order') }}</label>
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
                {{ trans('admin/documentframeworks/table.is_active') }}
            </label>
        </div>
    </div>
@stop
