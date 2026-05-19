@props([
        'action_route',
        'model_name' => 'asset',
        'name' => null,
    ])
@aware(['name'])

@php
    $bulkActionId = Illuminate\Support\Str::camel($name);
@endphp

<div id="{{ $bulkActionId }}BulkActions" style="min-width:0" class="hidden-print">
    <form
            method="POST"
            action="{{ $action_route }}"
            accept-charset="UTF-8"
            class="form-inline"
            id="{{ $bulkActionId }}Form"
    >
        @csrf

        {{--        The sort and order will only be used if the cookie is actually empty (like on first-use)--}}
        <input name="sort" type="hidden" value="{{ "{$model_name}.id" }}">
        <input name="order" type="hidden" value="asc">
        <label for="bulk_actions">
            <span class="sr-only">
                {{ trans('button.bulk_actions') }}
            </span>
        </label>
        <select name="bulk_actions" class="form-control select2" aria-label="bulk_actions" style="width: 320px; max-width: 100%;">
            {{ $slot }}
        </select>

        <button
            type="submit"
            class="btn btn-theme"
            id="{{ $bulkActionId }}Button"
            style="margin-left: 4px;"
            disabled
        >{{ trans('button.go') }}</button>
    </form>
</div>
