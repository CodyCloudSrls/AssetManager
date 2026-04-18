<?php

namespace App\Http\Controllers\Documents;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreDocumentRequest;
use App\Models\Document;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

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

        if (! $document->save()) {
            return redirect()->back()->withInput()->withErrors($document->getErrors());
        }

        return redirect()->route('documents.show', $document)
            ->with('success', trans('admin/documents/message.create.success'));
    }

    public function show(Document $document): View
    {
        $this->authorize('view', $document);

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

        if (! $document->save()) {
            return redirect()->back()->withInput()->withErrors($document->getErrors());
        }

        return redirect()->route('documents.show', $document)
            ->with('success', trans('admin/documents/message.update.success'));
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
        return [
            'document' => $document,
            'documentStatuses' => Document::getStatusOptions(),
        ];
    }
}
