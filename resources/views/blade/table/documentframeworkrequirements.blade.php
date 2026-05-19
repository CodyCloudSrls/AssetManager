@props([
    'route' => route('api.documentframeworkrequirements.index'),
    'name' => 'documentframeworkrequirements',
    'presenter' => \App\Presenters\DocumentFrameworkRequirementPresenter::dataTableLayout(),
    'fixed_right_number' => 1,
    'table_header' => trans('admin/documentframeworks/general.requirements_tab'),
    'buttons' => null,
])

@can('view', \App\Models\DocumentFramework::class)
    @if ($table_header)
        <h3 class="box-title{{ (! auth()->user()?->hasAccess('documentframeworks.edit')) ? ' pull-left' : '' }}">
            {{ $table_header }}
        </h3>
    @endif

    @if (auth()->user()?->hasAccess('documentframeworks.edit'))
        <div id="{{ Illuminate\Support\Str::camel($name) }}Toolbar" class="pull-left" style="min-width:0; padding-top: 10px; margin-right: 8px;">
            <x-table.bulk-actions
                :$name
                action_route="{{ route('documentframeworkrequirements.bulk.edit') }}"
                model_name="document_framework_requirements"
            >
                <option value="edit">{{ trans('general.bulk_edit') }}</option>
            </x-table.bulk-actions>
        </div>
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
