@extends('layouts/default')

@section('title')
    {{ trans('admin/documents/general.view') }} {{ $document->name }}
    @parent
@stop

@section('header_right')
    <x-button.info-panel-toggle/>
@endsection

@section('content')
    <x-container columns="2">
        <x-page-column class="col-md-9 main-panel">
            <x-tabs>
                <x-slot:tabnav>
                    <x-tabs.details-tab/>
                    <x-tabs.nav-item
                        name="assignments"
                        label="{{ trans('admin/documents/general.assignments') }}"
                        count="{{ $document->documentAssignments->count() }}"
                        tooltip="{{ trans('admin/documents/general.assignments') }}"
                    />
                    <x-tabs.note-tab :item="$document" count="{{ $document->journal->count() }}"/>
                    <x-tabs.files-tab :item="$document" count="{{ $document->uploads()->count() }}"/>
                    <x-tabs.history-tab count="{{ $document->history()->count() }}" :model="$document"/>
                    <x-tabs.upload-tab :item="$document"/>
                </x-slot:tabnav>

                <x-slot:tabpanes>
                    <x-tabs.pane name="details">
                        <div class="clearfix visible-lg-block" style="padding: 6px;"></div>

                        <x-page-column class="col-md-8 col-sm-12">
                            <x-page-data>
                                <x-data-row :label="trans('admin/documents/form.document_number')">{{ $document->document_number }}</x-data-row>
                                <x-data-row :label="trans('admin/documents/form.document_type')">{{ $document->type?->name }}</x-data-row>
                                <x-data-row :label="trans('admin/documents/form.framework')">{{ $document->framework?->name }}</x-data-row>
                                <x-data-row :label="trans('admin/documents/form.framework_requirements')">
                                    @if ($document->frameworkRequirements->count() > 0)
                                        <ul style="padding-left: 18px; margin-bottom: 0;">
                                            @foreach ($document->frameworkRequirements as $requirement)
                                                <li>
                                                    <a href="{{ route('documentframeworkrequirements.show', $requirement) }}">{{ $requirement->code }} - {{ $requirement->title }}</a>
                                                    <span class="text-muted">({{ \App\Models\Document::coverageRoleOptions()[$requirement->pivot->coverage_role] ?? $requirement->pivot->coverage_role }})</span>
                                                </li>
                                            @endforeach
                                        </ul>
                                    @endif
                                </x-data-row>
                                <x-data-row :label="trans('admin/documents/form.reference')">{{ $document->reference }}</x-data-row>
                                <x-data-row :label="trans('admin/documents/form.version')">{{ $document->version }}</x-data-row>
                                <x-data-row :label="trans('general.status')">{{ \App\Models\Document::getStatusOptions()[$document->status] ?? $document->status }}</x-data-row>
                                <x-data-row :label="trans('general.company')">{{ $document->company?->name }}</x-data-row>
                                <x-data-row :label="trans('admin/documents/form.owner')">{{ $document->owner?->display_name }}</x-data-row>
                                <x-data-row :label="trans('admin/documents/form.classification')">{{ $document->classification }}</x-data-row>
                                <x-data-row :label="trans('admin/documents/form.retention_period')">{{ $document->retention_period }}</x-data-row>
                                <x-data-row :label="trans('admin/documents/form.scope')">{{ $document->scope }}</x-data-row>
                                <x-data-row :label="trans('admin/documents/form.issued_at')">{{ Helper::getFormattedDateObject($document->issued_at, 'date', false) }}</x-data-row>
                                <x-data-row :label="trans('admin/documents/form.effective_at')">{{ Helper::getFormattedDateObject($document->effective_at, 'date', false) }}</x-data-row>
                                <x-data-row :label="trans('admin/documents/form.next_review_at')">{{ Helper::getFormattedDateObject($document->next_review_at, 'date', false) }}</x-data-row>
                                <x-data-row :label="trans('admin/documents/general.assignments')">
                                    <a href="#assignments" data-toggle="tab">
                                        {{ trans('admin/documents/general.assignments') }}
                                        @if ($document->documentAssignments->count() > 0)
                                            ({{ $document->documentAssignments->count() }})
                                        @endif
                                    </a>
                                </x-data-row>
                                <x-data-row :label="trans('admin/documents/form.control_url')">
                                    @if ($document->control_url)
                                        <a href="{{ $document->control_url }}" target="_blank" rel="noopener noreferrer">{{ $document->control_url }}</a>
                                    @endif
                                </x-data-row>
                                <x-data-row :label="trans('admin/documents/form.summary')">
                                    {!! $document->summary ? \App\Helpers\Helper::parseEscapedMarkedown($document->summary) : '' !!}
                                </x-data-row>
                                <x-data-row :label="trans('general.notes')">
                                    {!! $document->notes ? \App\Helpers\Helper::parseEscapedMarkedown($document->notes) : '' !!}
                                </x-data-row>
                            </x-page-data>
                        </x-page-column>
                    </x-tabs.pane>

                    <x-tabs.pane name="assignments">
                        @can('update', $document)
                            @if ($document->company_id)
                                <div class="box box-default">
                                    <div class="box-header with-border">
                                        <h3 class="box-title">{{ trans('admin/documents/general.create_assignment') }}</h3>
                                    </div>
                                    <div class="box-body">
                                        <form method="POST" action="{{ route('documents.assignments.store', $document) }}" class="form-horizontal">
                                            @csrf
                                            @include('documents.partials.assignment-fields', [
                                                'document' => $document,
                                                'documentAssignment' => new \App\Models\DocumentAssignment,
                                                'assignableTypeToken' => old('assignable_type', \App\Models\DocumentAssignment::ASSIGNABLE_USER),
                                            ])

                                            <div class="form-group">
                                                <div class="col-md-7 col-md-offset-3">
                                                    <button class="btn btn-success">
                                                        <x-icon type="checkmark" />
                                                        {{ trans('general.save') }}
                                                    </button>
                                                </div>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            @else
                                <div class="callout callout-warning">
                                    {{ trans('admin/documents/message.assignment_requires_company') }}
                                </div>
                            @endif
                        @endcan

                        <div class="box box-default">
                            <div class="box-header with-border">
                                <h3 class="box-title">{{ trans('admin/documents/general.assignments') }}</h3>
                            </div>
                            <div class="box-body">
                                @include('documents.partials.assignments-table', [
                                    'assignments' => $document->documentAssignments,
                                    'document' => $document,
                                    'showActions' => auth()->user()->can('update', $document),
                                ])
                            </div>
                        </div>
                    </x-tabs.pane>

                    <x-tabs.pane name="notes">
                        <x-table.history
                            :table_header="trans('general.notes')"
                            :model="$document"
                            :route="route('api.activity.index', ['item_id' => $document->id, 'item_type' => 'document', 'action_type' => 'note added'])"
                            :hide_fields="['item', 'target', 'file', 'file_download', 'action_date', 'log_meta', 'quantity']"
                        />
                    </x-tabs.pane>

                    <x-tabs.pane name="files">
                        <x-table.files object_type="documents" :object="$document"/>
                    </x-tabs.pane>

                    <x-tabs.pane name="history">
                        <x-table.history :route="route('api.documents.history', $document)" :model="$document"/>
                    </x-tabs.pane>
                </x-slot:tabpanes>
            </x-tabs>
        </x-page-column>

        <x-page-column class="col-md-3">
            <x-box class="side-box expanded">
                <div class="box-body">
                    <div class="text-right" style="margin-bottom: 15px;">
                        <x-button.edit :item="$document" :route="route('documents.edit', $document)" />
                        @can('delete', $document)
                            @if ($document->isDeletable())
                                <a href="{{ route('documents.destroy', $document) }}"
                                   class="pull-right btn btn-sm btn-danger delete-asset"
                                   style="margin-right: 8px;"
                                   data-toggle="modal"
                                   data-title="{{ trans('general.delete') }}"
                                   data-content="{{ trans('general.sure_to_delete_var', ['item' => $document->name]) }}"
                                   data-target="#dataConfirmModal"
                                   data-tooltip="true"
                                   data-icon="fa fa-trash"
                                   data-placement="top"
                                   onClick="return false;">
                                    <x-icon type="delete" class="fa-fw" />
                                </a>
                            @endif
                        @endcan
                    </div>

                    <x-page-data>
                        <x-data-row :label="trans('general.created_by')">{{ $document->adminuser?->display_name }}</x-data-row>
                        <x-data-row :label="trans('general.created_at')">{{ Helper::getFormattedDateObject($document->created_at, 'datetime', false) }}</x-data-row>
                        <x-data-row :label="trans('general.updated_at')">{{ Helper::getFormattedDateObject($document->updated_at, 'datetime', false) }}</x-data-row>
                    </x-page-data>

                    <div style="margin-top: 15px;">
                        <x-button.note :item="$document" wide="true" />
                    </div>
                </div>
            </x-box>
        </x-page-column>
    </x-container>
