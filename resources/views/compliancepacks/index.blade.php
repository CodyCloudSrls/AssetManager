@extends('layouts/default')

@section('title')
    {{ trans('admin/compliancepacks/general.title') }}
    @parent
@stop

@section('content')
    <x-container>
        <x-box :header="trans('admin/compliancepacks/general.index_title')">
            <form method="GET" action="{{ route('settings.compliance_framework_packs.index') }}" class="form-inline" style="margin-bottom: 15px;">
                <div class="form-group">
                    <label for="pack-filter-domain" class="sr-only">{{ trans('admin/compliancepacks/general.filter_domain') }}</label>
                    <select id="pack-filter-domain" name="domain" class="form-control input-sm">
                        <option value="">{{ trans('admin/compliancepacks/general.all_domains') }}</option>
                        @foreach ($filterOptions['domains'] as $value => $label)
                            <option value="{{ $value }}" {{ ($filters['domain'] ?? '') === $value ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label for="pack-filter-locale" class="sr-only">{{ trans('admin/compliancepacks/general.filter_locale') }}</label>
                    <select id="pack-filter-locale" name="locale" class="form-control input-sm">
                        <option value="">{{ trans('admin/compliancepacks/general.all_locales') }}</option>
                        @foreach ($filterOptions['locales'] as $value => $label)
                            <option value="{{ $value }}" {{ ($filters['locale'] ?? '') === $value ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label for="pack-filter-jurisdiction" class="sr-only">{{ trans('admin/compliancepacks/general.filter_jurisdiction') }}</label>
                    <select id="pack-filter-jurisdiction" name="jurisdiction" class="form-control input-sm">
                        <option value="">{{ trans('admin/compliancepacks/general.all_jurisdictions') }}</option>
                        @foreach ($filterOptions['jurisdictions'] as $value => $label)
                            <option value="{{ $value }}" {{ ($filters['jurisdiction'] ?? '') === $value ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label for="pack-filter-source-status" class="sr-only">{{ trans('admin/compliancepacks/general.filter_source_status') }}</label>
                    <select id="pack-filter-source-status" name="source_status" class="form-control input-sm">
                        <option value="">{{ trans('admin/compliancepacks/general.all_source_statuses') }}</option>
                        @foreach ($filterOptions['source_statuses'] as $value => $label)
                            <option value="{{ $value }}" {{ ($filters['source_status'] ?? '') === $value ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label for="pack-filter-system-status" class="sr-only">{{ trans('admin/compliancepacks/general.filter_system_status') }}</label>
                    <select id="pack-filter-system-status" name="system_status" class="form-control input-sm">
                        <option value="">{{ trans('admin/compliancepacks/general.all_system_statuses') }}</option>
                        @foreach ($filterOptions['system_statuses'] as $value => $label)
                            <option value="{{ $value }}" {{ ($filters['system_status'] ?? '') === $value ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label for="pack-filter-tenant-status" class="sr-only">{{ trans('admin/compliancepacks/general.filter_tenant_status') }}</label>
                    <select id="pack-filter-tenant-status" name="tenant_status" class="form-control input-sm">
                        <option value="">{{ trans('admin/compliancepacks/general.all_tenant_statuses') }}</option>
                        @foreach ($filterOptions['tenant_statuses'] as $value => $label)
                            <option value="{{ $value }}" {{ ($filters['tenant_status'] ?? '') === $value ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <button type="submit" class="btn btn-sm btn-primary">
                    <x-icon type="search" />
                    {{ trans('admin/compliancepacks/general.apply_filters') }}
                </button>
                @if (count($filters) > 0)
                    <a href="{{ route('settings.compliance_framework_packs.index') }}" class="btn btn-sm btn-default">
                        {{ trans('admin/compliancepacks/general.clear_filters') }}
                    </a>
                @endif
            </form>
            <div class="table-responsive">
                <table class="table table-striped snipe-table">
                    <thead>
                        <tr>
                            <th>{{ trans('admin/compliancepacks/general.pack') }}</th>
                            <th>{{ trans('admin/compliancepacks/general.locale') }}</th>
                            <th>{{ trans('admin/compliancepacks/general.domain') }}</th>
                            <th>{{ trans('admin/compliancepacks/general.jurisdiction') }}</th>
                            <th>{{ trans('admin/compliancepacks/general.source_status') }}</th>
                            <th>{{ trans('admin/compliancepacks/general.version') }}</th>
                            <th>{{ trans('admin/compliancepacks/general.checksum') }}</th>
                            <th>{{ trans('admin/compliancepacks/general.system_template') }}</th>
                            <th>{{ trans('admin/compliancepacks/general.tenants') }}</th>
                            <th>{{ trans('general.actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($packRows as $row)
                            <tr>
                                <td>
                                    <strong>{{ $row['name'] }}</strong><br>
                                    <code>{{ $row['key'] }}</code>
                                </td>
                                <td>{{ $dashboard->localeLabel($row['locale']) }}</td>
                                <td>{{ $dashboard->domainLabel($row['domain']) }}</td>
                                <td>{{ $row['jurisdiction'] ?: '-' }}</td>
                                <td>
                                    {{ $dashboard->sourceStatusLabel($row['source_status']) }}<br>
                                    <small class="text-muted">{{ $dashboard->sourceScopeLabel($row['source_scope']) }}</small>
                                </td>
                                <td>{{ $row['version'] }}</td>
                                <td><code title="{{ $row['checksum'] }}">{{ $dashboard->shortChecksum($row['checksum']) }}</code></td>
                                <td>
                                    <span class="label label-{{ $dashboard->statusLabelClass($row['system']['status']) }}">
                                        {{ $dashboard->statusLabel($row['system']['status']) }}
                                    </span>
                                </td>
                                <td>
                                    <span class="label label-success">{{ $row['tenant_counts']['current'] }}</span>
                                    <span class="label label-warning">{{ $row['tenant_counts']['outdated'] }}</span>
                                    <span class="label label-danger">{{ $row['tenant_counts']['modified'] }}</span>
                                    <span class="label label-default">{{ $row['tenant_counts']['missing_framework'] }}</span>
                                    @if ($row['tenant_counts']['actionable'] > 0)
                                        <span class="label label-info">{{ $row['tenant_counts']['actionable'] }}</span>
                                    @endif
                                </td>
                                <td>
                                    <a href="{{ route('settings.compliance_framework_packs.show', $row['key']) }}" class="btn btn-sm btn-primary">
                                        <x-icon type="search" />
                                        {{ trans('general.view') }}
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="10" class="text-muted">{{ trans('general.no_results') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-box>

        @include('compliancepacks.partials.events', ['events' => $latestEvents, 'showPack' => true])
    </x-container>
@stop
