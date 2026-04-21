<?php

namespace App\Http\Controllers\Documents;

use App\Enums\ActionType;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreDocumentAssignmentRequest;
use App\Models\Actionlog;
use App\Models\Document;
use App\Models\DocumentAssignment;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class DocumentAssignmentsController extends Controller
{
    public function store(StoreDocumentAssignmentRequest $request, Document $document): RedirectResponse
    {
        $this->authorize('update', $document);

        $assignment = new DocumentAssignment;
        $this->fillAssignment($assignment, $request, $document);
        $assignment->created_by = auth()->id();
        if (! $assignment->save()) {
            return redirect()->back()->withInput()->withErrors($assignment->getErrors());
        }

        $this->logAssignmentAction($document, $assignment, ActionType::Create);

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

        $this->fillAssignment($documentAssignment, $request, $document);
        if (! $documentAssignment->save()) {
            return redirect()->back()->withInput()->withErrors($documentAssignment->getErrors());
        }

        $this->logAssignmentAction($document, $documentAssignment, ActionType::Update);

        return redirect()->route('documents.show', $document)
            ->with('success', trans('admin/documents/message.assignment_update.success'));
    }

    public function destroy(Document $document, DocumentAssignment $documentAssignment): RedirectResponse
    {
        $this->authorize('update', $document);
        abort_unless((int) $documentAssignment->document_id === (int) $document->id, 404);

        $this->logAssignmentAction($document, $documentAssignment, ActionType::Delete);
        $documentAssignment->delete();

        return redirect()->route('documents.show', $document)
            ->with('success', trans('admin/documents/message.assignment_delete.success'));
    }

    private function fillAssignment(DocumentAssignment $assignment, StoreDocumentAssignmentRequest $request, Document $document): void
    {
        $assignable = $request->resolvedAssignable();

        $assignment->document_id = $document->id;
        $assignment->company_id = (int) $assignable->company_id;
        $assignment->assignable_type = $request->input('assignable_type');
        $assignment->assignable_id = (int) $request->input('assignable_id');
        $assignment->relation_type = $request->input('relation_type');
        $assignment->status = $request->input('status');
        $assignment->issuer_id = $request->filled('issuer_id') ? $request->integer('issuer_id') : null;
        $assignment->reference_number = $request->filled('reference_number') ? $request->input('reference_number') : null;
        $assignment->issued_at = $request->input('issued_at');
        $assignment->effective_at = $request->input('effective_at');
        $assignment->expires_at = $request->input('expires_at');
        $assignment->renewal_due_at = $request->input('renewal_due_at');
        $assignment->completed_at = $request->input('completed_at');
        $assignment->revoked_at = $request->input('revoked_at');
        $assignment->notes = $request->filled('notes') ? $request->input('notes') : null;
    }

    private function formData(Document $document, DocumentAssignment $documentAssignment): array
    {
        return [
            'document' => $document,
            'documentAssignment' => $documentAssignment,
            'assignableTypeToken' => DocumentAssignment::tokenForAssignableClass($documentAssignment->assignable_type),
        ];
    }

    private function logAssignmentAction(Document $document, DocumentAssignment $assignment, ActionType $actionType): void
    {
        $logAction = new Actionlog;
        $logAction->item_type = Document::class;
        $logAction->item_id = $document->id;
        $logAction->target_type = $assignment->assignable_type;
        $logAction->target_id = $assignment->assignable_id;
        $logAction->created_at = now();
        $logAction->action_date = now();
        $logAction->created_by = auth()->id();
        $logAction->note = implode(' | ', array_filter([
            $assignment->assignable_type_label,
            $assignment->assignable_display_name,
            $assignment->relation_type_label,
            $assignment->status_label,
            $assignment->reference_number ? trans('admin/documents/form.assignment_reference_number').': '.$assignment->reference_number : null,
        ]));
        $logAction->logaction($actionType);
    }
}
