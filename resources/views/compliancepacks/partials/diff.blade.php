<dl class="dl-horizontal">
    <dt>{{ trans('admin/compliancepacks/general.status') }}</dt>
    <dd>
        <span class="label label-{{ $dashboard->statusLabelClass($diff['status']) }}">
            {{ $dashboard->statusLabel($diff['status']) }}
        </span>
    </dd>

    <dt>{{ trans('admin/compliancepacks/general.source_version') }}</dt>
    <dd>{{ $diff['source_pack_version'] ?: '-' }}</dd>

    <dt>{{ trans('admin/compliancepacks/general.pack_version') }}</dt>
    <dd>{{ $diff['pack_version'] ?: '-' }}</dd>

    <dt>{{ trans('admin/compliancepacks/general.framework_changes') }}</dt>
    <dd>{{ count($diff['framework_changes']) }}</dd>

    <dt>{{ trans('admin/compliancepacks/general.missing_requirements') }}</dt>
    <dd>{{ count($diff['missing_requirements']) }}</dd>

    <dt>{{ trans('admin/compliancepacks/general.changed_requirements') }}</dt>
    <dd>{{ count($diff['changed_requirements']) }}</dd>

    <dt>{{ trans('admin/compliancepacks/general.custom_requirements') }}</dt>
    <dd>{{ count($diff['custom_requirements']) }}</dd>

    <dt>{{ trans('admin/compliancepacks/general.conflicts') }}</dt>
    <dd>{{ $diff['conflicts_count'] }}</dd>
</dl>

@if (count($diff['framework_changes']) > 0)
    <p><strong>{{ trans('admin/compliancepacks/general.framework_changes') }}</strong></p>
    <p><code>{{ implode(', ', array_keys($diff['framework_changes'])) }}</code></p>
@endif

@if (count($diff['missing_requirements']) > 0)
    <p><strong>{{ trans('admin/compliancepacks/general.missing_requirements') }}</strong></p>
    <p><code>{{ implode(', ', $diff['missing_requirements']) }}</code></p>
@endif

@if (count($diff['changed_requirements']) > 0)
    <p><strong>{{ trans('admin/compliancepacks/general.changed_requirements') }}</strong></p>
    <p><code>{{ implode(', ', array_keys($diff['changed_requirements'])) }}</code></p>
@endif
