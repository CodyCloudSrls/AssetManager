@extends('layouts/default')

@section('title')
    {{ $requirement->code }} - {{ $requirement->title }}
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
                    <x-tabs.nav-item name="documents" :label="trans('general.documents')" :count="$requirement->documents_count" icon="fa-regular fa-file-lines"/>
                </x-slot:tabnav>

                <x-slot:tabpanes>
                    <x-tabs.pane name="details">
                        <div class="clearfix visible-lg-block" style="padding: 6px;"></div>

                        <x-page-column class="col-md-8 col-sm-12">
                            <x-page-data>
                                <x-data-row :label="trans('admin/documentframeworkrequirements/table.framework')">
                                    @if ($requirement->framework)
                                        <a href="{{ route('documentframeworks.show', $requirement->framework) }}">{{ $requirement->framework->name }}</a>
                                    @endif
                                </x-data-row>
                                <x-data-row :label="trans('admin/documentframeworkrequirements/table.code')">{{ $requirement->code }}</x-data-row>
                                <x-data-row :label="trans('admin/documentframeworkrequirements/table.title')">{{ $requirement->title }}</x-data-row>
                                <x-data-row :label="trans('admin/documentframeworkrequirements/table.domain')">{{ $requirement->domain }}</x-data-row>
                                <x-data-row :label="trans('admin/documentframeworkrequirements/table.parent')">
                                    @if ($requirement->parent)
                                        <a href="{{ route('documentframeworkrequirements.show', $requirement->parent) }}">{{ $requirement->parent->code }} - {{ $requirement->parent->title }}</a>
                                    @endif
                                </x-data-row>
                                <x-data-row :label="trans('admin/documentframeworkrequirements/table.coverage')">{{ $requirement->coverage_label }}</x-data-row>
                                <x-data-row :label="trans('admin/documentframeworkrequirements/table.owner')">{{ $requirement->owner?->display_name }}</x-data-row>
                                <x-data-row :label="trans('admin/documentframeworkrequirements/table.default_document_type')">{{ $requirement->defaultDocumentType?->name }}</x-data-row>
                                <x-data-row :label="trans('admin/documentframeworkrequirements/table.review_frequency_months')">
                                    @if ($requirement->review_frequency_months)
                                        {{ trans('admin/documentframeworks/general.months_interval', ['count' => $requirement->review_frequency_months]) }}
                                    @endif
                                </x-data-row>
                                <x-data-row :label="trans('admin/documentframeworkrequirements/table.description')">{{ $requirement->description }}</x-data-row>
                                <x-data-row :label="trans('admin/documentframeworkrequirements/table.evidence_guidance')">{{ $requirement->evidence_guidance }}</x-data-row>
                                <x-data-row :label="trans('admin/documentframeworkrequirements/table.applicability_notes')">{{ $requirement->applicability_notes }}</x-data-row>
                            </x-page-data>
                        </x-page-column>
                    </x-tabs.pane>

                    <x-tabs.pane name="documents">
                        <x-table.documents :route="route('api.documents.index', ['document_framework_requirement_id' => $requirement->id])" />
                    </x-tabs.pane>
                </x-slot:tabpanes>
            </x-tabs>
        </x-page-column>

        <x-page-column class="col-md-3">
            <x-box class="side-box expanded">
                <div class="box-body">
                    <div class="text-right" style="margin-bottom: 15px;">
                        <x-button.edit :item="$requirement" :route="route('documentframeworkrequirements.edit', $requirement)" />
                        @can('delete', $requirement)
                            @if ($requirement->isDeletable())
                                <a href="{{ route('documentframeworkrequirements.destroy', $requirement) }}"
                                   class="pull-right btn btn-sm btn-danger delete-asset"
                                   style="margin-right: 8px;"
                                   data-toggle="modal"
                                   data-title="{{ trans('general.delete') }}"
                                   data-content="{{ trans('general.sure_to_delete_var', ['item' => $requirement->code.' - '.$requirement->title]) }}"
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
                        <x-data-row :label="trans('admin/documentframeworkrequirements/table.is_mandatory')">{{ $requirement->is_mandatory ? trans('general.yes') : trans('general.no') }}</x-data-row>
                        <x-data-row :label="trans('admin/documentframeworkrequirements/table.is_active')">{{ $requirement->is_active ? trans('general.yes') : trans('general.no') }}</x-data-row>
                        <x-data-row :label="trans('general.documents')">{{ number_format($requirement->documents_count) }}</x-data-row>
                        <x-data-row :label="trans('admin/documentframeworkrequirements/table.primary_documents_count')">{{ number_format($requirement->primary_documents_count) }}</x-data-row>
                        <x-data-row :label="trans('admin/documentframeworkrequirements/table.healthy_primary_documents_count')">{{ number_format($requirement->healthy_primary_documents_count) }}</x-data-row>
                        <x-data-row :label="trans('admin/documentframeworkrequirements/table.sort_order')">{{ $requirement->sort_order }}</x-data-row>
                        <x-data-row :label="trans('general.created_by')">{{ $requirement->adminuser?->display_name }}</x-data-row>
                        <x-data-row :label="trans('general.created_at')">{{ Helper::getFormattedDateObject($requirement->created_at, 'datetime', false) }}</x-data-row>
                        <x-data-row :label="trans('general.updated_at')">{{ Helper::getFormattedDateObject($requirement->updated_at, 'datetime', false) }}</x-data-row>
                    </x-page-data>
                </div>
            </x-box>
        </x-page-column>
    </x-container>
@stop

@section('moar_scripts')
    @include ('partials.bootstrap-table')
@stop