@endsection

@section('moar_scripts')
    @can('files', $document)
        @include ('modals.upload-file', ['item_type' => 'documents', 'item_id' => $document->id])
    @endcan

    @include ('modals.add-note', ['type' => 'document', 'id' => $document->id])
    @include ('partials.bootstrap-table')

    <script nonce="{{ csrf_token() }}">
        $(function () {
            function selectedAssignableType() {
                return $('input[name="assignment_assignable_type"]:checked').val();
            }

            function syncAssignableSelectors() {
                const selectedType = selectedAssignableType();
                $('#assignable_user_wrapper').toggle(selectedType === '{{ \App\Models\DocumentAssignment::ASSIGNABLE_USER }}');
                $('#assignable_asset_wrapper').toggle(selectedType === '{{ \App\Models\DocumentAssignment::ASSIGNABLE_ASSET }}');
                $('#assignable_location_id').toggle(selectedType === '{{ \App\Models\DocumentAssignment::ASSIGNABLE_LOCATION }}');
            }

            $('input[name="assignment_assignable_type"]').on('change', syncAssignableSelectors);
            $('#document_assignment_advanced_toggle').on('click', function () {
                $('#document_assignment_advanced_details').slideToggle('fast');
                $('#document_assignment_advanced_icon').toggleClass('fa-caret-right fa-caret-down');
            });
            syncAssignableSelectors();

            @if ($errors->has('assignable_type') || $errors->has('assignable_id') || $errors->has('assignable_user_id') || $errors->has('assignable_asset_id') || $errors->has('assignable_location_id') || $errors->has('relation_type') || $errors->has('status') || $errors->has('issuer_id') || $errors->has('reference_number') || $errors->has('issued_at') || $errors->has('effective_at') || $errors->has('expires_at') || $errors->has('renewal_due_at') || $errors->has('completed_at') || $errors->has('revoked_at') || $errors->has('notes'))
                $('a[href="#assignments"]').tab('show');
            @endif

            @if ($errors->has('issued_at') || $errors->has('completed_at') || $errors->has('revoked_at') || $errors->has('notes'))
                $('#document_assignment_advanced_icon').removeClass('fa-caret-right').addClass('fa-caret-down');
            @endif
        });
    </script>
@endsection
