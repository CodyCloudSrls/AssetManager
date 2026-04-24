<?php

namespace App\Http\Controllers\Documents;

use App\Enums\ActionType;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreDocumentRequest;
use App\Models\Document;
use App\Models\DocumentAssignment;
use App\Models\DocumentFrameworkRequirement;
use App\Support\Documents\DocumentAssignmentManager;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class DocumentsController extends Controller
{
    public function index(): View
    {
        $this->authorize('index', Document::class);

        return view('documents.index');
    }

    public function create(): View
    {
        $this->authorize('create', Document::class);

        return view('documents.edit', $this->formData(new Document));
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
            'documentAssignments.company',
            'documentAssignments.adminuser',
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

    private function fillDocument(Document $document, StoreDocumentRequest $request): void
    {
        $document->fill($request->all());
    }

    private function formData(Document $document): array
    {
        if ($document->exists) {
            $document->load([
                'documentAssignments.assignable',
                'documentAssignments.issuer',
                'documentAssignments.company',
                'documentAssignments.adminuser',
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

        return [
            'document' => $document,
            'documentStatuses' => Document::getStatusOptions(),
            'frameworkRequirements' => $frameworkRequirements,
            'frameworkRequirementOptionsByFramework' => $frameworkRequirementOptionsByFramework,
            'selectedPrimaryRequirementIds' => $document->exists
                ? $document->frameworkRequirements()->wherePivot('coverage_role', Document::COVERAGE_PRIMARY)->pluck('document_framework_requirements.id')->all()
                : [],
            'selectedSupportingRequirementIds' => $document->exists
                ? $document->frameworkRequirements()->wherePivot('coverage_role', Document::COVERAGE_SUPPORTING)->pluck('document_framework_requirements.id')->all()
                : [],
        ];
    }

    private function syncRequirementMappings(Document $document, StoreDocumentRequest $request): void
    {
        $syncData = [];

        foreach (collect($request->input('primary_requirement_ids', []))->filter() as $requirementId) {
            $syncData[(int) $requirementId] = [
                'coverage_role' => Document::COVERAGE_PRIMARY,
                'notes' => null,
                'covered_at' => now(),
                'created_by' => auth()->id(),
            ];
        }

        foreach (collect($request->input('supporting_requirement_ids', []))->filter() as $requirementId) {
            $syncData[(int) $requirementId] = [
                'coverage_role' => Document::COVERAGE_SUPPORTING,
                'notes' => null,
                'covered_at' => now(),
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
