<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreDocumentTypeRequest;
use App\Models\DocumentType;
use App\Models\Tenant;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class DocumentTypesController extends Controller
{
    public function index(): View
    {
        $this->authorize('index', DocumentType::class);

        return view('documenttypes.index')->with('document_type_count', DocumentType::withTrashed()->count());
    }

    public function create(): View
    {
        $this->authorize('create', DocumentType::class);

        return view('documenttypes.edit', $this->formData(new DocumentType([
            'is_active' => true,
        ])));
    }

    public function store(StoreDocumentTypeRequest $request): RedirectResponse
    {
        $this->authorize('create', DocumentType::class);

        $documentType = new DocumentType;
        $documentType->fill($request->validated());
        $documentType->created_by = auth()->id();
        $documentType->is_active = $request->boolean('is_active', true);

        if ($documentType->save()) {
            return redirect()->route('documenttypes.index')->with('success', trans('admin/documenttypes/message.create.success'));
        }

        return redirect()->back()->withInput()->withErrors($documentType->getErrors());
    }

    public function show(DocumentType $documenttype): View
    {
        $this->authorize('view', $documenttype);

        $documenttype->loadCount('documents');

        return view('documenttypes.view', compact('documenttype'));
    }

    public function edit(DocumentType $documenttype): View
    {
        $this->authorize('update', $documenttype);

        return view('documenttypes.edit', $this->formData($documenttype));
    }

    public function update(StoreDocumentTypeRequest $request, DocumentType $documenttype): RedirectResponse
    {
        $this->authorize('update', $documenttype);

        $documenttype->fill($request->validated());
        $documenttype->is_active = $request->boolean('is_active');

        if ($documenttype->save()) {
            return redirect()->route('documenttypes.index')->with('success', trans('admin/documenttypes/message.update.success'));
        }

        return redirect()->back()->withInput()->withErrors($documenttype->getErrors());
    }

    public function destroy(DocumentType $documenttype): RedirectResponse
    {
        $this->authorize('delete', $documenttype);

        if (! $documenttype->isDeletable()) {
            return redirect()->route('documenttypes.index')->with('error', trans('admin/documenttypes/message.delete.associated_documents'));
        }

        $documenttype->delete();

        return redirect()->route('documenttypes.index')->with('success', trans('admin/documenttypes/message.delete.success'));
    }

    public function restore(int $id): RedirectResponse
    {
        $this->authorize('delete', DocumentType::class);

        $documenttype = DocumentType::withTrashed()->findOrFail($id);
        $this->authorize('delete', $documenttype);

        if ($documenttype->restore()) {
            return redirect()->route('documenttypes.index')->with('success', trans('admin/documenttypes/message.restore.success'));
        }

        return redirect()->route('documenttypes.index')->with('error', trans('general.could_not_restore', ['item_type' => trans('general.document_type'), 'error' => $documenttype->getErrors()->first()]));
    }

    private function formData(DocumentType $item): array
    {
        return [
            'item' => $item,
            'visibilityOptions' => $this->visibilityOptions(),
        ];
    }

    private function visibilityOptions(): array
    {
        $options = DocumentType::visibilityOptions();

        if (Tenant::canCurrentUserUseGlobalTenantContext()) {
            return $options;
        }

        unset($options[DocumentType::VISIBILITY_GLOBAL]);

        return $options;
    }
}
