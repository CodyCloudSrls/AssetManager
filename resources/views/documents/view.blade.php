@extends('layouts/default')

@section('title')
    {{ trans('admin/documents/general.view') }} {{ $document->name }}
    @parent
@stop

@section('header_right')
    <a href="{{ route('documents.index') }}" class="btn btn-default">
        <x-icon type="angle-left" class="fa-fw" /> {{ trans('general.back_to_list') }}
    </a>
    <x-button.info-panel-toggle/>
@endsection

@section('content')
    @php
        $assignmentCount = $document->documentAssignments->count();
        $assignmentPreview = $document->documentAssignments->take(5);
        $remainingAssignmentCount = max(0, $assignmentCount - $assignmentPreview->count());
        $statusLabel = \App\Models\Document::getStatusOptions()[$document->status] ?? $document->status;
        $statusClass = match ($document->status) {
            \App\Models\Document::STATUS_ACTIVE => 'label label-success',
            \App\Models\Document::STATUS_IN_REVIEW => 'label label-warning',
            \App\Models\Document::STATUS_OBSOLETE,
            \App\Models\Document::STATUS_ARCHIVED => 'label label-danger',
            default => 'label label-default',
        };

        $activeAssignmentsCount = $document->documentAssignments->filter(function ($assignment) {
            return in_array($assignment->status, [
                \App\Models\DocumentAssignment::STATUS_PLANNED,
                \App\Models\DocumentAssignment::STATUS_REQUIRED,
                \App\Models\DocumentAssignment::STATUS_ACTIVE,
            ], true);
        })->count();
        $renewalDueCount = $document->documentAssignments->filter(fn ($assignment) => $assignment->is_expiring)->count();
        $expiredAssignmentsCount = $document->documentAssignments->filter(fn ($assignment) => $assignment->is_expired)->count();

        // Elapsed share of the real span start->end (0% at start, 100% at/after end).
        // Previously this used a fixed 3-year window counted backwards from today with no
        // start baseline, so a doc 4 days into a 2-year span read ~34% instead of ~0.5%.
        $datePercent = function ($start, $end) {
            if (! $end) {
                return 0;
            }

            $endDate = $end instanceof \Carbon\Carbon ? $end : \Carbon\Carbon::parse($end);
            $today = \Carbon\Carbon::today();

            if ($today->gte($endDate)) {
                return 100; // at or past the end date
            }

            $startDate = $start
                ? ($start instanceof \Carbon\Carbon ? $start : \Carbon\Carbon::parse($start))
                : $today;

            if ($startDate->gte($endDate)) {
                return 100; // missing/invalid span
            }

            $total = $startDate->diffInDays($endDate);
            $elapsed = $startDate->diffInDays($today, false);

            if ($elapsed <= 0) {
                return 0; // span not started yet
            }

            return min(100, max(0, ($elapsed / $total) * 100));
        };

        $reviewPercent = $datePercent($document->effective_at, $document->next_review_at);
        $reviewDate = $document->next_review_at ? \Carbon\Carbon::parse($document->next_review_at) : null;
        $isReviewOverdue = $reviewDate?->isPast() ?? false;
        $isReviewDueSoon = ! $isReviewOverdue && $reviewDate?->lte(\Carbon\Carbon::today()->addDays(30));

        $nextRenewalAssignment = $document->documentAssignments
            ->filter(fn ($assignment) => ! is_null($assignment->renewal_due_at))
            ->sortBy('renewal_due_at')
            ->first();
        $renewalPercent = $datePercent(
            $nextRenewalAssignment?->effective_at ?? $nextRenewalAssignment?->issued_at,
            $nextRenewalAssignment?->renewal_due_at
        );

        $nextExpiryAssignment = $document->documentAssignments
            ->filter(fn ($assignment) => ! is_null($assignment->expires_at))
            ->sortBy('expires_at')
            ->first();
        $expiryPercent = $datePercent(
            $nextExpiryAssignment?->effective_at ?? $nextExpiryAssignment?->issued_at,
            $nextExpiryAssignment?->expires_at
        );
    @endphp
    <x-container columns="2">
        <x-page-column class="col-md-9 main-panel">
            <x-tabs>
                <x-slot:tabnav>
                    <x-tabs.details-tab/>
                    <x-tabs.nav-item
                        name="assignments"
                        label="{{ trans('admin/documents/general.assignments') }}"
                        count="{{ $document->documentAssignments->count() }}"
                        tooltip="{{ trans('admin/documents/general.assignments') }}"
                    />
                    @can('view', \App\Models\Ticket::class)
                        <x-tabs.nav-item
                            name="tickets"
                            icon="fa-solid fa-life-ring fa-fw"
                            label="{{ trans('general.tickets') }}"
                            count="{{ $document->tickets()->count() }}"
                            tooltip="{{ trans('general.tickets') }}"
                        />
                    @endcan
                    <x-tabs.note-tab :item="$document" count="{{ $document->journal->count() }}"/>
                    <x-tabs.files-tab :item="$document" count="{{ $document->uploads()->count() }}"/>
                    <x-tabs.history-tab count="{{ $document->history()->count() }}" :model="$document"/>
                    <x-tabs.upload-tab :item="$document"/>
                </x-slot:tabnav>

                <x-slot:tabpanes>
                    <x-tabs.pane name="details">
                        <div class="clearfix visible-lg-block" style="padding: 6px;"></div>

                        <x-page-column class="col-md-4 col-sm-12">
                            <x-well>
                                <span class="progress-text">{{ trans('general.status') }}</span>
                                <div style="margin-top: 10px;">
                                    <span class="{{ $statusClass }}">{{ $statusLabel }}</span>
                                </div>
                                @if ($activeAssignmentsCount > 0)
                                    <div class="text-muted" style="margin-top: 8px;">
                                        {{ trans('admin/documents/general.assignments') }}: {{ $activeAssignmentsCount }}
                                    </div>
                                @endif
                            </x-well>
                        </x-page-column>

                        <x-page-column class="col-md-4 col-sm-12">
                            <x-well>
                                <x-icon type="calendar" class="fa-fw"/>
                                <strong>{{ trans('admin/documents/form.next_review_at') }}</strong>
                                @if ($document->next_review_at)
                                    {{ Helper::getFormattedDateObject($document->next_review_at, 'date', false) }}
                                    <span class="text-muted">{{ \Carbon\Carbon::parse($document->next_review_at)->diffForHumans(['parts' => 2]) }}</span>
                                @else
                                    {{ trans('general.na') }}
                                @endif
                            </x-well>
                        </x-page-column>

                        <x-page-column class="col-md-4 col-sm-12">
                            <x-well>
                                <x-icon type="checkout" class="fa-fw"/>
                                <strong>{{ trans('admin/documents/general.assignments') }}</strong>
                                @if ($assignmentCount > 0)
                                    {{ $assignmentCount }}
                                    <span class="text-muted">
                                        @if ($renewalDueCount > 0)
                                            • {{ $renewalDueCount }} {{ trans('admin/documents/general.assignment_expiring_flag') }}
                                        @elseif ($expiredAssignmentsCount > 0)
                                            • {{ $expiredAssignmentsCount }} {{ trans('admin/documents/general.assignment_expired_flag') }}
                                        @endif
                                    </span>
                                @else
                                    {{ trans('general.na') }}
                                @endif
                            </x-well>
                        </x-page-column>

                        <div class="clearfix"></div>

                        <x-page-column class="col-md-8 col-sm-12">
                            <x-page-data>
                                <x-data-row :label="trans('admin/documents/form.document_number')">{{ $document->document_number }}</x-data-row>
                                <x-data-row :label="trans('admin/documents/form.document_type')">{{ $document->type?->name }}</x-data-row>
                                <x-data-row :label="trans('admin/documents/form.framework')">{{ $document->framework?->name }}</x-data-row>
                                <x-data-row :label="trans('admin/documents/form.framework_requirements')">
                                    @if ($document->frameworkRequirements->count() > 0)
                                        <ul style="padding-left: 18px; margin-bottom: 0;">
                                            @foreach ($document->frameworkRequirements as $requirement)
                                                <li>
                                                    <a href="{{ route('documentframeworkrequirements.show', $requirement) }}">{{ $requirement->code }} - {{ $requirement->title }}</a>
                                                    <span class="text-muted">({{ \App\Models\Document::coverageRoleOptions()[$requirement->pivot->coverage_role] ?? $requirement->pivot->coverage_role }})</span>
                                                </li>
                                            @endforeach
                                        </ul>
                                    @endif
                                </x-data-row>
                                <x-data-row :label="trans('admin/documents/form.reference')">{{ $document->reference }}</x-data-row>
                                <x-data-row :label="trans('admin/documents/form.version')">{{ $document->version }}</x-data-row>
                                <x-data-row :label="trans('general.status')">
                                    <span class="{{ $statusClass }}">{{ $statusLabel }}</span>
                                </x-data-row>
                                <x-data-row :label="trans('general.company')">{{ $document->company?->name }}</x-data-row>
                                <x-data-row :label="trans('admin/documents/form.owner')">{{ $document->owner?->display_name }}</x-data-row>
                                <x-data-row :label="trans('admin/documents/form.classification')">{{ $document->classification }}</x-data-row>
                                <x-data-row :label="trans('admin/documents/form.retention_period')">{{ $document->retention_period }}</x-data-row>
                                <x-data-row :label="trans('admin/documents/form.scope')">{{ $document->scope }}</x-data-row>
                                <x-data-row :label="trans('admin/tenantservices/general.field_label')">
                                    @if ($document->tenantServices->count() > 0)
                                        <ul style="padding-left: 18px; margin-bottom: 0;">
                                            @foreach ($document->tenantServices as $tenantService)
                                                <li>
                                                    <a href="{{ route('tenants.services.index', $tenantService->tenant_id) }}">{{ $tenantService->name }}</a>
                                                    <span class="text-muted">({{ $tenantService->macro_area_label }}, {{ $tenantService->assigned_relevance_label }})</span>
                                                </li>
                                            @endforeach
                                        </ul>
                                    @endif
                                </x-data-row>
                                <x-data-row :label="trans('admin/documents/form.issued_at')">{{ Helper::getFormattedDateObject($document->issued_at, 'date', false) }}</x-data-row>
                                <x-data-row :label="trans('admin/documents/form.effective_at')">{{ Helper::getFormattedDateObject($document->effective_at, 'date', false) }}</x-data-row>
                                <x-data-row :label="trans('admin/documents/form.next_review_at')">
                                    {{ Helper::getFormattedDateObject($document->next_review_at, 'date', false) }}
                                    @if ($document->next_review_at)
                                        <span class="text-muted"> - {{ \Carbon\Carbon::parse($document->next_review_at)->diffForHumans(['parts' => 2]) }}</span>
                                        @if ($isReviewOverdue)
                                            <span class="text-danger"> {{ trans('admin/documents/general.review_overdue') }}</span>
                                        @elseif ($isReviewDueSoon)
                                            <span class="text-warning"> {{ trans('admin/documents/general.review_due') }}</span>
                                        @endif
                                    @endif
                                </x-data-row>
                                <x-data-row :label="trans('admin/documents/general.assignments')">
                                    @if ($assignmentCount > 0)
                                        <ul style="padding-left: 18px; margin-bottom: 8px;">
                                            @foreach ($assignmentPreview as $assignment)
                                                <li>
                                                    @if ($assignment->assignable_url)
                                                        <a href="{{ $assignment->assignable_url }}">{{ $assignment->assignable_display_name }}</a>
                                                    @else
                                                        {{ $assignment->assignable_display_name ?: '—' }}
                                                    @endif
                                                    <span class="text-muted">({{ $assignment->relation_type_label }}, {{ $assignment->status_label }})</span>
                                                </li>
                                            @endforeach
                                            @if ($remainingAssignmentCount > 0)
                                                <li class="text-muted">+{{ $remainingAssignmentCount }}</li>
                                            @endif
                                        </ul>
                                    @endif
                                    <a href="#assignments" data-toggle="tab">
                                        {{ trans('admin/documents/general.assignments') }}@if ($assignmentCount > 0) ({{ $assignmentCount }}) @endif
                                    </a>
                                </x-data-row>
                                <x-data-row :label="trans('admin/documents/form.control_url')">
                                    @if ($document->control_url)
                                        <a href="{{ $document->control_url }}" target="_blank" rel="noopener noreferrer">{{ $document->control_url }}</a>
                                    @endif
                                </x-data-row>
                                <x-data-row :label="trans('admin/documents/form.summary')">
                                    {!! $document->summary ? \App\Helpers\Helper::parseEscapedMarkedown($document->summary) : '' !!}
                                </x-data-row>
                                <x-data-row :label="trans('general.notes')">
                                    {!! $document->notes ? \App\Helpers\Helper::parseEscapedMarkedown($document->notes) : '' !!}
                                </x-data-row>
                            </x-page-data>
                        </x-page-column>

                        <x-page-column class="col-md-4 col-sm-12">
                            @if ($document->next_review_at || $nextRenewalAssignment || $nextExpiryAssignment)
                                <x-well class="well-sm">
                                    @if ($document->next_review_at)
                                        <x-progressbar use_well="false" columns="12" :text="trans('admin/documents/form.next_review_at')" :percent="$reviewPercent">
                                            {{ Helper::getFormattedDateObject($document->next_review_at, 'date', false) }}
                                        </x-progressbar>
                                    @endif

                                    @if ($nextRenewalAssignment)
                                        <x-progressbar use_well="false" columns="12" :text="trans('admin/documents/form.assignment_renewal_due_at')" :percent="$renewalPercent">
                                            {{ Helper::getFormattedDateObject($nextRenewalAssignment->renewal_due_at, 'date', false) }}
                                        </x-progressbar>
                                    @endif

                                    @if ($nextExpiryAssignment)
                                        <x-progressbar use_well="false" columns="12" :text="trans('admin/documents/form.assignment_expires_at')" :percent="$expiryPercent">
                                            {{ Helper::getFormattedDateObject($nextExpiryAssignment->expires_at, 'date', false) }}
                                        </x-progressbar>
                                    @endif
                                </x-well>
                            @endif

                            <x-well class="well-sm">
                                <div class="well-display">
                                    <x-data-row icon_type="checkout" :label="trans('admin/documents/general.assignments')">
                                        {{ $assignmentCount }}
                                    </x-data-row>
                                    <x-data-row :label="trans('general.status')">
                                        {{ $activeAssignmentsCount }}
                                    </x-data-row>
                                    <x-data-row :label="trans('admin/documents/general.assignment_expiring_flag')">
                                        {{ $renewalDueCount }}
                                    </x-data-row>
                                    <x-data-row :label="trans('admin/documents/general.assignment_expired_flag')">
                                        {{ $expiredAssignmentsCount }}
                                    </x-data-row>
                                </div>
                            </x-well>
                        </x-page-column>
                    </x-tabs.pane>

                    <x-tabs.pane name="assignments">
                        <div class="box box-default">
                            <div class="box-header with-border">
                                <h3 class="box-title">{{ trans('admin/documents/general.assignments') }}</h3>
                            </div>
                            <div class="box-body">
                                @include('documents.partials.assignments-table', [
                                    'assignments' => $document->documentAssignments,
                                    'document' => $document,
                                    'showActions' => auth()->user()->can('update', $document),
                                ])

                                <h3>{{ trans('admin/documents/general.assignment_audit_events') }}</h3>
                                @include('documents.partials.assignment-events-table', [
                                    'events' => $document->documentAssignmentEvents,
                                ])
                            </div>
                        </div>
                    </x-tabs.pane>

                    @can('view', \App\Models\Ticket::class)
                        <x-tabs.pane name="tickets">
                            <x-table.tickets :route="route('api.tickets.index', ['document_id' => $document->id])"/>
                        </x-tabs.pane>
                    @endcan

                    <x-tabs.pane name="notes">
                        <x-table.history
                            :table_header="trans('general.notes')"
                            :model="$document"
                            :route="route('api.activity.index', ['item_id' => $document->id, 'item_type' => 'document', 'action_type' => 'note added'])"
                            :hide_fields="['item', 'target', 'file', 'file_download', 'action_date', 'log_meta', 'quantity']"
                        />
                    </x-tabs.pane>

                    <x-tabs.pane name="files">
                        <x-table.files object_type="documents" :object="$document"/>
                    </x-tabs.pane>

                    <x-tabs.pane name="history">
                        <x-table.history :route="route('api.documents.history', $document)" :model="$document"/>
                    </x-tabs.pane>
                </x-slot:tabpanes>
            </x-tabs>
        </x-page-column>

        <x-page-column class="col-md-3">
            <x-box class="side-box expanded">
                <x-info-panel :infoPanelObj="$document">
                    <x-slot:buttons>
                        <x-button.edit :item="$document" :route="route('documents.edit', $document)" />
                        <x-button.delete :item="$document" />
                    </x-slot:buttons>

                    <x-info-element icon="fa-regular fa-file-lines fa-fw" :title="trans('general.name')">
                        <x-copy-to-clipboard class="pull-right" copy_what="document_name">{{ $document->name }}</x-copy-to-clipboard>
                    </x-info-element>

                    <x-info-element icon="fas fa-hashtag fa-fw" :title="trans('admin/documents/form.document_number')">
                        <x-copy-to-clipboard class="pull-right" copy_what="document_number">{{ $document->document_number }}</x-copy-to-clipboard>
                    </x-info-element>

                    <x-info-element icon="fas fa-folder-open fa-fw" :title="trans('admin/documents/form.document_type')">
                        {{ $document->type?->name }}
                    </x-info-element>

                    <x-info-element icon="fas fa-sitemap fa-fw" :title="trans('admin/documents/form.framework')">
                        {{ $document->framework?->name }}
                    </x-info-element>

                    <x-info-element icon="fas fa-building fa-fw" :title="trans('general.company')">
                        {{ $document->company?->name }}
                    </x-info-element>

                    <x-info-element icon="fas fa-user fa-fw" :title="trans('admin/documents/form.owner')">
                        {{ $document->owner?->display_name }}
                    </x-info-element>

                    <x-info-element icon="fas fa-link fa-fw" :title="trans('admin/documents/general.assignments')">
                        <a href="#assignments" data-toggle="tab">
                            {{ $assignmentCount }} {{ trans('admin/documents/general.assignments') }}
                        </a>
                    </x-info-element>

                    @can('view', \App\Models\Ticket::class)
                        <x-info-element icon="fa-solid fa-life-ring fa-fw" :title="trans('general.tickets')">
                            <a href="#tickets" data-toggle="tab">
                                {{ $document->tickets()->count() }} {{ trans('general.tickets') }}
                            </a>
                        </x-info-element>
                    @endcan

                    <x-info-element icon="fas fa-calendar-day fa-fw" :title="trans('admin/documents/form.issued_at')">
                        {{ Helper::getFormattedDateObject($document->issued_at, 'date', false) }}
                    </x-info-element>

                    <x-info-element icon="fas fa-calendar-check fa-fw" :title="trans('admin/documents/form.effective_at')">
                        {{ Helper::getFormattedDateObject($document->effective_at, 'date', false) }}
                    </x-info-element>

                    <x-info-element icon="fas fa-calendar-alt fa-fw" :title="trans('admin/documents/form.next_review_at')">
                        {{ Helper::getFormattedDateObject($document->next_review_at, 'date', false) }}
                    </x-info-element>

                    @if ($document->control_url)
                        <x-info-element icon="fas fa-link fa-fw" :title="trans('admin/documents/form.control_url')">
                            <a href="{{ $document->control_url }}" target="_blank" rel="noopener noreferrer">{{ $document->control_url }}</a>
                        </x-info-element>
                    @endif

                    <x-info-element icon="fas fa-calendar-plus fa-fw" :title="trans('general.created_at')">
                        {{ Helper::getFormattedDateObject($document->created_at, 'datetime', false) }}
                    </x-info-element>

                    <x-info-element icon="fas fa-calendar-check fa-fw" :title="trans('general.updated_at')">
                        {{ Helper::getFormattedDateObject($document->updated_at, 'datetime', false) }}
                    </x-info-element>
                </x-info-panel>

                <div class="box-body">
                    <div style="margin-top: 15px;">
                        <x-button.note :item="$document" wide="true" />
                    </div>
                </div>
            </x-box>
        </x-page-column>
    </x-container>
@endsection

@section('moar_scripts')
    @can('files', $document)
        @include ('modals.upload-file', ['item_type' => 'documents', 'item_id' => $document->id])
    @endcan

    @include ('modals.add-note', ['type' => 'document', 'id' => $document->id])
    @include ('partials.bootstrap-table')
@endsection
