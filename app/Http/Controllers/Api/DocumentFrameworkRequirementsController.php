<?php

namespace App\Http\Controllers\Api;

use App\Helpers\Helper;
use App\Http\Controllers\Concerns\AppliesTenantCompanyFilter;
use App\Http\Controllers\Controller;
use App\Http\Requests\FilterRequest;
use App\Http\Requests\StoreDocumentFrameworkRequirementRequest;
use App\Http\Transformers\DocumentFrameworkRequirementsTransformer;
use App\Models\DocumentFramework;
use App\Models\DocumentFrameworkRequirement;
use App\Support\Compliance\ComplianceDomainAccess;
use App\Support\Documents\DocumentAreaAccess;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DocumentFrameworkRequirementsController extends Controller
{
    use AppliesTenantCompanyFilter;

    public function index(FilterRequest $request): array
    {
        $this->authorize('view', DocumentFramework::class);

        $allowedColumns = [
            'id', 'code', 'title', 'domain', 'obligation_type', 'evidence_type', 'delegation_level',
            'risk_level', 'documents_count', 'minimum_required_documents', 'review_frequency_months', 'sort_order', 'is_mandatory',
            'is_active', 'created_at', 'created_by',
        ];

        $relations = ['framework', 'owner', 'defaultDocumentType', 'adminuser', 'parent'];

        if (DocumentFrameworkRequirement::parentPivotTableExists()) {
            $relations[] = 'parents';
        }

        $requirements = DocumentFrameworkRequirement::query()
            ->visibleThroughFramework()
            ->with($relations)
            ->withCount($this->visibleDocumentCoverageCounts($request));
        ComplianceDomainAccess::applyRequirementScope($requirements, $request->user());

        if ($request->input('deleted') === 'true') {
            $requirements->onlyTrashed();
        }

        if ($request->filled('document_framework_id')) {
            $requirements->where('document_framework_id', (int) $request->input('document_framework_id'));
        }

        // Per-area scoping: requisiti del solo dominio compliance (NIS2/GDPR/DL81/...).
        if ($request->filled('compliance_domain')) {
            $requirements->whereHas('framework', fn ($query) => $query->where('compliance_domain', '=', $request->input('compliance_domain')));
        }

        $tenantCompanyIds = $this->tenantCompanyIdsFromRequest($request);

        if (! is_null($tenantCompanyIds)) {
            if (count($tenantCompanyIds) === 0) {
                $requirements->whereRaw('1 = 0');
            } else {
                $requirements->whereHas('framework', fn ($query) => $query->whereIn('company_id', $tenantCompanyIds));
            }
        }

        if ($request->filled('filter') || $request->filled('search')) {
            $requirements->TextSearch($request->input('filter') ?: $request->input('search'));
        }

        if ($request->filled('coverage_status')) {
            $requirements = $requirements->get()->filter(fn ($item) => $item->coverage_status === $request->input('coverage_status'));

            return (new DocumentFrameworkRequirementsTransformer)->transformRequirements($requirements->values(), $requirements->count());
        }

        $limit = app('api_limit_value');
        $offset = Helper::clampPaginationOffset($request->input('offset'), $requirements->count(), $limit);
        $order = $request->input('order') === 'asc' ? 'asc' : 'desc';
        $sort = in_array($request->input('sort'), $allowedColumns) ? $request->input('sort') : 'sort_order';

        if ($sort === 'created_by') {
            $requirements = $requirements->OrderByCreatedBy($order);
        } elseif ($sort === 'owner') {
            $requirements = $requirements->OrderByOwner($order);
        } elseif ($sort === 'framework') {
            $requirements = $requirements->OrderByFramework($order);
        } else {
            $requirements = $requirements->orderBy($sort, $order);
        }

        $total = $requirements->count();
        $requirements = $requirements->skip($offset)->take($limit)->get();

        return (new DocumentFrameworkRequirementsTransformer)->transformRequirements($requirements, $total);
    }

    public function show(DocumentFrameworkRequirement $documentframeworkrequirement): array
    {
        $this->authorize('view', $documentframeworkrequirement);

        $relations = ['framework', 'owner', 'defaultDocumentType', 'adminuser', 'parent'];

        if (DocumentFrameworkRequirement::parentPivotTableExists()) {
            $relations[] = 'parents';
        }

        $documentframeworkrequirement->load($relations)
            ->loadCount($this->visibleDocumentCoverageCounts(request()));

        return (new DocumentFrameworkRequirementsTransformer)->transformRequirement($documentframeworkrequirement);
    }

    public function store(StoreDocumentFrameworkRequirementRequest $request): JsonResponse
    {
        $framework = DocumentFramework::findOrFail($request->input('document_framework_id'));
        $this->authorize('update', $framework);

        $requirement = new DocumentFrameworkRequirement;
        $validated = $request->validated();
        unset($validated['parent_ids']);

        $requirement->fill($validated);
        $requirement->created_by = auth()->id();
        $requirement->is_active = $request->boolean('is_active', true);
        $requirement->is_mandatory = $request->boolean('is_mandatory', true);

        if ($requirement->save()) {
            if ($request->has('parent_ids') || $request->has('parent_id')) {
                $this->syncParentRequirements($requirement, $request->input('parent_ids', []));
            }

            return response()->json(Helper::formatStandardApiResponse('success', (new DocumentFrameworkRequirementsTransformer)->transformRequirement($requirement), trans('admin/documentframeworkrequirements/message.create.success')));
        }

        return response()->json(Helper::formatStandardApiResponse('error', null, $requirement->getErrors()), 422);
    }

    public function update(StoreDocumentFrameworkRequirementRequest $request, DocumentFrameworkRequirement $documentframeworkrequirement): JsonResponse
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

            return response()->json(Helper::formatStandardApiResponse('success', (new DocumentFrameworkRequirementsTransformer)->transformRequirement($documentframeworkrequirement), trans('admin/documentframeworkrequirements/message.update.success')));
        }

        return response()->json(Helper::formatStandardApiResponse('error', null, $documentframeworkrequirement->getErrors()), 422);
    }

    public function destroy(DocumentFrameworkRequirement $documentframeworkrequirement): JsonResponse
    {
        $this->authorize('delete', $documentframeworkrequirement);

        if (! $documentframeworkrequirement->isDeletable()) {
            return response()->json(Helper::formatStandardApiResponse('error', null, trans('admin/documentframeworkrequirements/message.delete.associated_documents')));
        }

        $documentframeworkrequirement->delete();

        return response()->json(Helper::formatStandardApiResponse('success', null, trans('admin/documentframeworkrequirements/message.delete.success')));
    }

    public function restore(int $id): JsonResponse
    {
        $requirement = DocumentFrameworkRequirement::withTrashed()->findOrFail($id);
        $this->authorize('delete', $requirement);

        if ($requirement->restore()) {
            return response()->json(Helper::formatStandardApiResponse('success', null, trans('admin/documentframeworkrequirements/message.restore.success')));
        }

        return response()->json(Helper::formatStandardApiResponse('error', null, trans('general.could_not_restore', ['item_type' => trans('general.document_framework_requirement'), 'error' => $requirement->getErrors()->first()])));
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

    private function visibleDocumentCoverageCounts(Request $request): array
    {
        return [
            'documents' => fn ($query) => $this->applyVisibleDocumentScope($query, $request),
            'primaryDocuments as primary_documents_count' => fn ($query) => $this->applyVisibleDocumentScope($query, $request),
            'primaryDocuments as healthy_primary_documents_count' => function ($query) use ($request) {
                $query->currentForCoverage();
                $this->applyVisibleDocumentScope($query, $request);
            },
        ];
    }

    private function applyVisibleDocumentScope($query, Request $request): void
    {
        ComplianceDomainAccess::applyDocumentScope($query, $request->user());
        DocumentAreaAccess::applyDocumentScope($query, $request->user());
    }
}
