<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreDocumentFrameworkRequest;
use App\Models\DocumentFramework;
use App\Models\DocumentFrameworkRequirement;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

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

        return view('documentframeworks.edit', $this->formData(new DocumentFramework([
            'status' => 'active',
            'is_active' => true,
        ])));
    }

    public function store(StoreDocumentFrameworkRequest $request): RedirectResponse
    {
        $this->authorize('create', DocumentFramework::class);

        $documentFramework = new DocumentFramework;
        $documentFramework->fill($request->all());
        $documentFramework->created_by = auth()->id();
        $documentFramework->status = $request->input('status', 'active');
        $documentFramework->is_active = $request->boolean('is_active', true);

        if ($documentFramework->save()) {
            return redirect()->route('documentframeworks.index')->with('success', trans('admin/documentframeworks/message.create.success'));
        }

        return redirect()->back()->withInput()->withErrors($documentFramework->getErrors());
    }

    public function show(DocumentFramework $documentframework): View
    {
        $this->authorize('view', $documentframework);

        $documentframework->loadCount(['documents', 'requirements']);

        $requirements = DocumentFrameworkRequirement::query()
            ->forFramework($documentframework->id)
            ->with(['owner', 'defaultDocumentType'])
            ->withCount([
                'documents',
                'primaryDocuments as primary_documents_count',
                'primaryDocuments as healthy_primary_documents_count' => fn ($query) => $query
                    ->where('documents.status', \App\Models\Document::STATUS_ACTIVE)
                    ->where(function ($nested) {
                        $nested->whereNull('documents.next_review_at')
                            ->orWhereDate('documents.next_review_at', '>=', now()->toDateString());
                    }),
            ])
            ->ordered()
            ->get();

        $documentframework->setRelation('requirements', $requirements);

        return view('documentframeworks.view', compact('documentframework'));
    }

    public function edit(DocumentFramework $documentframework): View
    {
        $this->authorize('update', $documentframework);

        return view('documentframeworks.edit', $this->formData($documentframework));
    }

    public function update(StoreDocumentFrameworkRequest $request, DocumentFramework $documentframework): RedirectResponse
    {
        $this->authorize('update', $documentframework);

        $documentframework->fill($request->all());
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
        ];
    }
}
