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
use App\Models\Document;
use Illuminate\Http\JsonResponse;

class DocumentFrameworkRequirementsController extends Controller
{
    use AppliesTenantCompanyFilter;

    public function index(FilterRequest $request): array
    {
        $this->authorize('view', DocumentFramework::class);

        $allowedColumns = [
            'id', 'code', 'title', 'domain', 'obligation_type', 'evidence_type', 'delegation_level',
            'risk_level', 'documents_count', 'review_frequency_months', 'sort_order', 'is_mandatory',
            'is_active', 'created_at', 'created_by',
        ];

        $requirements = DocumentFrameworkRequirement::query()
            ->visibleThroughFramework()
            ->with(['framework', 'owner', 'defaultDocumentType', 'adminuser'])
            ->withCount([
                'documents',
                'primaryDocuments as primary_documents_count',
                'primaryDocuments as healthy_primary_documents_count' => fn ($query) => $query
                    ->where('documents.status', Document::STATUS_ACTIVE)
                    ->where(function ($nested) {
                        $nested->whereNull('documents.next_review_at')
                            ->orWhereDate('documents.next_review_at', '>=', now()->toDateString());
                    }),
            ]);

        if ($request->input('deleted') === 'true') {
            $requirements->onlyTrashed();
        }

        if ($request->filled('document_framework_id')) {
            $requirements->where('document_framework_id', (int) $request->input('document_framework_id'));
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

        $documentframeworkrequirement->load(['framework', 'owner', 'defaultDocumentType', 'adminuser'])
            ->loadCount([
                'documents',
                'primaryDocuments as primary_documents_count',
                'primaryDocuments as healthy_primary_documents_count' => fn ($query) => $query
                    ->where('documents.status', Document::STATUS_ACTIVE)
                    ->where(function ($nested) {
                        $nested->whereNull('documents.next_review_at')
                            ->orWhereDate('documents.next_review_at', '>=', now()->toDateString());
                    }),
            ]);

        return (new DocumentFrameworkRequirementsTransformer)->transformRequirement($documentframeworkrequirement);
    }

    public function store(StoreDocumentFrameworkRequirementRequest $request): JsonResponse
    {
        $framework = DocumentFramework::findOrFail($request->input('document_framework_id'));
        $this->authorize('update', $framework);

        $requirement = new DocumentFrameworkRequirement;
        $requirement->fill($request->validated());
        $requirement->created_by = auth()->id();
        $requirement->is_active = $request->boolean('is_active', true);
        $requirement->is_mandatory = $request->boolean('is_mandatory', true);

        if ($requirement->save()) {
            return response()->json(Helper::formatStandardApiResponse('success', (new DocumentFrameworkRequirementsTransformer)->transformRequirement($requirement), trans('admin/documentframeworkrequirements/message.create.success')));
        }

        return response()->json(Helper::formatStandardApiResponse('error', null, $requirement->getErrors()), 422);
    }

    public function update(StoreDocumentFrameworkRequirementRequest $request, DocumentFrameworkRequirement $documentframeworkrequirement): JsonResponse
    {
        $this->authorize('update', $documentframeworkrequirement);

        $documentframeworkrequirement->fill($request->validated());
        $documentframeworkrequirement->is_active = $request->boolean('is_active');
        $documentframeworkrequirement->is_mandatory = $request->boolean('is_mandatory');

        if ($documentframeworkrequirement->save()) {
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
}
