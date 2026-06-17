{{-- Apply checkbox for a bulk-edit field; only checked fields are written. --}}
<div class="form-group" style="margin-top:-8px;">
    <div class="col-md-8 col-md-offset-3">
        <label class="form-control">
            <input type="checkbox" name="apply_{{ $field }}" value="1" @checked(old('apply_'.$field))>
            {{ trans('general.apply') }}
        </label>
    </div>
</div>
