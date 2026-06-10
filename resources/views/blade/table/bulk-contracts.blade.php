@aware(['name'])

<form
        method="POST"
        action="{{ route('contracts.bulkedit') }}"
        accept-charset="UTF-8"
        class="form-inline"
        id="{{ Illuminate\Support\Str::camel($name) }}Form"
>
    @csrf
    @can('update', \App\Models\CustomerContract::class)
        <div style="width:100% !important;" class="hidden-print">
            <label for="bulk_actions" class="sr-only">{{ trans('general.bulk_actions') }}</label>
            <select name="bulk_actions" class="form-control select2" style="width: 200px;" aria-label="bulk_actions">
                <option value="edit">{{ trans('general.bulk_edit') }}</option>
            </select>
            <button class="btn btn-theme" id="{{ Illuminate\Support\Str::camel($name) }}Button" disabled>{{ trans('button.go') }}</button>
        </div>
    @endcan
</form>
