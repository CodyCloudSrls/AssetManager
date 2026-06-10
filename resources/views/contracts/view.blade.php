@extends('layouts/default')

@section('title')
{{ trans('admin/contracts/general.view') }} - {{ $contract->name }}
@parent
@stop

@section('content')
    <x-container columns="2">
        <x-page-column class="col-md-9 main-panel">
            <x-tabs>
                <x-slot:tabnav>
                    <x-tabs.nav-item name="details" icon="fa-solid fa-file-signature fa-fw" label="{{ trans('general.details') }}" tooltip="{{ trans('general.details') }}" />
                    <x-tabs.nav-item name="subscriptions" icon="fa-solid fa-repeat fa-fw" label="{{ trans('admin/contracts/general.subscriptions') }}" tooltip="{{ trans('admin/contracts/general.subscriptions') }}" count="{{ $contract->subscriptions->count() }}" />
                    <x-tabs.nav-item name="audit" icon="fa-solid fa-fingerprint fa-fw" label="{{ trans('admin/contracts/general.audit_events') }}" tooltip="{{ trans('admin/contracts/general.audit_events') }}" count="{{ $contract->events->count() }}" />
                    <x-tabs.files-tab :item="$contract" count="{{ $contract->uploads()->count() }}"/>
                    <x-tabs.upload-tab :item="$contract"/>
                </x-slot:tabnav>

                <x-slot:tabpanes>
                    <x-tabs.pane name="details">
                        <x-page-data>
                            <x-data-row :label="trans('general.customer')"><a href="{{ route('customers.show', $contract->customer) }}">{{ $contract->customer?->name }}</a></x-data-row>
                            <x-data-row :label="trans('general.company')">{{ $contract->company?->name }}</x-data-row>
                            <x-data-row :label="trans('admin/contracts/general.contract_number')">{{ $contract->contract_number }}</x-data-row>
                            <x-data-row :label="trans('general.status')">{{ $contract->status_label }}</x-data-row>
                            <x-data-row :label="trans('admin/contracts/general.owner')">{{ $contract->owner?->display_name }}</x-data-row>
                            <x-data-row :label="trans('general.document')">@if ($contract->document)<a href="{{ route('documents.show', $contract->document) }}">{{ $contract->document->name }}</a>@endif</x-data-row>
                            <x-data-row :label="trans('admin/contracts/general.signed_at')">{{ \App\Helpers\Helper::getFormattedDateObject($contract->signed_at, 'date', false) }}</x-data-row>
                            <x-data-row :label="trans('admin/contracts/general.starts_at')">{{ \App\Helpers\Helper::getFormattedDateObject($contract->starts_at, 'date', false) }}</x-data-row>
                            <x-data-row :label="trans('admin/contracts/general.ends_at')">{{ \App\Helpers\Helper::getFormattedDateObject($contract->ends_at, 'date', false) }}</x-data-row>
                            <x-data-row :label="trans('admin/contracts/general.renewal_due_at')">{{ \App\Helpers\Helper::getFormattedDateObject($contract->renewal_due_at, 'date', false) }}</x-data-row>
                            <x-data-row :label="trans('admin/contracts/general.notice_due_at')">{{ \App\Helpers\Helper::getFormattedDateObject($contract->notice_due_at, 'date', false) }}</x-data-row>
                            <x-data-row :label="trans('admin/contracts/general.scope')">{{ $contract->scope }}</x-data-row>
                            <x-data-row :label="trans('admin/tenantservices/general.field_label')">
                                @if ($contract->tenantServices->count() > 0)
                                    <ul style="padding-left: 18px; margin-bottom: 0;">
                                        @foreach ($contract->tenantServices as $tenantService)
                                            <li>
                                                <a href="{{ route('tenants.services.index', $tenantService->tenant_id) }}">{{ $tenantService->name }}</a>
                                                <span class="text-muted">({{ $tenantService->macro_area_label }}, {{ $tenantService->assigned_relevance_label }})</span>
                                            </li>
                                        @endforeach
                                    </ul>
                                @endif
                            </x-data-row>
                            <x-data-row :label="trans('general.notes')">{!! $contract->notes ? \App\Helpers\Helper::parseEscapedMarkedownInline($contract->notes) : '' !!}</x-data-row>
                        </x-page-data>
                    </x-tabs.pane>

                    <x-tabs.pane name="subscriptions">
                        <div class="table-responsive">
                            <table class="table table-striped snipe-table">
                                <thead>
                                    <tr>
                                        <th>{{ trans('admin/contracts/general.subscription_name') }}</th>
                                        <th>{{ trans('admin/contracts/general.service_code') }}</th>
                                        <th>{{ trans('admin/contracts/general.monthly_revenue') }}</th>
                                        <th>{{ trans('admin/contracts/general.monthly_cost') }}</th>
                                        <th>{{ trans('admin/contracts/general.monthly_net') }}</th>
                                        <th>{{ trans('admin/contracts/general.cost_supplier') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($contract->subscriptions as $subscription)
                                        @php
                                            $monthlyRevenue = (float) $subscription->monthly_revenue;
                                            $monthlyCost = $subscription->costLines->sum(fn ($costLine) => (float) $costLine->monthly_cost);
                                        @endphp
                                        <tr>
                                            <td>{{ $subscription->name }}</td>
                                            <td>{{ $subscription->service_code }}</td>
                                            <td>{{ $contract->currency }} {{ \App\Helpers\Helper::formatCurrencyOutput($monthlyRevenue) }}</td>
                                            <td>{{ $contract->currency }} {{ \App\Helpers\Helper::formatCurrencyOutput($monthlyCost) }}</td>
                                            <td>{{ $contract->currency }} {{ \App\Helpers\Helper::formatCurrencyOutput($monthlyRevenue - $monthlyCost) }}</td>
                                            <td>{{ $subscription->costLines->pluck('supplier.name')->filter()->implode(', ') }}</td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="6" class="text-muted">{{ trans('general.no_results') }}</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </x-tabs.pane>

                    <x-tabs.pane name="audit">
                        <div class="table-responsive">
                            <table class="table table-striped snipe-table">
                                <thead>
                                    <tr>
                                        <th>{{ trans('general.date') }}</th>
                                        <th>{{ trans('general.user') }}</th>
                                        <th>{{ trans('admin/contracts/general.event_type') }}</th>
                                        <th>{{ trans('admin/contracts/general.previous_hash') }}</th>
                                        <th>{{ trans('admin/contracts/general.event_hash') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($contract->events as $event)
                                        <tr>
                                            <td>{{ \App\Helpers\Helper::getFormattedDateObject($event->created_at, 'datetime', false) }}</td>
                                            <td>{{ $event->actor?->display_name }}</td>
                                            <td>{{ trans('admin/contracts/general.event_'.$event->event_type) }}</td>
                                            <td><code>{{ $event->previous_hash }}</code></td>
                                            <td><code>{{ $event->event_hash }}</code></td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="5" class="text-muted">{{ trans('general.no_results') }}</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </x-tabs.pane>

                    <x-tabs.pane name="files" class="{{ $contract->uploads->count() == 0 ? 'hidden-print' : '' }}">
                        <x-table.files object_type="contracts" :object="$contract"/>
                    </x-tabs.pane>
                </x-slot:tabpanes>
            </x-tabs>
        </x-page-column>

        <x-page-column class="col-md-3 hidden-print">
            <x-box>
                <x-page-data>
                    <x-data-row :label="trans('admin/contracts/general.monthly_revenue')">{{ $contract->currency }} {{ \App\Helpers\Helper::formatCurrencyOutput($contract->subscriptions->sum(fn ($subscription) => (float) $subscription->monthly_revenue)) }}</x-data-row>
                    <x-data-row :label="trans('admin/contracts/general.monthly_cost')">{{ $contract->currency }} {{ \App\Helpers\Helper::formatCurrencyOutput($contract->subscriptions->sum(fn ($subscription) => $subscription->costLines->sum(fn ($costLine) => (float) $costLine->monthly_cost))) }}</x-data-row>
                </x-page-data>
                <div class="text-right">
                    @can('update', $contract)
                        <a href="{{ route('contracts.edit', $contract) }}" class="btn btn-warning">
                            <x-icon type="edit" class="fa-fw" />
                            {{ trans('general.edit') }}
                        </a>
                    @endcan
                    @can('delete', $contract)
                        <x-button.delete :item="$contract" />
                    @endcan
                </div>
            </x-box>
        </x-page-column>
    </x-container>
@endsection

@section('moar_scripts')
    @can('files', $contract)
        @include('modals.upload-file', ['item_type' => 'contracts', 'item_id' => $contract->id])
    @endcan

    @include('partials.bootstrap-table', ['exportFile' => 'contract-' . $contract->name . '-export', 'search' => false])
@endsection
