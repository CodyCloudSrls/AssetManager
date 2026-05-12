@extends('layouts/default')

{{-- Page title --}}
@section('title')

  {{ trans('admin/suppliers/table.view') }} -
  {{ $supplier->name }}

  @parent
@stop

@section('header_right')
    <i class="fa-regular fa-2x fa-square-caret-right pull-right" id="expand-info-panel-button" data-tooltip="true" title="{{ trans('button.show_hide_info') }}"></i>
@endsection

{{-- Page content --}}
@section('content')
    <x-container columns="2">
        <x-page-column class="col-md-9 main-panel">
            <x-tabs>
                <x-slot:tabnav>

                    <x-tabs.nav-item
                        name="nis"
                        icon="fa-solid fa-shield-halved fa-fw"
                        label="NIS2"
                        tooltip="NIS2"
                    />
                    <x-tabs.asset-tab count="{{ $supplier->assets()->AssetsForShow()->count() }}" />
                    <x-tabs.license-tab count="{{ $supplier->licenses->count() }}" />
                    <x-tabs.accessory-tab count="{{ $supplier->accessories->count() }}" />
                    <x-tabs.consumable-tab count="{{ $supplier->consumables->count() }}" />
                    <x-tabs.maintenance-tab count="{{ $supplier->maintenances->count() }}"/>
                    <x-tabs.files-tab :item="$supplier" count="{{ $supplier->uploads()->count() }}"/>
                    <x-tabs.upload-tab :item="$supplier"/>

                </x-slot:tabnav>



                <x-slot:tabpanes>

                    <x-tabs.pane name="nis">
                        @php
                            $supplierEvidenceChecklist = $supplier->nisEvidenceChecklist();
                        @endphp
                        <x-page-data>
                            <x-data-row :label="trans('admin/suppliers/table.tax_code')">{{ $supplier->tax_code }}</x-data-row>
                            <x-data-row :label="trans('admin/suppliers/table.nis_relevant')">{{ $supplier->nis_relevant ? trans('general.yes') : trans('general.no') }}</x-data-row>
                            <x-data-row :label="trans('admin/suppliers/table.nis_criticality')">{{ $supplier->nis_criticality_label }}</x-data-row>
                            <x-data-row :label="trans('admin/suppliers/table.nis_relevance_type')">{{ $supplier->nis_relevance_type_label }}</x-data-row>
                            <x-data-row :label="trans('admin/suppliers/table.nis_assessment_status')">{{ $supplier->nis_assessment_status_label }}</x-data-row>
                            <x-data-row :label="trans('admin/suppliers/table.nis_assessment_method')">{{ $supplier->nis_assessment_method_label }}</x-data-row>
                            <x-data-row :label="trans('admin/suppliers/table.nis_assessment_outcome')">{{ $supplier->nis_assessment_outcome_label }}</x-data-row>
                            <x-data-row :label="trans('admin/suppliers/table.cpv_codes')">{{ $supplier->cpv_codes }}</x-data-row>
                            <x-data-row :label="trans('admin/suppliers/table.nis_assessment_scope')">{{ $supplier->nis_assessment_scope }}</x-data-row>
                            <x-data-row :label="trans('admin/suppliers/table.nis_relevance_criteria')">{{ $supplier->nis_relevance_criteria }}</x-data-row>
                            <x-data-row :label="trans('admin/suppliers/table.nis_last_assessment_at')">{{ Helper::getFormattedDateObject($supplier->nis_last_assessment_at, 'date', false) }}</x-data-row>
                            <x-data-row :label="trans('admin/suppliers/table.nis_next_review_at')">{{ Helper::getFormattedDateObject($supplier->nis_next_review_at, 'date', false) }}</x-data-row>
                        </x-page-data>

                        <h3>{{ trans('admin/suppliers/table.supplier_evidence_checklist') }}</h3>
                        <p class="help-block">{{ trans('admin/suppliers/table.supplier_evidence_checklist_help') }}</p>
                        <div class="table-responsive">
                            <table class="table table-striped snipe-table">
                                <thead>
                                    <tr>
                                        <th>{{ trans('admin/suppliers/table.supplier_evidence_category') }}</th>
                                        <th>{{ trans('admin/suppliers/table.supplier_evidence_linked') }}</th>
                                        <th>{{ trans('admin/suppliers/table.supplier_evidence_review_status') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($supplierEvidenceChecklist as $evidenceItem)
                                        <tr>
                                            <td>{{ $evidenceItem['label'] }}</td>
                                            <td>{{ $evidenceItem['count'] }}</td>
                                            <td><span class="{{ $evidenceItem['status_class'] }}">{{ $evidenceItem['status_label'] }}</span></td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <h3>{{ trans('admin/suppliers/table.supplier_evidence_documents') }}</h3>
                        @include('documents.partials.assignments-table', [
                            'assignments' => $supplier->documentAssignments,
                            'showDocumentColumn' => true,
                            'showTargetColumn' => false,
                            'showActions' => true,
                        ])
                    </x-tabs.pane>

                    <!-- start assets tab pane -->
                    <x-tabs.pane name="assets">
                        <x-table.assets name="assets" :route="route('api.assets.index', ['supplier_id' => $supplier->id, 'itemtype' => 'assets'])"/>
                    </x-tabs.pane>
                    <!-- end assets tab pane -->


                    <!-- start licenses tab pane -->
                    <x-tabs.pane name="licenses">
                        <x-table.licenses :name="$supplier->name" :route="route('api.licenses.index', ['supplier_id' => $supplier->id])"/>
                    </x-tabs.pane>
                    <!-- end licenses tab pane -->

                    <!-- start accessories tab pane -->
                    <x-tabs.pane name="accessories">
                        <x-table.accessories :name="$supplier->name" :route="route('api.accessories.index', ['supplier_id' => $supplier->id])"/>
                    </x-tabs.pane>
                    <!-- end accessories tab pane -->

                    <!-- start consumables tab pane -->
                    <x-tabs.pane name="consumables">
                        <x-table.consumables :name="$supplier->name" :route="route('api.consumables.index', ['supplier_id' => $supplier->id])"/>
                    </x-tabs.pane>
                    <!-- end consumables tab pane -->


                    <!-- start consumables tab pane -->
                    @can('view', \App\Models\Asset::class)
                        <x-tabs.pane name="maintenances" class="{{ $supplier->maintenances->count() == 0 ? 'hidden-print' : '' }}">
                            <x-slot:table_header>
                                {{ trans('admin/maintenances/general.maintenances') }}
                            </x-slot:table_header>

                                <x-table
                                        buttons="maintenanceButtons"
                                        api_url="{{ route('api.maintenances.index', ['supplier_id' => $supplier->id]) }}"
                                        :presenter="\App\Presenters\MaintenancesPresenter::dataTableLayout()"
                                        export_filename="export-{{ str_slug($supplier->name) }}-maintenances-{{ date('Y-m-d') }}"
                                />

                        </x-tabs.pane>
                    @endcan
                    <!-- end consumables tab pane -->

                    <!-- start files tab pane -->
                    <x-tabs.pane name="files" class="{{ $supplier->uploads->count() == 0 ? 'hidden-print' : '' }}">
                        <x-table.files object_type="suppliers" :object="$supplier"/>
                    </x-tabs.pane>
                    <!-- end files tab pane -->

                </x-slot:tabpanes>

            </x-tabs>
        </x-page-column>
        <x-page-column class="col-md-3 hidden-print">

            <x-box class="side-box expanded">
                <x-info-panel :infoPanelObj="$supplier" img_path="{{ app('suppliers_upload_url') }}">

                    <x-slot:buttons>
                        <x-button :item="$supplier" permission="update" :route="route('suppliers.edit', $supplier->id)" class="btn-warning"  />
                        <x-button.delete :item="$supplier" />
                    </x-slot:buttons>


                </x-info-panel>
            </x-box>
        </x-page-column>

    </x-container>

    <div class="visible-print">
        <table style="margin-top: 80px;" class="signature-boxes">
            <tr>
                <td style="padding-right: 10px; vertical-align: top; font-weight: bold;">{{ trans('general.signed_off_by') }}:</td>
                <td style="padding-right: 10px; vertical-align: top;">______________________________________</td>
                <td style="padding-right: 10px; vertical-align: top;">______________________________________</td>
                <td>_____________</td>
            </tr>
            <tr style="height: 80px;">
                <td></td>
                <td style="padding-right: 10px; vertical-align: top;">{{ trans('general.name') }}</td>
                <td style="padding-right: 10px; vertical-align: top;">{{ trans('general.signature') }}</td>
                <td style="padding-right: 10px; vertical-align: top;">{{ trans('general.date') }}</td>
            </tr>
            <tr>
                <td style="padding-right: 10px; vertical-align: top; font-weight: bold;">{{ trans('admin/users/table.manager') }}:</td>
                <td style="padding-right: 10px; vertical-align: top;">______________________________________</td>
                <td style="padding-right: 10px; vertical-align: top;">______________________________________</td>
                <td>_____________</td>
            </tr>
            <tr>
                <td></td>
                <td style="padding-right: 10px; vertical-align: top;">{{ trans('general.name') }}</td>
                <td style="padding-right: 10px; vertical-align: top;">{{ trans('general.signature') }}</td>
                <td style="padding-right: 10px; vertical-align: top;">{{ trans('general.date') }}</td>
                <td></td>
            </tr>

        </table>
    </div>

@endsection

@section('moar_scripts')
    @can('files', $supplier)
        @include ('modals.upload-file', ['item_type' => 'suppliers', 'item_id' => $supplier->id])
    @endcan

    @include ('partials.bootstrap-table', ['exportFile' => 'suppliers-' . $supplier->name . '-export', 'search' => false])
@endsection
