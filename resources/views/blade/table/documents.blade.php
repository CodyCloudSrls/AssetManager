@props([
    'route' => route('api.documents.index'),
    'name' => 'documents',
    'presenter' => \App\Presenters\DocumentPresenter::dataTableLayout(),
    'fixed_right_number' => 1,
    'table_header' => trans('general.documents'),
    'buttons' => 'documentButtons',
])

@can('view', \App\Models\Document::class)
    @php
        $canBulkEditDocuments = auth()->user()?->can('update', \App\Models\Document::class);
        $canBulkDeleteDocuments = auth()->user()?->can('delete', \App\Models\Document::class);
        $canBulkRestoreDocuments = auth()->user()?->can('create', \App\Models\Document::class);
        $showingDeletedDocuments = request('status_type') === 'Deleted';
    @endphp

    <x-slot:table_header>
        {{ $table_header }}
    </x-slot:table_header>

    @if (($showingDeletedDocuments && $canBulkRestoreDocuments) || (! $showingDeletedDocuments && ($canBulkEditDocuments || $canBulkDeleteDocuments)))
        <div id="{{ Illuminate\Support\Str::camel($name) }}Toolbar" class="pull-left" style="min-width:0; padding-top: 10px; margin-right: 8px;">
            <x-table.bulk-actions
                :$name
                action_route="{{ route('documents.bulk.edit') }}"
                model_name="documents"
            >
                @if ($showingDeletedDocuments)
                    <option value="restore">{{ trans('button.restore') }}</option>
                @else
                    @if ($canBulkEditDocuments)
                        <option value="edit">{{ trans('general.bulk_edit') }}</option>
                    @endif
                    @if ($canBulkDeleteDocuments)
                        <option value="delete">{{ trans('general.bulk_delete') }}</option>
                    @endif
                @endif
            </x-table.bulk-actions>
        </div>
    @endif

    <x-table
        :$name
        :$presenter
        :$fixed_right_number
        show_column_search="true"
        show_advanced_search="true"
        buttons="{{ $buttons }}"
        api_url="{{ $route }}"
        export_filename="export-{{ str_slug($name) }}-documents-{{ date('Y-m-d') }}"
    />
@endcan
