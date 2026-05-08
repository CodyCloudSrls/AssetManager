@php
    $assignments = $assignments ?? collect();
    $showDocumentColumn = $showDocumentColumn ?? false;
    $showTargetColumn = $showTargetColumn ?? true;
    $document = $document ?? null;
    $showActions = $showActions ?? false;
@endphp

<div class="table-responsive">
    <table class="table table-striped snipe-table">
        <thead>
            <tr>
                @if ($showDocumentColumn)
                    <th>{{ trans('general.document') }}</th>
                @endif
                @if ($showTargetColumn)
                    <th>{{ trans('admin/documents/form.assignable_type') }}</th>
                    <th>{{ trans('admin/documents/form.assignable_target') }}</th>
                @endif
                <th>{{ trans('admin/documents/form.assignment_relation') }}</th>
                <th>{{ trans('admin/documents/form.assignment_status') }}</th>
                <th>{{ trans('admin/documents/form.assignment_reference_number') }}</th>
                <th>{{ trans('admin/documents/form.assignment_issuer') }}</th>
                <th>{{ trans('admin/documents/form.assignment_effective_at') }}</th>
                <th>{{ trans('admin/documents/form.assignment_expires_at') }}</th>
                <th>{{ trans('admin/documents/form.assignment_renewal_due_at') }}</th>
                <th>{{ trans('admin/documents/form.assignment_notes') }}</th>
                @if ($showActions)
                    <th class="text-right">{{ trans('table.actions') }}</th>
                @endif
            </tr>
        </thead>
        <tbody>
            @forelse ($assignments as $assignment)
                @php
                    $statusClass = match ($assignment->status) {
                        \App\Models\DocumentAssignment::STATUS_ACTIVE => 'label label-success',
                        \App\Models\DocumentAssignment::STATUS_REQUIRED => 'label label-warning',
                        \App\Models\DocumentAssignment::STATUS_COMPLETED => 'label label-primary',
                        \App\Models\DocumentAssignment::STATUS_EXPIRED,
                        \App\Models\DocumentAssignment::STATUS_REVOKED => 'label label-danger',
                        default => 'label label-default',
                    };
                    $actionDocument = $document ?: $assignment->document;
                    $canManageAssignment = $showActions
                        && $actionDocument
                        && auth()->user()
                        && auth()->user()->can('update', $actionDocument);
                @endphp
                <tr>
                    @if ($showDocumentColumn)
                        <td>
                            @if ($assignment->document)
                                <a href="{{ route('documents.show', $assignment->document) }}">{{ $assignment->document->name }}</a>
                            @endif
                        </td>
                    @endif
                    @if ($showTargetColumn)
                        <td>{{ $assignment->assignable_type_label }}</td>
                        <td>
                            @if ($assignment->assignable_url)
                                <a href="{{ $assignment->assignable_url }}">{{ $assignment->assignable_display_name }}</a>
                            @else
                                {{ $assignment->assignable_display_name ?: '—' }}
                            @endif
                        </td>
                    @endif
                    <td>{{ $assignment->relation_type_label }}</td>
                    <td>
                        <span class="{{ $statusClass }}">{{ $assignment->status_label }}</span>
                        @if ($assignment->is_expired)
                            <div class="text-danger" style="margin-top: 4px;">{{ trans('admin/documents/general.assignment_expired_flag') }}</div>
                        @elseif ($assignment->is_expiring)
                            <div class="text-warning" style="margin-top: 4px;">{{ trans('admin/documents/general.assignment_expiring_flag') }}</div>
                        @endif
                    </td>
                    <td>{{ $assignment->reference_number }}</td>
                    <td>{{ $assignment->issuer?->display_name }}</td>
                    <td>{{ \App\Helpers\Helper::getFormattedDateObject($assignment->effective_at, 'date', false) }}</td>
                    <td>{{ \App\Helpers\Helper::getFormattedDateObject($assignment->expires_at, 'date', false) }}</td>
                    <td>{{ \App\Helpers\Helper::getFormattedDateObject($assignment->renewal_due_at, 'date', false) }}</td>
                    <td>{{ \Illuminate\Support\Str::limit(trim(strip_tags((string) $assignment->notes)), 120) }}</td>
                    @if ($showActions)
                        <td class="text-right">
                            @if ($canManageAssignment)
                                <a href="{{ route('documents.assignments.edit', [$actionDocument, $assignment]) }}"
                                   class="btn btn-sm btn-warning"
                                   data-tooltip="true"
                                   title="{{ trans('general.edit') }}">
                                    <x-icon type="edit" class="fa-fw" />
                                </a>
                                <a href="{{ route('documents.assignments.destroy', [$actionDocument, $assignment]) }}"
                                   class="btn btn-sm btn-danger delete-asset"
                                   data-toggle="modal"
                                   data-title="{{ trans('general.delete') }}"
                                   data-content="{{ trans('general.delete_confirm', ['item' => $assignment->assignable_display_name ?: trans('general.document')]) }}"
                                   data-target="#dataConfirmModal"
                                   data-tooltip="true"
                                   data-icon="fa fa-trash"
                                   data-placement="top"
                                   onClick="return false;">
                                    <x-icon type="delete" class="fa-fw" />
                                </a>
                            @endif
                        </td>
                    @endif
                </tr>
            @empty
                <tr>
                    <td colspan="{{ ($showDocumentColumn ? 1 : 0) + ($showTargetColumn ? 2 : 0) + 8 + ($showActions ? 1 : 0) }}" class="text-muted">
                        {{ trans('general.no_results') }}
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
