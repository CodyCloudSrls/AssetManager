<x-box :header="trans('admin/compliancepacks/general.audit_events')">
    <div class="table-responsive">
        <table class="table table-striped snipe-table">
            <thead>
                <tr>
                    <th>{{ trans('general.created_at') }}</th>
                    @if ($showPack)
                        <th>{{ trans('admin/compliancepacks/general.pack') }}</th>
                    @endif
                    <th>{{ trans('admin/compliancepacks/general.scope') }}</th>
                    <th>{{ trans('admin/compliancepacks/general.event_type') }}</th>
                    <th>{{ trans('admin/compliancepacks/general.tenant') }}</th>
                    <th>{{ trans('admin/compliancepacks/general.pack_checksum') }}</th>
                    <th>{{ trans('admin/compliancepacks/general.event_hash') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($events as $event)
                    <tr>
                        <td>{{ Helper::getFormattedDateObject($event->created_at, 'datetime', false) }}</td>
                        @if ($showPack)
                            <td><code>{{ $event->pack_key }}</code></td>
                        @endif
                        <td>{{ $dashboard->scopeLabel($event->scope) }}</td>
                        <td>{{ $dashboard->eventTypeLabel($event->event_type) }}</td>
                        <td>{{ $event->company?->name ?? $event->tenant?->rootCompany()?->name ?? '-' }}</td>
                        <td><code title="{{ $event->pack_checksum }}">{{ substr($event->pack_checksum, 0, 16) }}</code></td>
                        <td><code title="{{ $event->event_hash }}">{{ substr((string) $event->event_hash, 0, 16) }}</code></td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ $showPack ? 7 : 6 }}">{{ trans('admin/compliancepacks/general.no_audit_events') }}</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</x-box>
