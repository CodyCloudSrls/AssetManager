@props([
    'route' => route('api.documentframeworkrequirements.index'),
    'name' => 'documentframeworkrequirements',
    'presenter' => \App\Presenters\DocumentFrameworkRequirementPresenter::dataTableLayout(),
    'fixed_right_number' => 1,
    'table_header' => trans('admin/documentframeworks/general.requirements_tab'),
    'buttons' => null,
])

@can('view', \App\Models\DocumentFramework::class)
    <x-slot:table_header>
        {{ $table_header }}
    </x-slot:table_header>

    <x-table
        :$name
        :$presenter
        :$fixed_right_number
        show_column_search="true"
        show_advanced_search="true"
        :$buttons
        api_url="{{ $route }}"
        export_filename="export-{{ str_slug($name) }}-requirements-{{ date('Y-m-d') }}"
    />
@endcan
