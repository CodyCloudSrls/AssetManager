@php
    $visibilityOptions = $item::visibilityOptions();
    $selectedVisibility = old($fieldname ?? 'visibility_type', $item->{$fieldname ?? 'visibility_type'} ?? ($item->company_id ? $item::VISIBILITY_PRIVATE : $item::VISIBILITY_GLOBAL));
@endphp
<div id="{{ $fieldname ?? 'visibility_type' }}" class="form-group{{ $errors->has($fieldname ?? 'visibility_type') ? ' has-error' : '' }}">
    <label for="{{ $fieldname ?? 'visibility_type' }}" class="col-md-3 control-label">{{ $translated_name }}</label>
    <div class="col-md-6">
        <select class="form-control select2" name="{{ $fieldname ?? 'visibility_type' }}" id="{{ $fieldname ?? 'visibility_type' }}" aria-label="{{ $fieldname ?? 'visibility_type' }}">
            @foreach ($visibilityOptions as $visibilityValue => $visibilityLabel)
                <option value="{{ $visibilityValue }}" @selected($selectedVisibility === $visibilityValue)>{{ $visibilityLabel }}</option>
            @endforeach
        </select>
        {!! $errors->first($fieldname ?? 'visibility_type', '<span class="alert-msg"><i class="fas fa-times" aria-hidden="true"></i> :message</span>') !!}
        <p class="help-block">{{ trans('general.template_visibility_help') }}</p>
    </div>
</div>
