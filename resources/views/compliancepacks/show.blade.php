@extends('layouts/default')

@section('title')
    {{ $packKey }}
    @parent
@stop

@section('header_right')
    <a href="{{ route('settings.compliance_framework_packs.index') }}" class="btn btn-default">
        <x-icon type="angle-left" />
        {{ trans('general.back') }}
    </a>
@stop

@section('content')
    <x-container>
        <x-box :header="data_get($pack, 'framework.name', $packKey)">
            <div class="row">
                <div class="col-md-2">
                    <strong>{{ trans('admin/compliancepacks/general.pack') }}</strong><br>
                    <code>{{ $packKey }}</code>
                </div>
                <div class="col-md-2">
                    <strong>{{ trans('admin/compliancepacks/general.locale') }}</strong><br>
                    {{ $dashboard->localeLabel($pack['locale'] ?? null) }}
                </div>
                <div class="col-md-2">
                    <strong>{{ trans('admin/compliancepacks/general.jurisdiction') }}</strong><br>
                    {{ data_get($pack, 'source_register.jurisdiction', data_get($pack, 'framework.jurisdiction', '-')) }}
                </div>
                <div class="col-md-2">
                    <strong>{{ trans('admin/compliancepacks/general.version') }}</strong><br>
                    {{ $pack['pack_version'] ?? data_get($pack, 'framework.version') }}
                </div>
                <div class="col-md-2">
                    <strong>{{ trans('admin/compliancepacks/general.source_status') }}</strong><br>
                    {{ $dashboard->sourceStatusLabel(data_get($pack, 'source_register.status')) }}
                    <small class="text-muted">({{ $dashboard->sourceScopeLabel(data_get($pack, 'source_register.scope')) }})</small>
                </div>
                <div class="col-md-2">
                    <strong>{{ trans('admin/compliancepacks/general.source_checked_at') }}</strong><br>
                    {{ data_get($pack, 'source_register.last_checked_at', '-') }}
                </div>
            </div>
            <div class="row" style="margin-top: 15px;">
                <div class="col-md-3">
                    <strong>{{ trans('admin/compliancepacks/general.source_register') }}</strong><br>
                    <code>{{ $pack['source_register_key'] ?? '-' }}</code>
                </div>
                <div class="col-md-9">
                    <strong>{{ trans('admin/compliancepacks/general.checksum') }}</strong><br>
                    <code>{{ $checksum }}</code>
                </div>
            </div>
            @if (data_get($pack, 'source_register.scope') === 'eu_baseline')
                <p class="help-block" style="margin-top: 15px;">
                    {{ trans('admin/compliancepacks/general.eu_baseline_help') }}
                </p>
            @endif
        </x-box>

        <x-box :header="trans('admin/compliancepacks/general.system_template')">
            <div class="row">
                <div class="col-md-8">
                    @include('compliancepacks.partials.diff', ['diff' => $systemDiff])
                </div>
                <div class="col-md-4 text-right">
                    @if ($dashboard->canApplySystemDiff($systemDiff))
                        <form method="POST" action="{{ route('settings.compliance_framework_packs.system.apply', $packKey) }}">
                            @csrf
                            <button type="submit" class="btn btn-warning">
                                <x-icon type="checkmark" />
                                {{ trans('admin/compliancepacks/general.apply_system') }}
                            </button>
                        </form>
                    @else
                        <span class="label label-success">{{ trans('admin/compliancepacks/general.no_action_required') }}</span>
                    @endif
                </div>
            </div>
        </x-box>

        <x-box :header="trans('admin/compliancepacks/general.tenant_copies')">
            <form id="tenant-pack-bulk-apply-form" method="POST" action="{{ route('settings.compliance_framework_packs.tenants.bulk_apply', $packKey) }}" class="form-inline" style="margin-bottom: 15px;">
                @csrf
                <input type="hidden" name="confirm_bulk_safe_update" value="1">
                <button type="submit" class="btn btn-sm btn-warning">
                    <x-icon type="checkmark" />
                    {{ trans('admin/compliancepacks/general.bulk_apply_tenants') }}
                </button>
                <span class="help-block" style="display: inline; margin-left: 10px;">
                    {{ trans('admin/compliancepacks/general.bulk_apply_help') }}
                </span>
            </form>
            <div class="table-responsive">
                <table class="table table-striped snipe-table">
                    <thead>
                        <tr>
                            <th>{{ trans('general.select') }}</th>
                            <th>{{ trans('admin/compliancepacks/general.tenant') }}</th>
                            <th>{{ trans('admin/compliancepacks/general.locale') }}</th>
                            <th>{{ trans('admin/compliancepacks/general.status') }}</th>
                            <th>{{ trans('admin/compliancepacks/general.source_version') }}</th>
                            <th>{{ trans('admin/compliancepacks/general.missing_requirements') }}</th>
                            <th>{{ trans('admin/compliancepacks/general.changed_requirements') }}</th>
                            <th>{{ trans('admin/compliancepacks/general.conflicts') }}</th>
                            <th>{{ trans('general.actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($tenantRows as $row)
                            @php
                                $tenant = $row['tenant'];
                                $diff = $row['diff'];
                            @endphp
                            <tr>
                                <td>
                                    @if ($row['can_apply'])
                                        <input
                                            type="checkbox"
                                            name="tenant_ids[]"
                                            value="{{ $tenant->id }}"
                                            form="tenant-pack-bulk-apply-form"
                                            aria-label="{{ trans('admin/compliancepacks/general.bulk_select_tenant') }}"
                                        >
                                    @else
                                        -
                                    @endif
                                </td>
                                <td>
                                    <a href="{{ route('tenants.show', $tenant) }}">{{ $row['root_company']?->name ?? '-' }}</a><br>
                                    <small class="text-muted">#{{ $tenant->id }}</small>
                                </td>
                                <td>{{ $dashboard->localeLabel($tenant->defaultLocale()) }}</td>
                                <td>
                                    <span class="label label-{{ $dashboard->statusLabelClass($diff['status']) }}">
                                        {{ $dashboard->statusLabel($diff['status']) }}
                                    </span>
                                </td>
                                <td>{{ $diff['source_pack_version'] ?: '-' }}</td>
                                <td>{{ count($diff['missing_requirements']) }}</td>
                                <td>{{ count($diff['changed_requirements']) }}</td>
                                <td>{{ $diff['conflicts_count'] }}</td>
                                <td>
                                    @if ($row['can_apply'])
                                        <form method="POST" action="{{ route('settings.compliance_framework_packs.tenants.apply', [$packKey, $tenant]) }}">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-warning">
                                                <x-icon type="checkmark" />
                                                {{ trans('admin/compliancepacks/general.apply_tenant') }}
                                            </button>
                                        </form>
                                    @elseif ($diff['status'] === 'current')
                                        <span class="label label-success">{{ trans('admin/compliancepacks/general.no_action_required') }}</span>
                                    @else
                                        <span class="label label-danger">{{ trans('admin/compliancepacks/general.manual_review') }}</span>
                                    @endif
                                    @if ($row['can_purge'])
                                        <form
                                            method="POST"
                                            action="{{ route('settings.compliance_framework_packs.tenants.purge_unused_bootstrap', [$packKey, $tenant]) }}"
                                            style="display: inline-block; margin-top: 4px;"
                                            onsubmit="return confirm('{{ trans('admin/compliancepacks/general.purge_tenant_confirm') }}');"
                                        >
                                            @csrf
                                            <input type="hidden" name="confirm_purge_unused_bootstrap" value="1">
                                            <button type="submit" class="btn btn-sm btn-danger">
                                                <x-icon type="delete" />
                                                {{ trans('admin/compliancepacks/general.purge_tenant') }}
                                            </button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9">{{ trans('admin/compliancepacks/general.no_compatible_tenants') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-box>

        @include('compliancepacks.partials.events', ['events' => $latestEvents, 'showPack' => false])
    </x-container>
@stop
