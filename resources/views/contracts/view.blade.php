@extends('layouts/default')

@section('title')
{{ trans('admin/contracts/general.view') }} - {{ $contract->name }}
@parent
@stop

@section('header_right')
    <a href="{{ route('contracts.index') }}" class="btn btn-default">
        <x-icon type="angle-left" class="fa-fw" /> {{ trans('general.back_to_list') }}
    </a>
@endsection

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
                        @can('files', $contract)
                            @if ($contract->customer && $contract->customer->uploads()->exists())
                                @can('files', $contract->customer)
                                    <div class="text-right hidden-print" style="margin-bottom: 10px;">
                                        <a href="#" class="btn btn-sm btn-primary" data-toggle="modal" data-target="#moveCustomerFileModal">
                                            <x-icon type="paperclip" class="fa-fw" />
                                            {{ trans('admin/contracts/general.move_file_from_customer') }}
                                            ({{ $contract->customer->uploads()->count() }})
                                        </a>
                                    </div>
                                @endcan
                            @endif
                        @endcan
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

        @if ($contract->customer && $contract->customer->uploads()->exists())
            @can('files', $contract->customer)
                <div class="modal fade" id="moveCustomerFileModal" tabindex="-1" role="dialog" aria-labelledby="moveCustomerFileModalLabel">
                    <div class="modal-dialog" role="document">
                        <form method="POST" action="{{ route('contracts.files.move', $contract) }}">
                            @csrf
                            <div class="modal-content">
                                <div class="modal-header">
                                    <button type="button" class="close" data-dismiss="modal" aria-label="{{ trans('button.cancel') }}"><span aria-hidden="true">&times;</span></button>
                                    <h4 class="modal-title" id="moveCustomerFileModalLabel">{{ trans('admin/contracts/general.move_file_from_customer') }}</h4>
                                </div>
                                <div class="modal-body">
                                    <p class="help-block">{{ trans('admin/contracts/general.move_file_help') }}</p>
                                    <div class="table-responsive">
                                        <table class="table table-striped">
                                            <thead>
                                                <tr>
                                                    <th style="width:1%;"><input type="checkbox" id="moveCustomerFileCheckAll" aria-label="{{ trans('general.select_all_none') }}"></th>
                                                    <th>{{ trans('general.file_name') }}</th>
                                                    <th>{{ trans('general.notes') }}</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach ($contract->customer->uploads()->orderByDesc('created_at')->get() as $upload)
                                                    <tr>
                                                        <td><input type="checkbox" name="file_ids[]" value="{{ $upload->id }}" class="move-customer-file-checkbox" aria-label="{{ $upload->filename }}"></td>
                                                        <td>{{ $upload->filename }}</td>
                                                        <td>{{ $upload->note }}</td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-default" data-dismiss="modal">{{ trans('button.cancel') }}</button>
                                    <button type="submit" class="btn btn-primary">{{ trans('admin/contracts/general.move_selected') }}</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <script nonce="{{ csrf_token() }}">
                    $(function () {
                        $('#moveCustomerFileCheckAll').on('change', function () {
                            $('.move-customer-file-checkbox').prop('checked', $(this).prop('checked'));
                        });
                    });
                </script>
            @endcan
        @endif
    @endcan

    @include('partials.bootstrap-table', ['exportFile' => 'contract-' . $contract->name . '-export', 'search' => false])
@endsection
