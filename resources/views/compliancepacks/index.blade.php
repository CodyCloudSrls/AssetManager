@extends('layouts/default')

@section('title')
    {{ trans('admin/compliancepacks/general.title') }}
    @parent
@stop

@section('content')
    <x-container>
        <x-box :header="trans('admin/compliancepacks/general.index_title')">
            <div class="table-responsive">
                <table class="table table-striped snipe-table">
                    <thead>
                        <tr>
                            <th>{{ trans('admin/compliancepacks/general.pack') }}</th>
                            <th>{{ trans('admin/compliancepacks/general.locale') }}</th>
                            <th>{{ trans('admin/compliancepacks/general.domain') }}</th>
                            <th>{{ trans('admin/compliancepacks/general.version') }}</th>
                            <th>{{ trans('admin/compliancepacks/general.checksum') }}</th>
                            <th>{{ trans('admin/compliancepacks/general.system_template') }}</th>
                            <th>{{ trans('admin/compliancepacks/general.tenants') }}</th>
                            <th>{{ trans('general.actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($packRows as $row)
                            <tr>
                                <td>
                                    <strong>{{ $row['name'] }}</strong><br>
                                    <code>{{ $row['key'] }}</code>
                                </td>
                                <td>{{ $dashboard->localeLabel($row['locale']) }}</td>
                                <td>{{ $dashboard->domainLabel($row['domain']) }}</td>
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
                        @endforeach
                    </tbody>
                </table>
            </div>
        </x-box>

        @include('compliancepacks.partials.events', ['events' => $latestEvents, 'showPack' => true])
    </x-container>
@stop
