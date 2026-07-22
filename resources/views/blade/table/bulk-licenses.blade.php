@aware(['name'])

<form
        method="POST"
        action="{{ route('licenses.bulkedit') }}"
        accept-charset="UTF-8"
        class="form-inline"
        id="{{ Illuminate\Support\Str::camel($name) }}Form"
>
    @csrf
    @canany(['update', 'delete'], \App\Models\License::class)
        <div style="width:100% !important;" class="hidden-print">
            <label for="bulk_actions" class="sr-only">{{ trans('general.bulk_actions') }}</label>
            <select name="bulk_actions" class="form-control select2" style="width: 200px;" aria-label="bulk_actions">
                @can('update', \App\Models\License::class)
                    <option value="edit">{{ trans('general.bulk_edit') }}</option>
                @endcan
                @can('delete', \App\Models\License::class)
                    <option value="delete">{{ trans('general.bulk_delete') }}</option>
                @endcan
            </select>
            <button class="btn btn-theme" id="{{ Illuminate\Support\Str::camel($name) }}Button" disabled>{{ trans('button.go') }}</button>
        </div>
    @endcanany
</form>
