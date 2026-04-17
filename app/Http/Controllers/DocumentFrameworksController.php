<?php

namespace App\Http\Controllers;

use App\Models\DocumentFramework;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class DocumentFrameworksController extends Controller
{
    public function index(): View
    {
        $this->authorize('index', DocumentFramework::class);

        return view('documentframeworks.index')->with('document_framework_count', DocumentFramework::withTrashed()->count());
    }

    public function create(): View
    {
        $this->authorize('create', DocumentFramework::class);

        return view('documentframeworks.edit')->with('item', new DocumentFramework);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', DocumentFramework::class);

        $documentFramework = new DocumentFramework;
        $documentFramework->fill($request->all());
        $documentFramework->created_by = auth()->id();
        $documentFramework->is_active = $request->boolean('is_active', true);

        if ($documentFramework->save()) {
            return redirect()->route('documentframeworks.index')->with('success', trans('admin/documentframeworks/message.create.success'));
        }

        return redirect()->back()->withInput()->withErrors($documentFramework->getErrors());
    }

    public function show(DocumentFramework $documentframework): View
    {
        $this->authorize('view', DocumentFramework::class);

        $documentframework->loadCount('documents');

        return view('documentframeworks.view', compact('documentframework'));
    }

    public function edit(DocumentFramework $documentframework): View
    {
        $this->authorize('update', DocumentFramework::class);

        return view('documentframeworks.edit')->with('item', $documentframework);
    }

    public function update(Request $request, DocumentFramework $documentframework): RedirectResponse
    {
        $this->authorize('update', DocumentFramework::class);

        $documentframework->fill($request->all());
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

    public function restore(int $id): RedirectResponse
    {
        $this->authorize('delete', DocumentFramework::class);

        $documentframework = DocumentFramework::withTrashed()->findOrFail($id);

        if ($documentframework->restore()) {
            return redirect()->route('documentframeworks.index')->with('success', trans('admin/documentframeworks/message.restore.success'));
        }

        return redirect()->route('documentframeworks.index')->with('error', trans('general.could_not_restore', ['item_type' => trans('general.document_framework'), 'error' => $documentframework->getErrors()->first()]));
    }
}
