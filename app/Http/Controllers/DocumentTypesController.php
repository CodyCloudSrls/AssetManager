<?php

namespace App\Http\Controllers;

use App\Models\DocumentType;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

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

        return view('documenttypes.edit')->with('item', new DocumentType);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', DocumentType::class);

        $documentType = new DocumentType;
        $documentType->fill($request->all());
        $documentType->created_by = auth()->id();
        $documentType->is_active = $request->boolean('is_active', true);

        if ($documentType->save()) {
            return redirect()->route('documenttypes.index')->with('success', trans('admin/documenttypes/message.create.success'));
        }

        return redirect()->back()->withInput()->withErrors($documentType->getErrors());
    }

    public function show(DocumentType $documenttype): View
    {
        $this->authorize('view', DocumentType::class);

        $documenttype->loadCount('documents');

        return view('documenttypes.view', compact('documenttype'));
    }

    public function edit(DocumentType $documenttype): View
    {
        $this->authorize('update', DocumentType::class);

        return view('documenttypes.edit')->with('item', $documenttype);
    }

    public function update(Request $request, DocumentType $documenttype): RedirectResponse
    {
        $this->authorize('update', DocumentType::class);

        $documenttype->fill($request->all());
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

        if ($documenttype->restore()) {
            return redirect()->route('documenttypes.index')->with('success', trans('admin/documenttypes/message.restore.success'));
        }

        return redirect()->route('documenttypes.index')->with('error', trans('general.could_not_restore', ['item_type' => trans('general.document_type'), 'error' => $documenttype->getErrors()->first()]));
    }
}
