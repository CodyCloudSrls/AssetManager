<!-- begin redirect submit options -->
@props([
    'object',
    'object_type' => '',
    'table_header' => trans('general.files'),
])
@can('viewFiles', $object)

<x-slot:table_header>
    {{ $table_header }}
</x-slot:table_header>

@if(isset($object))
    @can('files', $object)
        <form method="POST" action="{{ route('ui.files.bulkdestroy', ['object_type' => $object_type, 'id' => $object->id]) }}" id="{{ $object_type }}-bulkDeleteFilesForm" class="hidden-print" style="margin-bottom: 10px;">
            @csrf
            <button type="submit" class="btn btn-danger btn-sm" id="{{ $object_type }}-bulkDeleteFilesButton" disabled>
                <x-icon type="delete" class="fa-fw" />
                {{ trans('general.bulk_delete') }}
            </button>
        </form>
    @endcan
    <table
        data-columns="{{ \App\Presenters\UploadedFilesPresenter::dataTableLayout() }}"
        data-cookie-id-table="{{ $object_type }}-FileUploadsTable"
        data-id-table="{{ $object_type }}-FileUploadsTable"
        id="{{ $object_type }}-FileUploadsTable"
        data-side-pagination="server"
        data-pagination="true"
        data-sort-order="desc"
        data-sort-name="created_at"
        data-show-custom-view="true"
        data-custom-view="customViewFormatter"
        data-show-advanced-search="false"
        data-show-custom-view-button="true"
        data-url="{{ route('api.files.index', ['object_type' => $object_type, 'id' => $object->id]) }}"
        class="table table-striped snipe-table"
        data-export-options='{
                "fileName": "export-uploads-{{ str_slug($object->name) }}-{{ date('Y-m-d') }}",
                "ignoreColumn": ["image","delete","download","icon"]
                }'>
    </table>

    <x-gallery-card/>

    @can('files', $object)
        <div class="modal fade" id="editFileNoteModal" tabindex="-1" role="dialog" aria-labelledby="editFileNoteModalLabel">
            <div class="modal-dialog" role="document">
                <form id="editFileNoteForm" method="POST" action="" accept-charset="UTF-8">
                    @csrf
                    @method('PATCH')
                    <div class="modal-content">
                        <div class="modal-header">
                            <button type="button" class="close" data-dismiss="modal" aria-label="{{ trans('button.cancel') }}"><span aria-hidden="true">&times;</span></button>
                            <h4 class="modal-title" id="editFileNoteModalLabel">{{ trans('general.edit') }} &mdash; {{ trans('general.notes') }}</h4>
                        </div>
                        <div class="modal-body">
                            <label for="editFileNoteText" class="control-label" id="editFileNoteFilename"></label>
                            <textarea class="form-control" name="notes" id="editFileNoteText" rows="4" maxlength="65535"></textarea>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-default" data-dismiss="modal">{{ trans('button.cancel') }}</button>
                            <button type="submit" class="btn btn-primary">{{ trans('general.save') }}</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <script nonce="{{ csrf_token() }}">
            $(function () {
                $(document).on('click', '.edit-file-note', function () {
                    var $btn = $(this);
                    var action = '{{ url('/') }}/' + $btn.attr('data-object-type') + '/' + $btn.attr('data-object-id') + '/files/' + $btn.attr('data-file-id');
                    $('#editFileNoteForm').attr('action', action);
                    $('#editFileNoteText').val($btn.attr('data-note') || '');
                });

                var $filesTable = $('#{{ $object_type }}-FileUploadsTable');
                var $bulkForm = $('#{{ $object_type }}-bulkDeleteFilesForm');
                var $bulkButton = $('#{{ $object_type }}-bulkDeleteFilesButton');

                var selectedFileIds = function () {
                    var ids = [];
                    try {
                        ids = ($filesTable.bootstrapTable('getSelections') || []).map(function (row) { return row.id; });
                    } catch (error) {
                        ids = [];
                    }
                    // Fallback: some bootstrap-table builds don't bubble check.bs.table,
                    // so read the checked rows straight from the DOM via their row index.
                    if (ids.length === 0) {
                        var data = [];
                        try { data = $filesTable.bootstrapTable('getData') || []; } catch (e) { data = []; }
                        $filesTable.find('tbody input[type="checkbox"]:checked').each(function () {
                            var idx = $(this).closest('tr').data('index');
                            if (typeof idx !== 'undefined' && data[idx] && data[idx].id) {
                                ids.push(data[idx].id);
                            }
                        });
                    }
                    return ids;
                };

                var refreshBulkDelete = function () {
                    var ids = selectedFileIds();
                    $bulkButton.prop('disabled', ids.length === 0);
                    $bulkForm.find('input[name="ids[]"]').remove();
                    ids.forEach(function (id) {
                        $bulkForm.append('<input type="hidden" name="ids[]" value="' + id + '">');
                    });
                };

                $filesTable.on('check.bs.table uncheck.bs.table check-all.bs.table uncheck-all.bs.table load-success.bs.table page-change.bs.table', refreshBulkDelete);
                // Native-change fallback (covers builds where the bs.table check events don't fire)
                $filesTable.on('change', 'input[type="checkbox"]', function () { setTimeout(refreshBulkDelete, 0); });

                $bulkForm.on('submit', function (event) {
                    refreshBulkDelete();
                    if (selectedFileIds().length === 0) {
                        event.preventDefault();
                        return;
                    }
                    if (! window.confirm('{{ trans('general.file_upload_status.confirm_delete') }}')) {
                        event.preventDefault();
                    }
                });
            });
        </script>
    @endcan
@endif
@endcan
