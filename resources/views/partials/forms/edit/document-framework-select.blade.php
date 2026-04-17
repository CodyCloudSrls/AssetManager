@php
    $selectedFrameworkId = old($fieldname, (isset($item)) ? $item->{$fieldname} : '');
    $selectedFramework = $selectedFrameworkId ? \App\Models\DocumentFramework::withTrashed()->find($selectedFrameworkId) : null;
@endphp
<div id="{{ $fieldname }}" class="form-group{{ $errors->has($fieldname) ? ' has-error' : '' }}">
    <label for="{{ $fieldname }}_select" class="col-md-3 control-label">{{ $translated_name }}</label>
    <div class="col-md-7">
        <select class="js-data-ajax" data-endpoint="documentframeworks" data-placeholder="{{ trans('admin/documents/form.select_framework') }}" name="{{ $fieldname }}" style="width: 100%" id="{{ $fieldname }}_select" aria-label="{{ $fieldname }}"{{ ((isset($required)) && ($required == 'true')) ? ' required' : '' }}>
            @if ($selectedFramework)
                <option value="{{ $selectedFramework->id }}" selected="selected" role="option" aria-selected="true">
                    {{ $selectedFramework->name }}
                </option>
            @else
                <option value="" role="option">{{ trans('admin/documents/form.select_framework') }}</option>
            @endif
        </select>
    </div>
    <div class="col-md-1 col-sm-1 text-left">
        @can('create', \App\Models\DocumentFramework::class)
            @if ((!isset($hide_new)) || ($hide_new != 'true'))
                <a href='{{ route('modal.show', 'document-framework') }}' data-toggle="modal" data-target="#createModal" data-select='{{ $fieldname }}_select' class="btn btn-sm btn-theme">{{ trans('button.new') }}</a>
            @endif
        @endcan
    </div>
    {!! $errors->first($fieldname, '<div class="col-md-8 col-md-offset-3"><span class="alert-msg"><i class="fas fa-times" aria-hidden="true"></i> :message</span></div>') !!}
</div>
