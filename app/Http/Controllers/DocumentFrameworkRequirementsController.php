<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\AppliesTenantCompanyFilter;
use App\Http\Requests\StoreDocumentFrameworkRequirementRequest;
use App\Models\DocumentFramework;
use App\Models\DocumentFrameworkRequirement;
use App\Models\DocumentType;
use App\Support\Compliance\ComplianceDomainAccess;
use App\Support\Tenants\TenantRecordGuard;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

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
            ->get(['id', 'name', 'company_id', 'visibility_type', 'is_system_template', 'compliance_domain'])
            ->filter(fn (DocumentFramework $framework) => ComplianceDomainAccess::canAccessFramework($framework, request()->user()))
            ->values();

        $editableFrameworks = $frameworks
            ->filter(fn (DocumentFramework $framework) => Gate::allows('update', $framework))
            ->values();

        $editableFrameworkCreateOptions = $editableFrameworks
            ->map(fn (DocumentFramework $framework) => [
                'id' => (int) $framework->id,
                'url' => route('documentframeworkrequirements.create', $framework),
            ])
            ->values();

        return view('documentframeworkrequirements.index', [
            'frameworks' => $frameworks,
            'editableFrameworks' => $editableFrameworks,
            'editableFrameworkCreateOptions' => $editableFrameworkCreateOptions,
            'selectedFrameworkId' => (string) request('document_framework_id', ''),
            'coverageOptions' => DocumentFrameworkRequirement::coverageOptions(),
        ]);
    }

    public function create(DocumentFramework $documentframework): View
    {
        $this->authorize('update', $documentframework);

        return view('documentframeworkrequirements.edit', $this->formData(new DocumentFrameworkRequirement([
            'document_framework_id' => $documentframework->id,
            'minimum_required_documents' => 1,
            'delegation_level' => 'owner_review',
            'risk_level' => $documentframework->isNis2Domain() ? 'not_applicable' : 'medium',
        ]), $documentframework));
    }

    public function store(StoreDocumentFrameworkRequirementRequest $request, DocumentFramework $documentframework): RedirectResponse
    {
        $this->authorize('update', $documentframework);

        $requirement = new DocumentFrameworkRequirement;
        $validated = $request->validated();
        unset($validated['parent_ids']);

        $requirement->fill($validated);
        $requirement->document_framework_id = $documentframework->id;
        $requirement->created_by = auth()->id();
        $requirement->is_active = $request->boolean('is_active', true);
        $requirement->is_mandatory = $request->boolean('is_mandatory', true);

        if ($requirement->save()) {
            if ($request->has('parent_ids') || $request->has('parent_id')) {
                $this->syncParentRequirements($requirement, $request->input('parent_ids', []));
            }

            return redirect()->route('documentframeworks.show', $documentframework)->with('success', trans('admin/documentframeworkrequirements/message.create.success'));
        }

        return redirect()->back()->withInput()->withErrors($requirement->getErrors());
    }

    public function show(DocumentFrameworkRequirement $documentframeworkrequirement): View
    {
        $this->authorize('view', $documentframeworkrequirement);

        $relations = [
            'framework.owner',
            'owner',
            'defaultDocumentType',
            'parent',
            'adminuser',
        ];

        if (DocumentFrameworkRequirement::parentPivotTableExists()) {
            $relations[] = 'parents';
        }

        $documentframeworkrequirement->load($relations)->loadCount([
            'documents',
            'primaryDocuments as primary_documents_count',
            'primaryDocuments as healthy_primary_documents_count' => fn ($query) => $query->currentForCoverage(),
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

        $validated = $request->validated();
        unset($validated['parent_ids']);

        $documentframeworkrequirement->fill($validated);
        $documentframeworkrequirement->is_active = $request->boolean('is_active');
        $documentframeworkrequirement->is_mandatory = $request->boolean('is_mandatory');

        if ($documentframeworkrequirement->save()) {
            if ($request->has('parent_ids') || $request->has('parent_id')) {
                $this->syncParentRequirements($documentframeworkrequirement, $request->input('parent_ids', []));
            }

            return redirect()->route('documentframeworkrequirements.show', $documentframeworkrequirement)->with('success', trans('admin/documentframeworkrequirements/message.update.success'));
        }

        return redirect()->back()->withInput()->withErrors($documentframeworkrequirement->getErrors());
    }

    public function bulkEdit(Request $request): View|RedirectResponse
    {
        $requirements = $this->editableRequirementsFromRequest($request);

        if ($requirements instanceof RedirectResponse) {
            return $requirements;
        }

        $framework = $this->singleFrameworkForBulkEdit($requirements);

        if (! $framework) {
            return redirect()->back()->with('error', trans('admin/documents/message.invalid_requirements_for_framework'));
        }

        $selectedIds = $requirements->pluck('id')->map(fn ($id) => (int) $id)->all();
        $selectedParentIds = $requirements
            ->flatMap(fn (DocumentFrameworkRequirement $requirement) => $requirement->parent_requirement_ids)
            ->map(fn ($parentId) => (int) $parentId)
            ->filter(fn (int $parentId) => $parentId && ! in_array($parentId, $selectedIds, true))
            ->unique()
            ->values()
            ->all();

        $parentOptions = DocumentFrameworkRequirement::query()
            ->forFramework($framework->id)
            ->whereNull('deleted_at')
            ->whereNotIn('id', $selectedIds)
            ->ordered()
            ->get();

        return view('documentframeworkrequirements.bulk-edit', [
            'requirements' => $requirements,
            'framework' => $framework,
            'parentOptions' => $parentOptions,
            'selectedParentIds' => $selectedParentIds,
            'obligationTypeOptions' => DocumentFrameworkRequirement::obligationTypeOptions(),
            'evidenceTypeOptions' => DocumentFrameworkRequirement::evidenceTypeOptions(),
            'delegationLevelOptions' => DocumentFrameworkRequirement::delegationLevelOptions(),
            'riskLevelOptions' => DocumentFrameworkRequirement::riskLevelOptions(),
            'isNis2Framework' => $framework->isNis2Domain(),
        ]);
    }

    public function bulkUpdate(Request $request): RedirectResponse
    {
        $requirements = $this->editableRequirementsFromRequest($request);

        if ($requirements instanceof RedirectResponse) {
            return $requirements;
        }

        $framework = $this->singleFrameworkForBulkEdit($requirements);

        if (! $framework) {
            return redirect()->back()->with('error', trans('admin/documents/message.invalid_requirements_for_framework'));
        }

        $validator = Validator::make($request->all(), [
            'ids' => 'required|array',
            'ids.*' => 'integer|distinct|exists:document_framework_requirements,id',
            'apply_domain' => 'nullable|boolean',
            'domain' => 'nullable|string|max:120',
            'apply_obligation_type' => 'nullable|boolean',
            'obligation_type' => 'nullable|string|in:'.implode(',', array_keys(DocumentFrameworkRequirement::obligationTypeOptions())),
            'apply_parent_ids' => 'nullable|boolean',
            'parent_ids' => 'nullable|array',
            'parent_ids.*' => 'nullable|integer|distinct|exists:document_framework_requirements,id',
            'apply_owner_id' => 'nullable|boolean',
            'owner_id' => 'nullable|integer|exists:users,id',
            'apply_default_document_type_id' => 'nullable|boolean',
            'default_document_type_id' => 'nullable|integer|exists:document_types,id',
            'apply_minimum_required_documents' => 'nullable|boolean',
            'minimum_required_documents' => 'nullable|integer|min:0|max:65535',
            'apply_evidence_type' => 'nullable|boolean',
            'evidence_type' => 'nullable|string|in:'.implode(',', array_keys(DocumentFrameworkRequirement::evidenceTypeOptions())),
            'apply_delegation_level' => 'nullable|boolean',
            'delegation_level' => 'nullable|string|in:'.implode(',', array_keys(DocumentFrameworkRequirement::delegationLevelOptions())),
            'apply_risk_level' => 'nullable|boolean',
            'risk_level' => 'nullable|string|in:'.implode(',', array_keys(DocumentFrameworkRequirement::riskLevelOptions())),
            'apply_review_frequency_months' => 'nullable|boolean',
            'review_frequency_months' => 'nullable|integer|min:1|max:120',
            'apply_sort_order' => 'nullable|boolean',
            'sort_order' => 'nullable|integer|min:0|max:65535',
            'apply_official_reference' => 'nullable|boolean',
            'official_reference' => 'nullable|string|max:255',
            'apply_source_url' => 'nullable|boolean',
            'source_url' => 'nullable|url|max:2048',
            'apply_description' => 'nullable|boolean',
            'description' => 'nullable|string|max:65535',
            'apply_evidence_guidance' => 'nullable|boolean',
            'evidence_guidance' => 'nullable|string|max:65535',
            'apply_applicability_notes' => 'nullable|boolean',
            'applicability_notes' => 'nullable|string|max:65535',
            'is_mandatory_state' => ['nullable', Rule::in(['0', '1'])],
            'is_active_state' => ['nullable', Rule::in(['0', '1'])],
        ]);

        $validator->after(function ($validator) use ($request, $requirements, $framework) {
            if (! $this->bulkUpdateHasSelectedFields($request)) {
                $validator->errors()->add('bulk_actions', trans('admin/hardware/message.update.nothing_updated'));
            }

            if ($request->boolean('apply_minimum_required_documents') && ! $request->filled('minimum_required_documents')) {
                $validator->errors()->add('minimum_required_documents', trans('validation.required', ['attribute' => trans('admin/documentframeworkrequirements/table.minimum_required_documents')]));
            }

            if ($request->boolean('apply_sort_order') && ! $request->filled('sort_order')) {
                $validator->errors()->add('sort_order', trans('validation.required', ['attribute' => trans('admin/documentframeworkrequirements/table.sort_order')]));
            }

            $frameworkCompanyId = $framework->company_id ? (int) $framework->company_id : null;
            $frameworkTenantId = TenantRecordGuard::companyTenantId($frameworkCompanyId);

            if ($request->boolean('apply_owner_id') && $request->filled('owner_id') && ! TenantRecordGuard::userCanBeReferencedByTenant($request->integer('owner_id'), $frameworkTenantId)) {
                $validator->errors()->add('owner_id', trans('validation.exists', ['attribute' => 'owner']));
            }

            if ($request->boolean('apply_default_document_type_id') && $request->filled('default_document_type_id')) {
                $documentType = DocumentType::find($request->integer('default_document_type_id'));

                if (! TenantRecordGuard::templateCanBeAppliedToCompany($documentType, $frameworkCompanyId)) {
                    $validator->errors()->add('default_document_type_id', trans('validation.exists', ['attribute' => 'document type']));
                }
            }

            if ($request->boolean('apply_parent_ids')) {
                $parentIds = $this->normalizedIds($request->input('parent_ids', []));
                $parents = DocumentFrameworkRequirement::withoutGlobalScopes()
                    ->whereIn('id', $parentIds)
                    ->get()
                    ->keyBy('id');

                foreach ($parentIds as $parentId) {
                    $parent = $parents->get($parentId);

                    if (! $parent || (int) $parent->document_framework_id !== (int) $framework->id) {
                        $validator->errors()->add('parent_ids', trans('validation.exists', ['attribute' => trans('admin/documentframeworkrequirements/table.parent')]));

                        continue;
                    }

                    foreach ($requirements as $requirement) {
                        if ((int) $parentId === (int) $requirement->id) {
                            $validator->errors()->add('parent_ids', trans('validation.different', [
                                'attribute' => trans('admin/documentframeworkrequirements/table.parent'),
                                'other' => trans('admin/documentframeworkrequirements/table.code'),
                            ]));

                            continue;
                        }

                        if (! $framework->isNis2Domain() && $this->wouldCreateParentCycle((int) $requirement->id, (int) $parentId)) {
                            $validator->errors()->add('parent_ids', trans('admin/documentframeworkrequirements/general.parent_cycle_error'));
                        }
                    }
                }
            }
        });

        if ($validator->fails()) {
            return redirect()->back()->withInput()->withErrors($validator);
        }

        $updates = $this->bulkRequirementUpdates($request, $framework);
        $syncParents = $request->boolean('apply_parent_ids');
        $parentIds = $this->normalizedIds($request->input('parent_ids', []));

        if ($syncParents) {
            $updates['parent_id'] = $parentIds[0] ?? null;
        }

        DB::transaction(function () use ($requirements, $updates, $syncParents, $parentIds) {
            foreach ($requirements as $requirement) {
                $requirement->fill($updates);

                if ($requirement->save() && $syncParents) {
                    $this->syncParentRequirements($requirement, $parentIds);
                }
            }
        });

        return redirect()
            ->route('documentframeworks.show', $framework)
            ->with('success', trans('admin/documentframeworkrequirements/message.update.success'));
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
        if ($requirement->exists) {
            $relations = ['parent'];

            if (DocumentFrameworkRequirement::parentPivotTableExists()) {
                $relations[] = 'parents';
            }

            $requirement->loadMissing($relations);
            $requirement->loadCount([
                'documents',
                'primaryDocuments as primary_documents_count',
                'primaryDocuments as healthy_primary_documents_count' => fn ($query) => $query->currentForCoverage(),
            ]);
        }

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
            'isNis2Framework' => $framework->isNis2Domain(),
        ];
    }

    private function syncParentRequirements(DocumentFrameworkRequirement $requirement, array $parentIds): void
    {
        $parentIds = collect($parentIds)
            ->filter(fn ($parentId) => filled($parentId))
            ->map(fn ($parentId) => (int) $parentId)
            ->filter(fn (int $parentId) => $parentId > 0)
            ->unique()
            ->values()
            ->all();

        if (! DocumentFrameworkRequirement::parentPivotTableExists()) {
            return;
        }

        $requirement->parents()->sync($parentIds);
    }

    private function editableRequirementsFromRequest(Request $request)
    {
        $ids = $this->normalizedIds($request->input('ids', []));

        if ($ids === []) {
            return redirect()->back()->with('error', trans('general.bulk.delete.nothing_selected', [
                'object_type' => trans('general.document_framework_requirements'),
            ]));
        }

        $relations = [
            'framework' => fn ($query) => $query->withoutGlobalScopes(),
            'parent',
        ];

        if (DocumentFrameworkRequirement::parentPivotTableExists()) {
            $relations[] = 'parents';
        }

        $requirements = DocumentFrameworkRequirement::query()
            ->with($relations)
            ->whereIn('id', $ids)
            ->get()
            ->sortBy(fn (DocumentFrameworkRequirement $requirement) => array_search((int) $requirement->id, $ids, true))
            ->values();

        if ($requirements->count() !== count($ids)) {
            return redirect()->back()->with('error', trans('admin/documents/message.invalid_requirements_for_framework'));
        }

        foreach ($requirements as $requirement) {
            $this->authorize('update', $requirement);
        }

        return $requirements;
    }

    private function singleFrameworkForBulkEdit($requirements): ?DocumentFramework
    {
        if ($requirements->pluck('document_framework_id')->unique()->count() !== 1) {
            return null;
        }

        return $requirements->first()?->framework;
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
        $applyFields = [
            'apply_domain',
            'apply_obligation_type',
            'apply_parent_ids',
            'apply_owner_id',
            'apply_default_document_type_id',
            'apply_minimum_required_documents',
            'apply_evidence_type',
            'apply_delegation_level',
            'apply_risk_level',
            'apply_review_frequency_months',
            'apply_sort_order',
            'apply_official_reference',
            'apply_source_url',
            'apply_description',
            'apply_evidence_guidance',
            'apply_applicability_notes',
        ];

        foreach ($applyFields as $field) {
            if ($request->boolean($field)) {
                return true;
            }
        }

        return $request->filled('is_mandatory_state') || $request->filled('is_active_state');
    }

    private function bulkRequirementUpdates(Request $request, DocumentFramework $framework): array
    {
        $updates = [];

        foreach ([
            'domain',
            'obligation_type',
            'owner_id',
            'default_document_type_id',
            'minimum_required_documents',
            'evidence_type',
            'delegation_level',
            'review_frequency_months',
            'sort_order',
            'official_reference',
            'source_url',
            'description',
            'evidence_guidance',
            'applicability_notes',
        ] as $field) {
            if ($request->boolean('apply_'.$field)) {
                $updates[$field] = $request->filled($field) ? $request->input($field) : null;
            }
        }

        if ($request->boolean('apply_risk_level')) {
            $updates['risk_level'] = $framework->isNis2Domain()
                ? 'not_applicable'
                : ($request->input('risk_level') ?: 'medium');
        }

        if ($request->filled('is_mandatory_state')) {
            $updates['is_mandatory'] = $request->input('is_mandatory_state') === '1';
        }

        if ($request->filled('is_active_state')) {
            $updates['is_active'] = $request->input('is_active_state') === '1';
        }

        return $updates;
    }

    private function wouldCreateParentCycle(int $requirementId, int $candidateParentId): bool
    {
        if ($requirementId === $candidateParentId) {
            return true;
        }

        $visited = [];
        $frontier = [$candidateParentId];

        while ($frontier !== []) {
            $current = array_shift($frontier);

            if (isset($visited[$current])) {
                continue;
            }

            $visited[$current] = true;

            if ($current === $requirementId) {
                return true;
            }

            $legacyParentIds = DB::table('document_framework_requirements')
                ->where('id', $current)
                ->whereNotNull('parent_id')
                ->pluck('parent_id')
                ->map(fn ($id) => (int) $id)
                ->all();

            $pivotParentIds = DocumentFrameworkRequirement::parentPivotTableExists()
                ? DB::table('document_framework_requirement_parents')
                    ->where('child_requirement_id', $current)
                    ->pluck('parent_requirement_id')
                    ->map(fn ($id) => (int) $id)
                    ->all()
                : [];

            foreach (array_merge($legacyParentIds, $pivotParentIds) as $parentId) {
                if (! isset($visited[$parentId])) {
                    $frontier[] = $parentId;
                }
            }
        }

        return false;
    }
}
