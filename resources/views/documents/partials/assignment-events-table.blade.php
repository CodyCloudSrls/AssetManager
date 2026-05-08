@php
    $events = $events ?? collect();
    $assignment = $assignment ?? null;
    $fieldLabels = [
        'assignable_type' => trans('admin/documents/form.assignable_type'),
        'assignable_id' => trans('admin/documents/form.assignable_target'),
        'relation_type' => trans('admin/documents/form.assignment_relation'),
        'status' => trans('admin/documents/form.assignment_status'),
        'approval_status' => trans('admin/documents/form.assignment_approval_status'),
        'issuer_id' => trans('admin/documents/form.assignment_issuer'),
        'reviewer_id' => trans('admin/documents/form.assignment_reviewer'),
        'reference_number' => trans('admin/documents/form.assignment_reference_number'),
        'issued_at' => trans('admin/documents/form.assignment_issued_at'),
        'effective_at' => trans('admin/documents/form.assignment_effective_at'),
        'expires_at' => trans('admin/documents/form.assignment_expires_at'),
        'renewal_due_at' => trans('admin/documents/form.assignment_renewal_due_at'),
        'completed_at' => trans('admin/documents/form.assignment_completed_at'),
        'revoked_at' => trans('admin/documents/form.assignment_revoked_at'),
        'reviewed_at' => trans('admin/documents/form.assignment_reviewed_at'),
        'notes' => trans('admin/documents/form.assignment_notes'),
        'review_notes' => trans('admin/documents/form.assignment_review_notes'),
    ];
@endphp

<div class="table-responsive">
    <table class="table table-striped snipe-table">
        <thead>
            <tr>
                <th>{{ trans('general.date') }}</th>
                <th>{{ trans('general.action') }}</th>
                <th>{{ trans('admin/documents/form.assignable_target') }}</th>
                <th>{{ trans('admin/documents/form.assignment_approval_status') }}</th>
                <th>{{ trans('general.created_by') }}</th>
                <th>{{ trans('admin/documents/general.assignment_event_changes') }}</th>
                <th>{{ trans('admin/documents/general.assignment_event_hash') }}</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($events as $event)
                @php
                    $eventAssignment = $event->documentAssignment ?: $assignment;
                    $changedFields = collect(array_keys(array_merge($event->old_values ?? [], $event->new_values ?? [])))
                        ->map(fn ($field) => $fieldLabels[$field] ?? \Illuminate\Support\Str::headline($field))
                        ->implode(', ');
                @endphp
                <tr>
                    <td>{{ \App\Helpers\Helper::getFormattedDateObject($event->created_at, 'datetime', false) }}</td>
                    <td>{{ $event->event_type_label }}</td>
                    <td>
                        @if ($eventAssignment?->assignable_url)
                            <a href="{{ $eventAssignment->assignable_url }}">{{ $eventAssignment->assignable_display_name }}</a>
                        @else
                            {{ $eventAssignment?->assignable_display_name ?: trans('general.na') }}
                        @endif
                    </td>
                    <td>{{ $event->approval_status_label ?: trans('general.na') }}</td>
                    <td>{{ $event->actor?->display_name ?: trans('general.na') }}</td>
                    <td>{{ $changedFields ?: ($event->note ?: trans('general.na')) }}</td>
                    <td>
                        @if ($event->event_hash)
                            <span class="text-monospace" data-tooltip="true" title="{{ $event->event_hash }}">
                                {{ substr($event->event_hash, 0, 12) }}
                            </span>
                        @else
                            {{ trans('general.na') }}
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="text-muted">{{ trans('admin/documents/general.no_assignment_events') }}</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
