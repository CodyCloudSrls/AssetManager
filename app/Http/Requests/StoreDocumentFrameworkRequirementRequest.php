<?php

namespace App\Http\Requests;

use App\Models\DocumentFramework;
use App\Models\DocumentFrameworkRequirement;
use App\Models\DocumentType;
use App\Support\Tenants\TenantRecordGuard;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class StoreDocumentFrameworkRequirementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'document_framework_id' => 'required|integer|exists:document_frameworks,id',
            'parent_id' => 'nullable|integer|exists:document_framework_requirements,id',
            'parent_ids' => 'nullable|array',
            'parent_ids.*' => 'nullable|integer|distinct|exists:document_framework_requirements,id',
            'code' => 'required|string|max:100',
            'title' => 'required|string|max:255',
            'domain' => 'nullable|string|max:120',
            'obligation_type' => 'nullable|string|in:'.implode(',', array_keys(DocumentFrameworkRequirement::obligationTypeOptions())),
            'description' => 'nullable|string|max:65535',
            'evidence_guidance' => 'nullable|string|max:65535',
            'applicability_notes' => 'nullable|string|max:65535',
            'owner_id' => 'nullable|integer|exists:users,id',
            'default_document_type_id' => 'nullable|integer|exists:document_types,id',
            'minimum_required_documents' => 'required|integer|min:0|max:65535',
            'evidence_type' => 'nullable|string|in:'.implode(',', array_keys(DocumentFrameworkRequirement::evidenceTypeOptions())),
            'delegation_level' => 'required|string|in:'.implode(',', array_keys(DocumentFrameworkRequirement::delegationLevelOptions())),
            'risk_level' => 'required|string|in:'.implode(',', array_keys(DocumentFrameworkRequirement::riskLevelOptions())),
            'official_reference' => 'nullable|string|max:255',
            'source_url' => 'nullable|url|max:2048',
            'review_frequency_months' => 'nullable|integer|min:1|max:120',
            'sort_order' => 'nullable|integer|min:0|max:65535',
            'is_mandatory' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function prepareForValidation(): void
    {
        if (! $this->filled('document_framework_id') && $this->route('documentframework')) {
            $framework = $this->route('documentframework');
            $this->merge([
                'document_framework_id' => $framework?->id ?? $framework,
            ]);
        }

        if (! $this->filled('document_framework_id') && $this->route('documentframeworkrequirement')) {
            $requirement = $this->route('documentframeworkrequirement');

            if ($requirement instanceof DocumentFrameworkRequirement) {
                $this->merge(['document_framework_id' => $requirement->document_framework_id]);
            }
        }

        $framework = $this->route('documentframework');
        $frameworkId = (int) $this->input('document_framework_id');

        if (! $framework instanceof DocumentFramework && $frameworkId > 0) {
            $framework = DocumentFramework::find($frameworkId);
        }

        if ($framework instanceof DocumentFramework && $framework->isNis2Domain()) {
            $this->merge(['risk_level' => 'not_applicable']);
        }

        if (! $this->has('minimum_required_documents')) {
            $this->merge(['minimum_required_documents' => 1]);
        }

        if ($this->has('parent_ids')) {
            $parentIds = $this->normalizedParentIds($this->input('parent_ids', []));
            $this->merge([
                'parent_ids' => $parentIds,
                'parent_id' => $parentIds[0] ?? null,
            ]);
        } elseif ($this->filled('parent_id')) {
            $this->merge(['parent_ids' => [(int) $this->input('parent_id')]]);
        } elseif ($this->has('parent_id')) {
            $this->merge(['parent_ids' => []]);
        }
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $requirementId = $this->route('documentframeworkrequirement')?->id ?? $this->route('documentframeworkrequirement');
            $frameworkId = (int) $this->input('document_framework_id');

            $query = DocumentFrameworkRequirement::withoutGlobalScopes()
                ->whereNull('deleted_at')
                ->where('document_framework_id', $frameworkId)
                ->where('code', $this->input('code'));

            if ($requirementId) {
                $query->where('id', '!=', $requirementId);
            }

            if ($query->exists()) {
                $validator->errors()->add('code', trans('validation.unique'));
            }

            $framework = $frameworkId > 0 ? DocumentFramework::find($frameworkId) : null;

            if ($frameworkId > 0 && ! $framework) {
                $validator->errors()->add('document_framework_id', trans('validation.exists', ['attribute' => 'document framework']));
            }

            $parentIds = $this->parentIdsForValidation();

            if ($parentIds !== []) {
                $parents = DocumentFrameworkRequirement::withoutGlobalScopes()
                    ->whereIn('id', $parentIds)
                    ->get()
                    ->keyBy('id');

                foreach ($parentIds as $parentId) {
                    $parent = $parents->get($parentId);

                    if (! $parent || (int) $parent->document_framework_id !== $frameworkId) {
                        $validator->errors()->add('parent_ids', trans('validation.exists', ['attribute' => trans('admin/documentframeworkrequirements/table.parent')]));
                        continue;
                    }

                    if ($requirementId && (int) $parentId === (int) $requirementId) {
                        $validator->errors()->add('parent_ids', trans('validation.different', [
                            'attribute' => trans('admin/documentframeworkrequirements/table.parent'),
                            'other' => trans('admin/documentframeworkrequirements/table.code'),
                        ]));
                        continue;
                    }

                    if ($requirementId && ! $framework?->isNis2Domain() && $this->wouldCreateParentCycle((int) $requirementId, (int) $parentId)) {
                        $validator->errors()->add('parent_ids', trans('admin/documentframeworkrequirements/general.parent_cycle_error'));
                    }
                }
            }

            if ($framework) {
                $frameworkCompanyId = $framework->company_id ? (int) $framework->company_id : null;
                $frameworkTenantId = TenantRecordGuard::companyTenantId($frameworkCompanyId);

                if ($this->filled('owner_id') && ! TenantRecordGuard::userCanBeReferencedByTenant($this->integer('owner_id'), $frameworkTenantId)) {
                    $validator->errors()->add('owner_id', trans('validation.exists', ['attribute' => 'owner']));
                }

                if ($this->filled('default_document_type_id')) {
                    $documentType = DocumentType::find($this->integer('default_document_type_id'));

                    if (! TenantRecordGuard::templateCanBeAppliedToCompany($documentType, $frameworkCompanyId)) {
                        $validator->errors()->add('default_document_type_id', trans('validation.exists', ['attribute' => 'document type']));
                    }
                }
            }
        });
    }

    private function normalizedParentIds($value): array
    {
        return collect(is_array($value) ? $value : [$value])
            ->filter(fn ($parentId) => filled($parentId))
            ->map(fn ($parentId) => (int) $parentId)
            ->filter(fn (int $parentId) => $parentId > 0)
            ->unique()
            ->values()
            ->all();
    }

    private function parentIdsForValidation(): array
    {
        if ($this->has('parent_ids')) {
            return $this->normalizedParentIds($this->input('parent_ids', []));
        }

        if ($this->filled('parent_id')) {
            return [(int) $this->input('parent_id')];
        }

        return [];
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

            $pivotParentIds = Schema::hasTable('document_framework_requirement_parents')
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
