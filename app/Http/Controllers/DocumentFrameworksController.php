<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreDocumentFrameworkRequest;
use App\Models\Document;
use App\Models\DocumentFramework;
use App\Models\DocumentFrameworkRequirement;
use App\Models\Tenant;
use App\Support\Compliance\ComplianceDomainAccess;
use App\Support\Compliance\ComplianceFrameworkPackPurger;
use App\Support\Compliance\ConsultantFrameworkTransfer;
use App\Support\Documents\DocumentAreaAccess;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class DocumentFrameworksController extends Controller
{
    public function index(): View
    {
        $this->authorize('index', DocumentFramework::class);

        return view('documentframeworks.index')->with('document_framework_count', DocumentFramework::withTrashed()->operational()->count());
    }

    public function create(): View
    {
        $this->authorize('create', DocumentFramework::class);

        return view('documentframeworks.edit', $this->formData(new DocumentFramework([
            'status' => 'active',
            'is_active' => true,
        ])));
    }

    public function importForm(): View
    {
        $this->authorize('create', DocumentFramework::class);

        return view('documentframeworks.import', $this->formData(new DocumentFramework([
            'status' => 'active',
            'is_active' => true,
        ])));
    }

    public function store(StoreDocumentFrameworkRequest $request): RedirectResponse
    {
        $this->authorize('create', DocumentFramework::class);

        $documentFramework = new DocumentFramework;
        $documentFramework->fill($request->validated());
        $documentFramework->created_by = auth()->id();
        $documentFramework->status = $request->input('status', 'active');
        $documentFramework->is_active = $request->boolean('is_active', true);

        if ($documentFramework->save()) {
            return redirect()->route('documentframeworks.index')->with('success', trans('admin/documentframeworks/message.create.success'));
        }

        return redirect()->back()->withInput()->withErrors($documentFramework->getErrors());
    }

    public function import(Request $request, ConsultantFrameworkTransfer $transfer): RedirectResponse
    {
        $this->authorize('create', DocumentFramework::class);

        $validated = $request->validate([
            'file' => 'required|file|max:10240',
            'company_id' => 'nullable|integer|exists:companies,id',
            'visibility_type' => 'required|string|in:private,descendants,global',
        ]);

        if (($validated['visibility_type'] ?? null) === DocumentFramework::VISIBILITY_GLOBAL) {
            $canManageGlobalFramework = Tenant::canCurrentUserUseGlobalTenantContext();

            if (! $canManageGlobalFramework) {
                return redirect()->back()
                    ->withInput()
                    ->withErrors(['visibility_type' => trans('validation.in', ['attribute' => trans('general.template_visibility.label')])]);
            }
        } elseif (blank($validated['company_id'] ?? null)) {
            return redirect()->back()
                ->withInput()
                ->withErrors(['company_id' => trans('validation.required', ['attribute' => trans('general.company')])]);
        }

        try {
            $result = $transfer->import($request->file('file'), $validated, auth()->id());
        } catch (ValidationException $exception) {
            throw $exception;
        } catch (\Throwable $exception) {
            Log::warning('Document framework import failed.', ['exception' => $exception]);

            return redirect()->back()
                ->withInput()
                ->withErrors(['file' => trans('admin/documentframeworks/message.import.parse_error')]);
        }

        return redirect()
            ->route('documentframeworks.show', $result['framework'])
            ->with('success', trans('admin/documentframeworks/message.import.success', ['count' => $result['requirements_count']]));
    }

    public function show(DocumentFramework $documentframework, ComplianceFrameworkPackPurger $purger): View
    {
        $this->authorize('view', $documentframework);

        $documentframework->loadCount([
            'documents' => fn ($query) => $this->applyVisibleDocumentScope($query),
            'requirements',
        ]);

        $requirements = DocumentFrameworkRequirement::query()
            ->forFramework($documentframework->id)
            ->with(['owner', 'defaultDocumentType'])
            ->withCount($this->visibleDocumentCoverageCounts())
            ->ordered()
            ->get();

        $documentframework->setRelation('requirements', $requirements);

        return view('documentframeworks.view', [
            'documentframework' => $documentframework,
            'canPurgeUnusedBootstrap' => $purger->canPurgeFramework($documentframework),
        ]);
    }

    public function requirementsMatrix(DocumentFramework $documentframework): View
    {
        $this->authorize('view', $documentframework);

        $documentframework->loadMissing(['company.tenant', 'owner'])
            ->loadCount([
                'documents' => fn ($query) => $this->applyVisibleDocumentScope($query),
                'requirements',
            ]);

        $requirementRelations = [
            'owner',
            'defaultDocumentType',
            'parent',
            'documents' => function ($query) {
                $this->applyVisibleDocumentScope($query);
                $query->with(['owner', 'type'])
                    ->orderByRaw("case document_framework_requirement_document.coverage_role when 'primary' then 0 else 1 end")
                    ->orderByRaw('case when documents.next_review_at is null then 1 else 0 end')
                    ->orderBy('documents.next_review_at')
                    ->orderBy('documents.name');
            },
        ];

        if (DocumentFrameworkRequirement::parentPivotTableExists()) {
            $requirementRelations[] = 'parents';
        }

        $requirements = DocumentFrameworkRequirement::query()
            ->forFramework($documentframework->id)
            ->with($requirementRelations)
            ->withCount($this->visibleDocumentCoverageCounts())
            ->ordered()
            ->get();

        $documentframework->setRelation('requirements', $requirements);

        $reviewWarningDays = $documentframework->company?->tenant?->documentReviewWarningDays() ?? 30;

        return view('documentframeworks.requirements-matrix', [
            'documentframework' => $documentframework,
            'coverageSummary' => $documentframework->coverage_summary,
            'matrixRows' => $this->requirementMatrixRows($requirements, $reviewWarningDays),
            'reviewWarningDays' => $reviewWarningDays,
        ]);
    }

    public function export(
        DocumentFramework $documentframework,
        string $format,
        ConsultantFrameworkTransfer $transfer
    ): BinaryFileResponse|RedirectResponse {
        $this->authorize('view', $documentframework);

        try {
            $export = $transfer->export($documentframework, $format);
        } catch (\Throwable $exception) {
            Log::warning('Document framework export failed.', [
                'document_framework_id' => $documentframework->id,
                'format' => $format,
                'exception' => $exception,
            ]);

            return redirect()->back()->with('error', trans('admin/documentframeworks/message.export.error'));
        }

        return response()
            ->download($export['path'], $export['filename'], ['Content-Type' => $export['mime']])
            ->deleteFileAfterSend(true);
    }

    public function edit(DocumentFramework $documentframework): View
    {
        $this->authorize('update', $documentframework);

        return view('documentframeworks.edit', $this->formData($documentframework));
    }

    public function update(StoreDocumentFrameworkRequest $request, DocumentFramework $documentframework): RedirectResponse
    {
        $this->authorize('update', $documentframework);

        $documentframework->fill($request->validated());
        $documentframework->status = $request->input('status', 'active');
        $documentframework->is_active = $request->boolean('is_active');

        if ($documentframework->save()) {
            return redirect()->route('documentframeworks.index')->with('success', trans('admin/documentframeworks/message.update.success'));
        }

        return redirect()->back()->withInput()->withErrors($documentframework->getErrors());
    }

    public function destroy(DocumentFramework $documentframework): RedirectResponse
    {
        $this->authorize('delete', $documentframework);

        if (! $documentframework->isDeletable()) {
            return redirect()->route('documentframeworks.index')->with('error', trans('admin/documentframeworks/message.delete.associated_documents'));
        }

        $documentframework->delete();

        return redirect()->route('documentframeworks.index')->with('success', trans('admin/documentframeworks/message.delete.success'));
    }

    public function purgeUnusedBootstrap(Request $request, DocumentFramework $documentframework, ComplianceFrameworkPackPurger $purger): RedirectResponse
    {
        $this->authorize('delete', $documentframework);

        $request->validate([
            'confirm_purge_unused_bootstrap' => 'accepted',
        ]);

        $result = $purger->purgeFramework($documentframework, auth()->id());

        if (($result['status'] ?? null) === 'purged') {
            return redirect()
                ->route('documentframeworks.index')
                ->with('success', trans('admin/documentframeworks/message.purge_unused_bootstrap.success', [
                    'frameworks' => data_get($result, 'summary.frameworks_purged', 0),
                    'requirements' => data_get($result, 'summary.requirements_purged', 0),
                ]));
        }

        return redirect()
            ->route('documentframeworks.show', $documentframework)
            ->with('error', trans('admin/documentframeworks/message.purge_unused_bootstrap.blocked'));
    }

    public function restore(int $id): RedirectResponse
    {
        $this->authorize('delete', DocumentFramework::class);

        $documentframework = DocumentFramework::withTrashed()->findOrFail($id);
        $this->authorize('delete', $documentframework);

        if ($documentframework->restore()) {
            return redirect()->route('documentframeworks.index')->with('success', trans('admin/documentframeworks/message.restore.success'));
        }

        return redirect()->route('documentframeworks.index')->with('error', trans('general.could_not_restore', ['item_type' => trans('general.document_framework'), 'error' => $documentframework->getErrors()->first()]));
    }

    private function formData(DocumentFramework $item): array
    {
        return [
            'item' => $item,
            'statusOptions' => DocumentFramework::getStatusOptions(),
            'frameworkTypeOptions' => DocumentFramework::getFrameworkTypeOptions(),
            'complianceDomainOptions' => $this->complianceDomainOptions(),
            'visibilityOptions' => $this->visibilityOptions(),
        ];
    }

    private function complianceDomainOptions(): array
    {
        return collect(DocumentFramework::complianceDomainOptions())
            ->filter(fn ($label, $key) => ComplianceDomainAccess::canAccessDomain((string) $key, auth()->user()))
            ->all();
    }

    private function visibilityOptions(): array
    {
        $options = DocumentFramework::visibilityOptions();

        if (Tenant::canCurrentUserUseGlobalTenantContext()) {
            return $options;
        }

        unset($options[DocumentFramework::VISIBILITY_GLOBAL]);

        return $options;
    }

    private function requirementMatrixRows(Collection $requirements, int $reviewWarningDays): Collection
    {
        return $requirements->map(function (DocumentFrameworkRequirement $requirement) use ($reviewWarningDays) {
            $documents = $requirement->documents->values();
            $primaryDocuments = $documents
                ->filter(fn (Document $document) => $document->pivot?->coverage_role === Document::COVERAGE_PRIMARY)
                ->values();
            $supportingDocuments = $documents
                ->filter(fn (Document $document) => $document->pivot?->coverage_role === Document::COVERAGE_SUPPORTING)
                ->values();

            return [
                'requirement' => $requirement,
                'documents' => $documents,
                'primary_documents' => $primaryDocuments,
                'supporting_documents' => $supportingDocuments,
                'coverage_class' => $this->coverageLabelClass($requirement->coverage_status),
                'review_state' => $this->matrixReviewState($requirement, $primaryDocuments, $reviewWarningDays),
            ];
        });
    }

    private function visibleDocumentCoverageCounts(): array
    {
        return [
            'documents' => fn ($query) => $this->applyVisibleDocumentScope($query),
            'primaryDocuments as primary_documents_count' => fn ($query) => $this->applyVisibleDocumentScope($query),
            'primaryDocuments as healthy_primary_documents_count' => function ($query) {
                $query->currentForCoverage();
                $this->applyVisibleDocumentScope($query);
            },
        ];
    }

    private function applyVisibleDocumentScope($query): void
    {
        ComplianceDomainAccess::applyDocumentScope($query, request()->user());
        DocumentAreaAccess::applyDocumentScope($query, request()->user());
    }

    private function coverageLabelClass(string $coverageStatus): string
    {
        return match ($coverageStatus) {
            DocumentFrameworkRequirement::COVERAGE_COVERED => 'label label-success',
            DocumentFrameworkRequirement::COVERAGE_AT_RISK => 'label label-danger',
            DocumentFrameworkRequirement::COVERAGE_SUPPORTING_ONLY => 'label label-warning',
            default => 'label label-default',
        };
    }

    private function matrixReviewState(
        DocumentFrameworkRequirement $requirement,
        Collection $primaryDocuments,
        int $reviewWarningDays
    ): array {
        if ($requirement->coverage_status === DocumentFrameworkRequirement::COVERAGE_MISSING) {
            return [
                'label' => trans('admin/documentframeworkrequirements/general.matrix.review_missing'),
                'class' => 'label label-default',
                'document' => null,
            ];
        }

        if ($primaryDocuments->isEmpty()) {
            return [
                'label' => trans('admin/documentframeworkrequirements/general.matrix.review_missing_primary'),
                'class' => 'label label-warning',
                'document' => null,
            ];
        }

        $inactivePrimary = $primaryDocuments->first(fn (Document $document) => $document->status !== Document::STATUS_ACTIVE);
        if ($inactivePrimary) {
            return [
                'label' => trans('admin/documentframeworkrequirements/general.matrix.review_inactive_primary'),
                'class' => 'label label-danger',
                'document' => $inactivePrimary,
            ];
        }

        $today = Carbon::today();
        $warningDate = Carbon::today()->addDays($reviewWarningDays);

        $overduePrimary = $primaryDocuments
            ->filter(fn (Document $document) => $document->next_review_at && $document->next_review_at->lt($today))
            ->sortBy('next_review_at')
            ->first();

        if ($overduePrimary) {
            return [
                'label' => trans('admin/documentframeworkrequirements/general.matrix.review_overdue'),
                'class' => 'label label-danger',
                'document' => $overduePrimary,
            ];
        }

        $missingUploadPrimary = $primaryDocuments->first(fn (Document $document) => ! $document->hasCoverageUpload());
        if ($missingUploadPrimary) {
            return [
                'label' => trans('admin/documentframeworkrequirements/general.matrix.review_missing_upload'),
                'class' => 'label label-danger',
                'document' => $missingUploadPrimary,
            ];
        }

        $duePrimary = $primaryDocuments
            ->filter(fn (Document $document) => $document->next_review_at && $document->next_review_at->lte($warningDate))
            ->sortBy('next_review_at')
            ->first();

        if ($duePrimary) {
            return [
                'label' => trans('admin/documentframeworkrequirements/general.matrix.review_due'),
                'class' => 'label label-warning',
                'document' => $duePrimary,
            ];
        }

        return [
            'label' => trans('admin/documentframeworkrequirements/general.matrix.review_current'),
            'class' => 'label label-success',
            'document' => $primaryDocuments
                ->filter(fn (Document $document) => ! is_null($document->next_review_at))
                ->sortBy('next_review_at')
                ->first(),
        ];
    }
}
