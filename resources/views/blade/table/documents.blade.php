@props([
    'route' => route('api.documents.index'),
    'name' => 'documents',
    'presenter' => \App\Presenters\DocumentPresenter::dataTableLayout(),
    'fixed_right_number' => 1,
    'table_header' => trans('general.documents'),
    'buttons' => 'documentButtons',
])

@can('view', \App\Models\Document::class)
    <x-slot:table_header>
        {{ $table_header }}
    </x-slot:table_header>

    <x-table
        :$presenter
        :$fixed_right_number
        show_column_search="true"
        show_advanced_search="true"
        buttons="{{ $buttons }}"
        api_url="{{ $route }}"
        export_filename="export-{{ str_slug($name) }}-documents-{{ date('Y-m-d') }}"
    />
@endcan
