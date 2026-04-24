@props([
    'route',
    'name' => null,
    'table_header' => trans('general.history'),
    'model' => null,
    'hide_fields' => [],
])

@php
    $historyName = $name ?? 'history';

    if (($name === null) && $model) {
        $historyName = str_slug(class_basename($model)).'-'.$model->getKey().'-'.str_slug($table_header);
    }
@endphp

<!-- start history tab pane -->
@can('history', $model)
    <x-slot:table_header>
        {{ $table_header }}
    </x-slot:table_header>

    <x-table
        name="{{ $historyName }}"
        :presenter="\App\Presenters\HistoryPresenter::dataTableLayout($hide_fields)"
        show_advanced_search="false"
        api_url="{{ $route }}"
        nosticky="true"
        export_filename="export-history-{{ date('Y-m-d') }}"
    />
@endcan
<!-- end assets tab pane -->
