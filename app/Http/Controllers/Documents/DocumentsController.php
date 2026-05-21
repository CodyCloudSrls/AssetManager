<?php

namespace App\Http\Controllers\Documents;

use App\Enums\ActionType;
use App\Http\Controllers\Concerns\AppliesTenantCompanyFilter;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreDocumentRequest;
use App\Models\Company;
use App\Models\Document;
use App\Models\DocumentAssignment;
use App\Models\DocumentAssignmentEvent;
use App\Models\DocumentFramework;
use App\Models\DocumentFrameworkRequirement;
use App\Models\DocumentType;
use App\Models\User;
use App\Support\Documents\DocumentAssignmentManager;
use App\Support\Tenants\TenantRecordGuard;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class DocumentsController extends Controller
{
    use AppliesTenantCompanyFilter;

    public function index(): View
    {
        $this->authorize('index', Document::class);

        $tenantCompanyIds = $this->tenantCompanyIdsFromRequest(request());

        $frameworks = DocumentFramework::query()
            ->operational()
            ->active()
            ->when(! is_null($tenantCompanyIds), fn ($query) => count($tenantCompanyIds) === 0
                ? $query->whereRaw('1 = 0')
                : $query->whereIn('company_id', $tenantCompanyIds))
            ->ordered()
            ->get(['id', 'name']);

        $requirements = DocumentFrameworkRequirement::query()
            ->visibleThroughFramework()
            ->active()
            ->when(! is_null($tenantCompanyIds), fn ($query) => count($tenantCompanyIds) === 0
                ? $query->whereRaw('1 = 0')
                : $query->whereHas('framework', fn ($frameworkQuery) => $frameworkQuery->whereIn('company_id', $tenantCompanyIds)))
            ->with('framework')
            ->ordered()
            ->get(['id', 'document_framework_id', 'code', 'title']);

        $selectedRequirement = request('document_framework_requirement_id')
            ? $requirements->firstWhere('id', (int) request('document_framework_requirement_id'))
            : null;

        return view('documents.index', [
            'frameworks' => $frameworks,
            'requirements' => $requirements,
            'selectedRequirement' => $selectedRequirement,
        ]);
    }

    public function create(Request $request): View
    {
        $this->authorize('create', Document::class);

        $document = new Document;
        $document->company_id = Company::getIdFromInput($request->input('company_id'))
            ?: Company::preferredCompanySelectionId();

        $assignedUser = $this->prefilledAssignedUser($request);
        if ($assignedUser) {
            $document->company_id = $assignedUser->company_id ?: $document->company_id;
        }

        return view('documents.edit', $this->formData($document));
    }

    public function store(StoreDocumentRequest $request): RedirectResponse
    {
        $this->authorize('create', Document::class);

        $document = new Document;
        $this->fillDocument($document, $request);
        $document->created_by = auth()->id();

        try {
            $assignmentCreated = DB::transaction(function () use ($request, $document) {
                $this->persistDocument($document);
                $this->syncRequirementMappings($document, $request);

                return $this->persistInlineAssignment($request, $document);
            });
        } catch (ValidationException $exception) {
            return redirect()->back()->withInput()->withErrors($exception->errors());
        }

        return redirect()->route('documents.show', $document)
            ->with('success', $assignmentCreated
                ? trans('admin/documents/message.create.success').' '.trans('admin/documents/message.assignment_create.success')
                : trans('admin/documents/message.create.success'));
    }

    public function show(Document $document): View
    {
        $this->authorize('view', $document);

        $document->load([
            'company',
            'owner',
            'type',
            'framework',
            'frameworkRequirements',
            'adminuser',
            'documentAssignments.assignable',
            'documentAssignments.issuer',
            'documentAssignments.reviewer',
            'documentAssignments.company',
            'documentAssignments.adminuser',
            'documentAssignments.events.actor',
            'documentAssignmentEvents.actor',
            'documentAssignmentEvents.documentAssignment.assignable',
        ]);

        return view('documents.view', compact('document'));
    }

    public function edit(Document $document): View
    {
        $this->authorize('update', $document);

        return view('documents.edit', $this->formData($document));
    }

    public function update(StoreDocumentRequest $request, Document $document): RedirectResponse
    {
        $this->authorize('update', $document);

        $this->fillDocument($document, $request);

        try {
            $assignmentCreated = DB::transaction(function () use ($request, $document) {
                $this->persistDocument($document);
                $this->syncRequirementMappings($document, $request);

                return $this->persistInlineAssignment($request, $document);
            });
        } catch (ValidationException $exception) {
            return redirect()->back()->withInput()->withErrors($exception->errors());
        }

        return redirect()->route('documents.show', $document)
            ->with('success', $assignmentCreated
                ? trans('admin/documents/message.update.success').' '.trans('admin/documents/message.assignment_create.success')
                : trans('admin/documents/message.update.success'));
    }

    public function destroy(Document $document): RedirectResponse
    {
        $this->authorize('delete', $document);

        $document->delete();

        return redirect()->route('documents.index')
            ->with('success', trans('admin/documents/message.delete.success'));
    }

    public function restore($documentId): RedirectResponse
    {
        $this->authorize('create', Document::class);

        $document = Document::withTrashed()->findOrFail($documentId);
        $document->restore();

        return redirect()->route('documents.show', $document)
            ->with('success', trans('admin/documents/message.restore.success'));
    }

    public function bulkEdit(Request $request): View|RedirectResponse
    {
        $action = $request->input('bulk_actions', session()->getOldInput('bulk_actions', 'edit'));

        if ($action === 'delete') {
            $documents = $this->documentsFromBulkRequest($request, 'delete');

            if ($documents instanceof RedirectResponse) {
                return $documents;
            }

            DB::transaction(function () use ($documents) {
                foreach ($documents as $document) {
                    $document->delete();
                }
            });

            return redirect()->route('documents.index')
                ->with('success', trans('admin/documents/message.delete.success'));
        }

        if ($action === 'restore') {
            $documents = $this->documentsFromBulkRequest($request, 'restore');

            if ($documents instanceof RedirectResponse) {
                return $documents;
            }

            DB::transaction(function () use ($documents) {
                foreach ($documents as $document) {
                    $document->restore();
                }
            });

            return redirect()->route('documents.index')
                ->with('success', trans('admin/documents/message.restore.success'));
        }

        if ($action !== 'edit') {
            return redirect()->back()->with('error', trans('admin/documents/message.bulk_action_invalid'));
        }

        $documents = $this->documentsFromBulkRequest($request, 'edit');

        if ($documents instanceof RedirectResponse) {
            return $documents;
        }

        return view('documents.bulk-edit', [
            'documents' => $documents,
            'documentStatuses' => Document::getStatusOptions(),
        ]);
    }

    public function bulkUpdate(Request $request): RedirectResponse
    {
        $documents = $this->documentsFromBulkRequest($request, 'edit');

        if ($documents instanceof RedirectResponse) {
            return $documents;
        }

        $validator = Validator::make($request->all(), [
            'ids' => 'required|array',
            'ids.*' => 'integer|distinct|exists:documents,id',
            'apply_status' => 'nullable|boolean',
            'status' => ['nullable', Rule::in(array_keys(Document::getStatusOptions()))],
            'apply_owner_id' => 'nullable|boolean',
            'owner_id' => 'nullable|integer|exists:users,id',
            'apply_document_type_id' => 'nullable|boolean',
            'document_type_id' => 'nullable|integer|exists:document_types,id',
            'apply_classification' => 'nullable|boolean',
            'classification' => 'nullable|string|max:100',
            'apply_retention_period' => 'nullable|boolean',
            'retention_period' => 'nullable|string|max:100',
            'apply_scope' => 'nullable|boolean',
            'scope' => 'nullable|string|max:150',
            'apply_issued_at' => 'nullable|boolean',
            'issued_at' => 'nullable|date_format:Y-m-d',
            'apply_effective_at' => 'nullable|boolean',
            'effective_at' => 'nullable|date_format:Y-m-d',
            'apply_next_review_at' => 'nullable|boolean',
            'next_review_at' => 'nullable|date_format:Y-m-d',
            'apply_control_url' => 'nullable|boolean',
            'control_url' => 'nullable|url|max:2048',
        ]);

        $validator->after(function ($validator) use ($request, $documents) {
            if (! $this->bulkUpdateHasSelectedFields($request)) {
                $validator->errors()->add('bulk_actions', trans('admin/hardware/message.update.nothing_updated'));
            }

            if ($request->boolean('apply_status') && ! $request->filled('status')) {
                $validator->errors()->add('status', trans('validation.required', ['attribute' => trans('general.status')]));
            }

            if ($request->boolean('apply_owner_id') && $request->filled('owner_id')) {
                foreach ($documents as $document) {
                    $tenantId = TenantRecordGuard::companyTenantId($document->company_id ? (int) $document->company_id : null);

                    if (! TenantRecordGuard::userCanBeReferencedByTenant($request->integer('owner_id'), $tenantId)) {
                        $validator->errors()->add('owner_id', trans('validation.exists', ['attribute' => 'owner']));
                        break;
                    }
                }
            }

            if ($request->boolean('apply_document_type_id') && $request->filled('document_type_id')) {
                $documentType = DocumentType::find($request->integer('document_type_id'));

                foreach ($documents as $document) {
                    if (! TenantRecordGuard::templateCanBeAppliedToCompany($documentType, $document->company_id ? (int) $document->company_id : null)) {
                        $validator->errors()->add('document_type_id', trans('validation.exists', ['attribute' => 'document type']));
                        break;
                    }
                }
            }
        });

        if ($validator->fails()) {
            return redirect()->back()->withInput()->withErrors($validator);
        }

        $updates = $this->bulkDocumentUpdates($request);

        DB::transaction(function () use ($documents, $updates) {
            foreach ($documents as $document) {
                $document->fill($updates);
                $this->persistDocument($document);
            }
        });

        return redirect()->route('documents.index')
            ->with('success', trans('admin/documents/message.update.success'));
    }

    private function documentsFromBulkRequest(Request $request, string $action)
    {
        $ids = $this->normalizedIds($request->input('ids', session()->getOldInput('ids', [])));

        if ($ids === []) {
            return redirect()->back()->with('error', trans('general.bulk.delete.nothing_selected', [
                'object_type' => trans('general.documents'),
            ]));
        }

        $documents = Document::withTrashed()
            ->whereIn('id', $ids)
            ->get()
            ->sortBy(fn (Document $document) => array_search((int) $document->id, $ids, true))
            ->values();

        if ($documents->count() !== count($ids)) {
            return redirect()->back()->with('error', trans('admin/documents/message.invalid_bulk_documents'));
        }

        foreach ($documents as $document) {
            if ($action === 'restore') {
                $this->authorize('create', Document::class);
                continue;
            }

            if ($action === 'edit' && $document->trashed()) {
                return redirect()->back()->with('error', trans('admin/documents/message.invalid_bulk_documents'));
            }

            $this->authorize($action === 'delete' ? 'delete' : 'update', $document);
        }

        return $documents;
    }

    private function normalizedIds($value): array
    {
        return collect(is_array($value) ? $value : [$value])
            ->filter(fn ($id) => filled($id))
            ->map(fn ($id) => (int) $id)
            ->filter(fn (int $id) => $id > 0)
            ->unique()
            ->values()
            ->all();
    }

    private function bulkUpdateHasSelectedFields(Request $request): bool
    {
        foreach ([
            'apply_status',
            'apply_owner_id',
            'apply_document_type_id',
            'apply_classification',
            'apply_retention_period',
            'apply_scope',
            'apply_issued_at',
            'apply_effective_at',
            'apply_next_review_at',
            'apply_control_url',
        ] as $field) {
            if ($request->boolean($field)) {
                return true;
            }
        }

        return false;
    }

    private function bulkDocumentUpdates(Request $request): array
    {
        $updates = [];

        foreach ([
            'status',
            'owner_id',
            'document_type_id',
            'classification',
            'retention_period',
            'scope',
            'issued_at',
            'effective_at',
            'next_review_at',
            'control_url',
        ] as $field) {
            if ($request->boolean('apply_'.$field)) {
                $updates[$field] = $request->filled($field) ? $request->input($field) : null;
            }
        }

        return $updates;
    }

    private function fillDocument(Document $document, StoreDocumentRequest $request): void
    {
        $document->fill($request->all());
    }

    private function formData(Document $document): array
    {
        $documentAssignment = new DocumentAssignment;
        $assignableTypeToken = DocumentAssignment::ASSIGNABLE_USER;

        if (! $document->exists && ($assignedUser = $this->prefilledAssignedUser(request()))) {
            $documentAssignment->assignable_type = User::class;
            $documentAssignment->assignable_id = $assignedUser->id;
            $assignableTypeToken = DocumentAssignment::ASSIGNABLE_USER;
        }

        if ($document->exists) {
            $document->load([
                'documentAssignments.assignable',
                'documentAssignments.issuer',
                'documentAssignments.reviewer',
                'documentAssignments.company',
                'documentAssignments.adminuser',
                'documentAssignments.events.actor',
            ]);
        }

        $allFrameworkRequirements = DocumentFrameworkRequirement::query()
            ->visibleThroughFramework()
            ->where('is_active', true)
            ->with('framework')
            ->ordered()
            ->get();

        $frameworkRequirements = $document->document_framework_id
            ? $allFrameworkRequirements->where('document_framework_id', $document->document_framework_id)->values()
            : collect();

        $frameworkRequirementOptionsByFramework = $allFrameworkRequirements
            ->groupBy('document_framework_id')
            ->map(fn ($requirements) => $requirements->map(fn ($requirement) => [
                'id' => $requirement->id,
                'code' => $requirement->code,
                'title' => $requirement->title,
                'domain' => $requirement->domain,
            ])->values())
            ->toArray();

        $selectedRequirementEvidence = $document->exists
            ? $document->frameworkRequirements->mapWithKeys(function ($requirement) {
                $coveredAt = $requirement->pivot->covered_at;

                return [
                    (string) $requirement->id => [
                        'covered_at' => $coveredAt ? substr((string) $coveredAt, 0, 10) : null,
                        'notes' => $requirement->pivot->notes,
                    ],
                ];
            })->all()
            : [];

        return [
            'document' => $document,
            'documentStatuses' => Document::getStatusOptions(),
            'frameworkRequirements' => $frameworkRequirements,
            'frameworkRequirementOptionsByFramework' => $frameworkRequirementOptionsByFramework,
            'selectedRequirementEvidence' => $selectedRequirementEvidence,
            'selectedPrimaryRequirementIds' => $document->exists
                ? $document->frameworkRequirements()->wherePivot('coverage_role', Document::COVERAGE_PRIMARY)->pluck('document_framework_requirements.id')->all()
                : [],
            'selectedSupportingRequirementIds' => $document->exists
                ? $document->frameworkRequirements()->wherePivot('coverage_role', Document::COVERAGE_SUPPORTING)->pluck('document_framework_requirements.id')->all()
                : [],
            'documentAssignment' => $documentAssignment,
            'assignableTypeToken' => $assignableTypeToken,
        ];
    }

    private function prefilledAssignedUser(Request $request): ?User
    {
        $userId = $request->integer('assigned_user_id')
            ?: $request->integer('assignment_assignable_user_id');

        if (! $userId) {
            return null;
        }

        return User::whereNull('deleted_at')->find($userId);
    }

    private function syncRequirementMappings(Document $document, StoreDocumentRequest $request): void
    {
        $syncData = [];
        $evidence = collect($request->input('requirement_evidence', []));

        foreach (collect($request->input('primary_requirement_ids', []))->filter() as $requirementId) {
            $evidenceData = collect($evidence->get((string) $requirementId, $evidence->get((int) $requirementId, [])));
            $syncData[(int) $requirementId] = [
                'coverage_role' => Document::COVERAGE_PRIMARY,
                'notes' => $evidenceData->get('notes') ?: null,
                'covered_at' => $evidenceData->get('covered_at') ?: now(),
                'created_by' => auth()->id(),
            ];
        }

        foreach (collect($request->input('supporting_requirement_ids', []))->filter() as $requirementId) {
            $evidenceData = collect($evidence->get((string) $requirementId, $evidence->get((int) $requirementId, [])));
            $syncData[(int) $requirementId] = [
                'coverage_role' => Document::COVERAGE_SUPPORTING,
                'notes' => $evidenceData->get('notes') ?: null,
                'covered_at' => $evidenceData->get('covered_at') ?: now(),
                'created_by' => auth()->id(),
            ];
        }

        $document->frameworkRequirements()->sync($syncData);
    }

    private function persistDocument(Document $document): void
    {
        if ($document->save()) {
            return;
        }

        throw ValidationException::withMessages($this->modelErrorMessages($document->getErrors()));
    }

    private function persistInlineAssignment(StoreDocumentRequest $request, Document $document): bool
    {
        if (! DocumentAssignmentManager::submissionRequested($request)) {
            return false;
        }

        $assignment = new DocumentAssignment;
        DocumentAssignmentManager::fillAssignment(
            $assignment,
            DocumentAssignmentManager::normalizedPayload($request),
            $document
        );
        $assignment->created_by = auth()->id();

        if (! $assignment->save()) {
            throw ValidationException::withMessages($this->modelErrorMessages($assignment->getErrors()));
        }

        DocumentAssignmentManager::logAssignmentEvent(
            $document,
            $assignment,
            DocumentAssignmentEvent::EVENT_CREATED,
            [],
            DocumentAssignmentManager::auditSnapshot($assignment)
        );
        DocumentAssignmentManager::logAssignmentAction($document, $assignment, ActionType::Create);

        return true;
    }

    private function modelErrorMessages($errors): array
    {
        if (is_array($errors)) {
            return $errors;
        }

        if (is_object($errors) && method_exists($errors, 'toArray')) {
            return $errors->toArray();
        }

        return ['general' => [trans('general.error')]];
    }
}
