@extends('layouts/default')

@section('title')
    {{ trans('admin/tenantservices/general.title') }}
    @parent
@stop

@section('header_right')
    <a href="{{ route('tenants.show', $tenant) }}" class="btn btn-default">{{ trans('general.back') }}</a>
    <a href="{{ route('tenants.services.acn_export', $tenant) }}" class="btn btn-info">
        <x-icon type="download" class="fa-fw" />
        {{ trans('admin/tenantservices/general.export_acn') }}
    </a>
    @if ($canManageTenant)
        <a href="{{ route('tenants.services.create', $tenant) }}" class="btn btn-primary">
            <x-icon type="plus" class="fa-fw" />
            {{ trans('admin/tenantservices/general.create') }}
        </a>
    @endif
@stop

@section('content')
<div class="row">
    <div class="col-md-12">
        <div class="box box-default">
            <div class="box-header with-border">
                <h2 class="box-title">{{ trans('admin/tenantservices/general.inventory_title') }} - {{ $tenant->display_name }}</h2>
            </div>
            <div class="box-body">
                <div class="callout callout-info">
                    <p>{{ trans('admin/tenantservices/general.help') }}</p>
                    <p>{{ trans('admin/tenantservices/general.linking_guidance') }}</p>
                </div>

                @if ($canManageTenant)
                    <form id="bulkServicesForm" method="POST" action="{{ route('tenants.services.bulkedit', $tenant) }}" style="margin-bottom: 10px;">
                        @csrf
                        <button type="submit" class="btn btn-default btn-sm" id="bulkServicesButton">
                            <x-icon type="edit" class="fa-fw" />
                            {{ trans('general.bulk_edit') }}
                        </button>
                    </form>
                @endif

                <div class="table-responsive">
                    <table class="table table-striped snipe-table">
                        <thead>
                            <tr>
                                @if ($canManageTenant)
                                    <th style="width:1%;"><input type="checkbox" id="tenantServicesCheckAll" aria-label="{{ trans('general.select_all_none') }}"></th>
                                @endif
                                <th>{{ trans('admin/tenantservices/general.macro_area') }}</th>
                                <th>{{ trans('admin/tenantservices/general.name') }}</th>
                                <th>{{ trans('admin/tenantservices/general.relevance_preassigned') }}</th>
                                <th>{{ trans('admin/tenantservices/general.relevance_assigned') }}</th>
                                <th>{{ trans('general.status') }}</th>
                                <th>{{ trans('admin/tenantservices/general.linked_documents') }}</th>
                                <th>{{ trans('admin/tenantservices/general.linked_contracts') }}</th>
                                @if ($canManageTenant)
                                    <th class="text-right">{{ trans('general.actions') }}</th>
                                @endif
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($services as $service)
                                <tr>
                                    @if ($canManageTenant)
                                        <td><input type="checkbox" name="ids[]" value="{{ $service->id }}" form="bulkServicesForm" class="tenant-service-checkbox" aria-label="{{ $service->name }}"></td>
                                    @endif
                                    <td>{{ $service->macro_area_label }}</td>
                                    <td>
                                        <strong>{{ $service->name }}</strong>
                                        @if ($service->description)
                                            <br><span class="text-muted">{{ \Illuminate\Support\Str::limit($service->description, 160) }}</span>
                                        @endif
                                        @if ($service->acn_subject_basis)
                                            <br><span class="text-muted"><strong>{{ trans('admin/tenantservices/general.acn_subject_basis') }}:</strong> {{ \Illuminate\Support\Str::limit($service->acn_subject_basis, 160) }}</span>
                                        @endif
                                    </td>
                                    <td>{{ $service->pre_assigned_relevance_label }}</td>
                                    <td>{{ $service->assigned_relevance_label }}</td>
                                    <td>
                                        <span class="label {{ $service->is_active ? 'label-success' : 'label-default' }}">
                                            {{ $service->is_active ? trans('admin/tenantservices/general.active') : trans('admin/tenantservices/general.inactive') }}
                                        </span>
                                    </td>
                                    <td>
                                        <a href="{{ route('documents.index', ['tenant_id' => $tenant->id, 'tenant_service_id' => $service->id]) }}">
                                            {{ number_format($service->documents_count) }}
                                        </a>
                                    </td>
                                    <td>
                                        <a href="{{ route('contracts.index', ['tenant_id' => $tenant->id, 'tenant_service_id' => $service->id]) }}">
                                            {{ number_format($service->contracts_count) }}
                                        </a>
                                    </td>
                                    @if ($canManageTenant)
                                        <td class="text-right">
                                            <a href="{{ route('tenants.services.edit', [$tenant, $service]) }}" class="btn btn-warning btn-sm">
                                                <x-icon type="edit" class="fa-fw" />
                                                {{ trans('general.edit') }}
                                            </a>
                                            <form method="POST" action="{{ route('tenants.services.destroy', [$tenant, $service]) }}" style="display:inline;" onsubmit="return confirm('{{ trans('general.are_you_sure') }}');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-danger btn-sm">
                                                    <x-icon type="delete" class="fa-fw" />
                                                    {{ trans('general.delete') }}
                                                </button>
                                            </form>
                                        </td>
                                    @endif
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="{{ $canManageTenant ? 9 : 7 }}" class="text-muted">{{ trans('admin/tenantservices/general.no_services') }}</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@if ($canManageTenant)
@section('moar_scripts')
    <script nonce="{{ csrf_token() }}">
        $(function () {
            $('#tenantServicesCheckAll').on('change', function () {
                $('.tenant-service-checkbox').prop('checked', $(this).prop('checked'));
            });
        });
    </script>
@stop
@endif
