<?php

namespace App\Http\Controllers\Documents;

use App\Enums\ActionType;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreDocumentAssignmentRequest;
use App\Models\Document;
use App\Models\DocumentAssignment;
use App\Support\Documents\DocumentAssignmentManager;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class DocumentAssignmentsController extends Controller
{
    public function index(): View
    {
        $this->authorize('index', Document::class);

        return view('documentassignments.index');
    }

    public function store(StoreDocumentAssignmentRequest $request, Document $document): RedirectResponse
    {
        $this->authorize('update', $document);

        $assignment = new DocumentAssignment;
        DocumentAssignmentManager::fillAssignment(
            $assignment,
            $request->only(array_keys(DocumentAssignmentManager::rules())),
            $document
        );
        $assignment->created_by = auth()->id();
        if (! $assignment->save()) {
            return redirect()->back()->withInput()->withErrors($assignment->getErrors());
        }

        DocumentAssignmentManager::logAssignmentAction($document, $assignment, ActionType::Create);

        return redirect()->route('documents.show', $document)
            ->with('success', trans('admin/documents/message.assignment_create.success'));
    }

    public function edit(Document $document, DocumentAssignment $documentAssignment): View
    {
        $this->authorize('update', $document);
        abort_unless((int) $documentAssignment->document_id === (int) $document->id, 404);

        return view('documents.assignment-edit', $this->formData($document, $documentAssignment));
    }

    public function update(StoreDocumentAssignmentRequest $request, Document $document, DocumentAssignment $documentAssignment): RedirectResponse
    {
        $this->authorize('update', $document);
        abort_unless((int) $documentAssignment->document_id === (int) $document->id, 404);

        DocumentAssignmentManager::fillAssignment(
            $documentAssignment,
            $request->only(array_keys(DocumentAssignmentManager::rules())),
            $document
        );
        if (! $documentAssignment->save()) {
            return redirect()->back()->withInput()->withErrors($documentAssignment->getErrors());
        }

        DocumentAssignmentManager::logAssignmentAction($document, $documentAssignment, ActionType::Update);

        return redirect()->route('documents.show', $document)
            ->with('success', trans('admin/documents/message.assignment_update.success'));
    }

    public function destroy(Document $document, DocumentAssignment $documentAssignment): RedirectResponse
    {
        $this->authorize('update', $document);
        abort_unless((int) $documentAssignment->document_id === (int) $document->id, 404);

        DocumentAssignmentManager::logAssignmentAction($document, $documentAssignment, ActionType::Delete);
        $documentAssignment->delete();

        return redirect()->route('documents.show', $document)
            ->with('success', trans('admin/documents/message.assignment_delete.success'));
    }
    private function formData(Document $document, DocumentAssignment $documentAssignment): array
    {
        return [
            'document' => $document,
            'documentAssignment' => $documentAssignment,
            'assignableTypeToken' => DocumentAssignment::tokenForAssignableClass($documentAssignment->assignable_type),
        ];
    }
}
