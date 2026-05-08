<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\AppliesTenantCompanyFilter;
use App\Http\Requests\StoreDocumentFrameworkRequirementRequest;
use App\Models\DocumentFramework;
use App\Models\DocumentFrameworkRequirement;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class DocumentFrameworkRequirementsController extends Controller
{
    use AppliesTenantCompanyFilter;

    public function index(): View
    {
        $this->authorize('view', DocumentFramework::class);

        $tenantCompanyIds = $this->tenantCompanyIdsFromRequest(request());

        $frameworks = DocumentFramework::query()
            ->operational()
            ->active()
            ->when(! is_null($tenantCompanyIds), fn ($query) => count($tenantCompanyIds) === 0
                ? $query->whereRaw('1 = 0')
                : $query->whereIn('company_id', $tenantCompanyIds))
            ->ordered()
            ->get(['id', 'name', 'company_id', 'visibility_type', 'is_system_template']);

        $editableFrameworks = $frameworks
            ->filter(fn (DocumentFramework $framework) => Gate::allows('update', $framework))
            ->values();

        return view('documentframeworkrequirements.index', [
            'frameworks' => $frameworks,
            'editableFrameworks' => $editableFrameworks,
            'coverageOptions' => DocumentFrameworkRequirement::coverageOptions(),
        ]);
    }

    public function create(DocumentFramework $documentframework): View
    {
        $this->authorize('update', $documentframework);

        return view('documentframeworkrequirements.edit', $this->formData(new DocumentFrameworkRequirement([
            'document_framework_id' => $documentframework->id,
            'delegation_level' => 'owner_review',
            'risk_level' => 'medium',
        ]), $documentframework));
    }

    public function store(StoreDocumentFrameworkRequirementRequest $request, DocumentFramework $documentframework): RedirectResponse
    {
        $this->authorize('update', $documentframework);

        $requirement = new DocumentFrameworkRequirement;
        $requirement->fill($request->validated());
        $requirement->document_framework_id = $documentframework->id;
        $requirement->created_by = auth()->id();
        $requirement->is_active = $request->boolean('is_active', true);
        $requirement->is_mandatory = $request->boolean('is_mandatory', true);

        if ($requirement->save()) {
            return redirect()->route('documentframeworks.show', $documentframework)->with('success', trans('admin/documentframeworkrequirements/message.create.success'));
        }

        return redirect()->back()->withInput()->withErrors($requirement->getErrors());
    }

    public function show(DocumentFrameworkRequirement $documentframeworkrequirement): View
    {
        $this->authorize('view', $documentframeworkrequirement);

        $documentframeworkrequirement->load([
            'framework.owner',
            'owner',
            'defaultDocumentType',
            'parent',
            'adminuser',
        ])->loadCount([
            'documents',
            'primaryDocuments as primary_documents_count',
            'primaryDocuments as healthy_primary_documents_count' => fn ($query) => $query
                ->where('documents.status', \App\Models\Document::STATUS_ACTIVE)
                ->where(function ($nested) {
                    $nested->whereNull('documents.next_review_at')
                        ->orWhereDate('documents.next_review_at', '>=', now()->toDateString());
                }),
        ]);

        return view('documentframeworkrequirements.view', [
            'requirement' => $documentframeworkrequirement,
        ]);
    }

    public function edit(DocumentFrameworkRequirement $documentframeworkrequirement): View
    {
        $this->authorize('update', $documentframeworkrequirement);

        return view('documentframeworkrequirements.edit', $this->formData(
            $documentframeworkrequirement,
            $documentframeworkrequirement->framework
        ));
    }

    public function update(StoreDocumentFrameworkRequirementRequest $request, DocumentFrameworkRequirement $documentframeworkrequirement): RedirectResponse
    {
        $this->authorize('update', $documentframeworkrequirement);

        $documentframeworkrequirement->fill($request->validated());
        $documentframeworkrequirement->is_active = $request->boolean('is_active');
        $documentframeworkrequirement->is_mandatory = $request->boolean('is_mandatory');

        if ($documentframeworkrequirement->save()) {
            return redirect()->route('documentframeworkrequirements.show', $documentframeworkrequirement)->with('success', trans('admin/documentframeworkrequirements/message.update.success'));
        }

        return redirect()->back()->withInput()->withErrors($documentframeworkrequirement->getErrors());
    }

    public function destroy(DocumentFrameworkRequirement $documentframeworkrequirement): RedirectResponse
    {
        $this->authorize('delete', $documentframeworkrequirement);

        if (! $documentframeworkrequirement->isDeletable()) {
            return redirect()->route('documentframeworks.show', $documentframeworkrequirement->framework)->with('error', trans('admin/documentframeworkrequirements/message.delete.associated_documents'));
        }

        $documentframework = $documentframeworkrequirement->framework;
        $documentframeworkrequirement->delete();

        return redirect()->route('documentframeworks.show', $documentframework)->with('success', trans('admin/documentframeworkrequirements/message.delete.success'));
    }

    public function restore(int $id): RedirectResponse
    {
        $requirement = DocumentFrameworkRequirement::withTrashed()->findOrFail($id);
        $this->authorize('delete', $requirement);

        if ($requirement->restore()) {
            return redirect()->route('documentframeworkrequirements.show', $requirement)->with('success', trans('admin/documentframeworkrequirements/message.restore.success'));
        }

        return redirect()->route('documentframeworks.show', $requirement->framework)->with('error', trans('general.could_not_restore', ['item_type' => trans('general.document_framework_requirement'), 'error' => $requirement->getErrors()->first()]));
    }

    private function formData(DocumentFrameworkRequirement $requirement, DocumentFramework $framework): array
    {
        $parentOptions = DocumentFrameworkRequirement::query()
            ->forFramework($framework->id)
            ->whereNull('deleted_at')
            ->when($requirement->id, fn ($query) => $query->where('id', '!=', $requirement->id))
            ->ordered()
            ->get();

        return [
            'item' => $requirement,
            'framework' => $framework,
            'parentOptions' => $parentOptions,
            'obligationTypeOptions' => DocumentFrameworkRequirement::obligationTypeOptions(),
            'evidenceTypeOptions' => DocumentFrameworkRequirement::evidenceTypeOptions(),
            'delegationLevelOptions' => DocumentFrameworkRequirement::delegationLevelOptions(),
            'riskLevelOptions' => DocumentFrameworkRequirement::riskLevelOptions(),
        ];
    }
}
