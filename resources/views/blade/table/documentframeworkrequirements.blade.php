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

    @if (auth()->user()?->hasAccess('documentframeworks.edit'))
        <x-slot:bulkactions>
            <x-table.bulk-actions
                action_route="{{ route('documentframeworkrequirements.bulk.edit') }}"
                model_name="document_framework_requirements"
            >
                <option value="edit">{{ trans('general.bulk_edit') }}</option>
            </x-table.bulk-actions>
        </x-slot:bulkactions>
    @endif

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
