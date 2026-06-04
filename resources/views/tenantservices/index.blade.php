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
                </div>

                <div class="table-responsive">
                    <table class="table table-striped snipe-table">
                        <thead>
                            <tr>
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
                                    <td>{{ $service->macro_area_label }}</td>
                                    <td>
                                        <strong>{{ $service->name }}</strong>
                                        @if ($service->description)
                                            <br><span class="text-muted">{{ \Illuminate\Support\Str::limit($service->description, 160) }}</span>
                                        @endif
                                    </td>
                                    <td>{{ $service->pre_assigned_relevance_label }}</td>
                                    <td>{{ $service->assigned_relevance_label }}</td>
                                    <td>
                                        <span class="label {{ $service->is_active ? 'label-success' : 'label-default' }}">
                                            {{ $service->is_active ? trans('admin/tenantservices/general.active') : trans('admin/tenantservices/general.inactive') }}
                                        </span>
                                    </td>
                                    <td>{{ number_format($service->documents_count) }}</td>
                                    <td>{{ number_format($service->contracts_count) }}</td>
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
                                    <td colspan="{{ $canManageTenant ? 8 : 7 }}" class="text-muted">{{ trans('admin/tenantservices/general.no_services') }}</td>
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
