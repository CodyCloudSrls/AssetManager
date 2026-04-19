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
@endsection
