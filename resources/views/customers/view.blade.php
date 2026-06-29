@extends('layouts/default')

@section('title')
{{ trans('admin/customers/table.view') }} - {{ $customer->name }}
@parent
@stop

@section('header_right')
    <i class="fa-regular fa-2x fa-square-caret-right pull-right" id="expand-info-panel-button" data-tooltip="true" title="{{ trans('button.show_hide_info') }}"></i>
@endsection

@section('content')
    <x-container columns="2">
        <x-page-column class="col-md-9 main-panel">
            <x-tabs>
                <x-slot:tabnav>
                    <x-tabs.nav-item name="details" icon="fa-solid fa-address-card fa-fw" label="{{ trans('general.details') }}" tooltip="{{ trans('general.details') }}" />
                    <x-tabs.nav-item name="nis" icon="fa-solid fa-shield-halved fa-fw" label="NIS2" tooltip="NIS2" />
                    <x-tabs.nav-item name="contracts" icon="fa-solid fa-file-signature fa-fw" label="{{ trans('admin/contracts/general.contracts') }}" tooltip="{{ trans('admin/contracts/general.contracts') }}" count="{{ $customer->contracts->count() }}" />
                    <x-tabs.nav-item name="documents" icon="fa-regular fa-file-lines fa-fw" label="{{ trans('general.documents') }}" tooltip="{{ trans('general.documents') }}" count="{{ $customer->documentAssignments->count() }}" />
                    <x-tabs.files-tab :item="$customer" count="{{ $customer->uploads()->count() }}"/>
                    <x-tabs.upload-tab :item="$customer"/>
                </x-slot:tabnav>

                <x-slot:tabpanes>
                    <x-tabs.pane name="details">
                        <x-page-data>
                            <x-data-row :label="trans('admin/customers/table.customer_number')">{{ $customer->customer_number }}</x-data-row>
                            <x-data-row :label="trans('general.company')">{{ $customer->company?->name }}</x-data-row>
                            <x-data-row :label="trans('general.status')">{{ $customer->status_label }}</x-data-row>
                            <x-data-row :label="trans('admin/customers/table.vat_number')">{{ $customer->vat_number }}</x-data-row>
                            <x-data-row :label="trans('admin/customers/table.tax_code')">{{ $customer->tax_code }}</x-data-row>
                            <x-data-row :label="trans('admin/customers/table.sdi_code')">{{ $customer->sdi_code }}</x-data-row>
                            <x-data-row :label="trans('admin/customers/table.pec')">{{ $customer->pec }}</x-data-row>
                            <x-data-row :label="trans('admin/customers/table.contact')">{{ $customer->contact }}</x-data-row>
                            <x-data-row :label="trans('admin/customers/table.phone')">{{ $customer->phone }}</x-data-row>
                            <x-data-row :label="trans('admin/customers/table.email')">{{ $customer->email }}</x-data-row>
                            <x-data-row :label="trans('general.url')">{{ $customer->url }}</x-data-row>
                            <x-data-row :label="trans('general.notes')">{!! $customer->notes ? \App\Helpers\Helper::parseEscapedMarkedownInline($customer->notes) : '' !!}</x-data-row>
                        </x-page-data>
                    </x-tabs.pane>

                    <x-tabs.pane name="nis">
                        <x-page-data>
                            <x-data-row :label="trans('admin/customers/table.sector')">{{ $customer->sector }}</x-data-row>
                            <x-data-row :label="trans('admin/customers/table.nis_profile')">{{ $customer->nis_profile_label }}</x-data-row>
                            <x-data-row :label="trans('admin/customers/table.nis_service_role')">{{ $customer->nis_service_role_label }}</x-data-row>
                            <x-data-row :label="trans('admin/customers/table.nis_criticality')">{{ $customer->nis_criticality_label }}</x-data-row>
                            <x-data-row :label="trans('admin/customers/table.security_contact')">{{ $customer->security_contact }}</x-data-row>
                            <x-data-row :label="trans('admin/customers/table.security_email')">{{ $customer->security_email }}</x-data-row>
                            <x-data-row :label="trans('admin/customers/table.nis_obligations')">{{ $customer->nis_obligations }}</x-data-row>
                            <x-data-row :label="trans('admin/customers/table.incident_notification_terms')">{{ $customer->incident_notification_terms }}</x-data-row>
                            <x-data-row :label="trans('admin/customers/table.sla_terms')">{{ $customer->sla_terms }}</x-data-row>
                            <x-data-row :label="trans('admin/customers/table.audit_rights')">{{ $customer->audit_rights }}</x-data-row>
                            <x-data-row :label="trans('admin/customers/table.nis_last_assessment_at')">{{ \App\Helpers\Helper::getFormattedDateObject($customer->nis_last_assessment_at, 'date', false) }}</x-data-row>
                            <x-data-row :label="trans('admin/customers/table.nis_next_review_at')">{{ \App\Helpers\Helper::getFormattedDateObject($customer->nis_next_review_at, 'date', false) }}</x-data-row>
                        </x-page-data>
                    </x-tabs.pane>

                    <x-tabs.pane name="contracts">
                        @can('create', \App\Models\CustomerContract::class)
                            <div class="text-right" style="margin-bottom: 10px;">
                                <a href="{{ route('contracts.create', ['customer_id' => $customer->id, 'company_id' => $customer->company_id]) }}" class="btn btn-sm btn-warning">
                                    <i class="fa fa-plus" aria-hidden="true"></i>
                                    {{ trans('admin/contracts/general.create') }}
                                </a>
                            </div>
                        @endcan
                        <div class="table-responsive">
                            <table class="table table-striped snipe-table">
                                <thead>
                                    <tr>
                                        <th>{{ trans('general.name') }}</th>
                                        <th>{{ trans('admin/contracts/general.contract_number') }}</th>
                                        <th>{{ trans('general.status') }}</th>
                                        <th>{{ trans('admin/contracts/general.starts_at') }}</th>
                                        <th>{{ trans('admin/contracts/general.ends_at') }}</th>
                                        <th>{{ trans('admin/contracts/general.renewal_due_at') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($customer->contracts as $contract)
                                        <tr>
                                            <td><a href="{{ route('contracts.show', $contract) }}">{{ $contract->name }}</a></td>
                                            <td>{{ $contract->contract_number }}</td>
                                            <td>{{ $contract->status_label }}</td>
                                            <td>{{ \App\Helpers\Helper::getFormattedDateObject($contract->starts_at, 'date', false) }}</td>
                                            <td>{{ \App\Helpers\Helper::getFormattedDateObject($contract->ends_at, 'date', false) }}</td>
                                            <td>{{ \App\Helpers\Helper::getFormattedDateObject($contract->renewal_due_at, 'date', false) }}</td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="6" class="text-muted">{{ trans('general.no_results') }}</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </x-tabs.pane>

                    <x-tabs.pane name="documents">
                        @include('documents.partials.assignments-table', [
                            'assignments' => $customer->documentAssignments,
                            'showDocumentColumn' => true,
                            'showTargetColumn' => false,
                            'showActions' => true,
                        ])
                    </x-tabs.pane>

                    <x-tabs.pane name="files" class="{{ $customer->uploads->count() == 0 ? 'hidden-print' : '' }}">
                        <x-table.files object_type="customers" :object="$customer"/>
                    </x-tabs.pane>
                </x-slot:tabpanes>
            </x-tabs>
        </x-page-column>

        <x-page-column class="col-md-3 hidden-print">
            <x-box class="side-box expanded">
                <x-info-panel :infoPanelObj="$customer" img_path="{{ app('customers_upload_url') }}">
                    <x-slot:buttons>
                        <x-button :item="$customer" permission="update" :route="route('customers.edit', $customer->id)" class="btn-warning" />
                        <x-button.delete :item="$customer" />
                    </x-slot:buttons>
                </x-info-panel>
            </x-box>
        </x-page-column>
    </x-container>
@endsection

@section('moar_scripts')
    @can('files', $customer)
        @include('modals.upload-file', ['item_type' => 'customers', 'item_id' => $customer->id])
    @endcan

    @include('partials.bootstrap-table', ['exportFile' => 'customers-' . $customer->name . '-export', 'search' => false])
@endsection
