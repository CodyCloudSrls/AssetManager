@extends('layouts/edit-form', [
    'createText' => trans('admin/models/table.create') ,
    'updateText' => trans('admin/models/table.update'),
    'topSubmit' => true,
    'helpPosition' => 'right',
    'helpText' => trans('admin/models/general.about_models_text'),
    'formAction' => (isset($item->id)) ? route('models.update', ['model' => $item->id]) : route('models.store'),
])

{{-- Page content --}}
@section('inputFields')
@include ('partials.forms.edit.name', ['translated_name' => trans('admin/models/table.name'), 'required' => 'true'])
@include ('partials.forms.edit.company-select', ['translated_name' => trans('general.company'), 'fieldname' => 'company_id', 'item' => $item])
@include ('partials.forms.edit.template-visibility-select', ['translated_name' => trans('general.template_visibility.label'), 'fieldname' => 'visibility_type', 'item' => $item])
@include ('partials.forms.edit.category-select', ['translated_name' => trans('admin/categories/general.category_name'), 'fieldname' => 'category_id', 'required' => 'true', 'category_type' => 'asset'])
@include ('partials.forms.edit.manufacturer-select', ['translated_name' => trans('general.manufacturer'), 'fieldname' => 'manufacturer_id'])

{{-- Default customer contract for this model: new assets of the model inherit it. --}}
<div class="form-group {{ $errors->has('customer_contract_id') ? ' has-error' : '' }}">
    <label for="customer_contract_id" class="col-md-3 control-label">{{ trans('admin/models/general.customer_contract') }}</label>
    <div class="col-md-7">
        <select name="customer_contract_id" id="customer_contract_id" class="form-control select2" style="min-width:350px" aria-label="customer_contract_id">
            <option value="">{{ trans('admin/models/general.customer_contract_none') }}</option>
            @foreach (\App\Models\CustomerContract::orderBy('name')->get(['id', 'name', 'contract_number']) as $ct)
                <option value="{{ $ct->id }}" {{ (int) old('customer_contract_id', $item->customer_contract_id) === (int) $ct->id ? 'selected' : '' }}>{{ $ct->name }}{{ $ct->contract_number ? ' ('.$ct->contract_number.')' : '' }}</option>
            @endforeach
        </select>
        <p class="help-block">{{ trans('admin/models/general.customer_contract_help') }}</p>
        {!! $errors->first('customer_contract_id', '<span class="alert-msg" aria-hidden="true"><i class="fas fa-times"></i> :message</span>') !!}
    </div>
</div>

@include ('partials.forms.edit.model_number')
@include ('partials.forms.edit.depreciation')
@include ('partials.forms.edit.minimum_quantity')

<!-- require serial boolean -->
<div class="form-group">
    <label for="require_serial" class="col-md-3 control-label">
        {{ trans('admin/hardware/general.require_serial') }}
    </label>

    <div class="col-md-9">
        <div class="form-inline" style="display: flex; align-items: center; gap: 8px;">
            <input type="checkbox" name="require_serial" value="1" @checked(old('require_serial', $item->require_serial)) id="require_serial" aria-label="require_serial" />
            <a
                    href="#"
                    data-tooltip="true"
                    title="{{ trans('admin/hardware/general.require_serial_help') }}"
                    style="display: inline-flex; align-items: center;"
            >
                <x-icon type="info-circle" />
                <span class="sr-only">{{ trans('admin/hardware/general.require_serial_help') }}</span>
            </a>
        </div>
    </div>
</div>
<!-- EOL -->

<div class="form-group {{ $errors->has('eol') ? ' has-error' : '' }}">
    <label for="eol" class="col-md-3 control-label">{{ trans('general.eol') }}</label>
    <div class="col-md-3 col-sm-4 col-xs-7">
        <div class="input-group">
            <input class="form-control" type="text" name="eol" id="eol" value="{{ old('eol', isset($item->eol)) ? $item->eol : ''  }}" />
            <span class="input-group-addon">
                {{ trans('general.months') }}
            </span>
        </div>
    </div>
    <div class="col-md-9 col-md-offset-3">
        {!! $errors->first('eol', '<span class="alert-msg" aria-hidden="true"><br><i class="fas fa-times"></i> :message</span>') !!}
    </div>
</div>

<!-- Custom Fieldset -->
<!-- If $item->id is null we are cloning the model and we need the $model_id variable -->
@livewire('custom-field-set-default-values-for-model', ["model_id" => $item->id ?? $model_id ?? null])

@include ('partials.forms.edit.notes')
@include ('partials.forms.edit.requestable', ['requestable_text' => trans('admin/models/general.requestable')])
@include ('partials.forms.edit.image-upload', ['image_path' => app('models_upload_path')])

{{-- The model custom fieldset is governed by the category default fieldset: when the
     selected category defines one, the picker is locked (grey, read-only) and inherited.
     The controller enforces the same rule server-side. --}}
@php($ccCategoryFieldsets = \App\Models\Category::where('category_type', 'asset')
    ->whereNotNull('fieldset_id')
    ->with('fieldset:id,name')
    ->get()
    ->mapWithKeys(fn ($c) => [(string) $c->id => ['id' => (int) $c->fieldset_id, 'name' => optional($c->fieldset)->name]]))
@push('js')
<script>
(function () {
    var ccCategoryFieldsets = @json($ccCategoryFieldsets);
    var lockedTpl = @json(trans('admin/models/general.fieldset_from_category'));

    function categorySelect() { return document.querySelector('[name="category_id"]'); }
    function fieldsetSelect() { return document.getElementById('fieldset_id'); }

    function ensureNote(fs) {
        var note = document.getElementById('cc-fieldset-lock-note');
        if (!note) {
            note = document.createElement('p');
            note.id = 'cc-fieldset-lock-note';
            note.className = 'help-block';
            note.style.display = 'none';
            (fs.parentNode || fs).appendChild(note);
        }
        return note;
    }

    function applyLock(setValue) {
        var cat = categorySelect();
        var fs = fieldsetSelect();
        if (!cat || !fs) return;
        var lock = ccCategoryFieldsets[String(cat.value)];
        var note = ensureNote(fs);
        if (lock) {
            if (setValue && String(fs.value) !== String(lock.id)) {
                fs.value = String(lock.id);
                if (window.jQuery) { window.jQuery(fs).trigger('change'); }
            }
            if (!fs.disabled) { fs.disabled = true; }
            if (fs.style.background !== 'rgb(238, 238, 238)') { fs.style.background = '#eee'; }
            var msg = lockedTpl.replace(':name', lock.name || '');
            if (note.textContent !== msg) { note.textContent = msg; }
            if (note.style.display !== '') { note.style.display = ''; }
        } else {
            if (fs.disabled) { fs.disabled = false; }
            if (fs.style.background !== '') { fs.style.background = ''; }
            if (note.style.display !== 'none') { note.style.display = 'none'; }
        }
    }

    function init() {
        applyLock(true);
        var cat = categorySelect();
        if (cat) {
            cat.addEventListener('change', function () { applyLock(true); });
            if (window.jQuery) { window.jQuery(cat).on('change', function () { applyLock(true); }); }
        }
        // Re-assert the lock (disabled/style only) after Livewire re-renders the picker.
        var fs = fieldsetSelect();
        if (fs && window.MutationObserver) {
            new MutationObserver(function () { applyLock(false); }).observe(fs.parentNode || fs, { childList: true, attributes: true, subtree: true });
        }
    }

    if (document.readyState !== 'loading') { init(); } else { document.addEventListener('DOMContentLoaded', init); }
})();
</script>
@endpush

@stop
