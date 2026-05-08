<?php

namespace App\Http\Controllers\Api;

use App\Helpers\Helper;
use App\Http\Controllers\Concerns\AppliesTenantCompanyFilter;
use App\Http\Controllers\Controller;
use App\Http\Requests\FilterRequest;
use App\Http\Requests\StoreDocumentRequest;
use App\Http\Transformers\ActionlogsTransformer;
use App\Http\Transformers\DocumentsTransformer;
use App\Models\Document;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DocumentsController extends Controller
{
    use AppliesTenantCompanyFilter;

    public function index(FilterRequest $request): JsonResponse|array
    {
        $this->authorize('index', Document::class);

        $documents = Document::select('documents.*')
            ->with('company', 'owner', 'framework', 'type', 'adminuser', 'frameworkRequirements', 'documentAssignments.assignable');

        if ($request->filled('filter') || $request->filled('search')) {
            $documents->TextSearch($request->input('filter') ?: $request->input('search'));
        }

        if ($request->input('status_type') === 'Deleted') {
            $documents->onlyTrashed();
        } elseif ($request->filled('status')) {
            $documents->where('documents.status', '=', $request->input('status'));
        }

        if ($request->filled('review_status')) {
            if ($request->input('review_status') === 'due') {
                $documents->DueForReview($this->tenantFromRequest($request)?->documentReviewWarningDays() ?? 30);
            }

            if ($request->input('review_status') === 'overdue') {
                $documents->OverdueForReview();
            }
        }

        if ($request->filled('company_id')) {
            $documents->where('documents.company_id', '=', $request->input('company_id'));
        }

        $this->applyTenantCompanyFilter($documents, $request, 'documents.company_id');

        if ($request->filled('owner_id')) {
            $documents->where('documents.owner_id', '=', $request->input('owner_id'));
        }

        if ($request->filled('document_type_id')) {
            $documents->where('documents.document_type_id', '=', $request->input('document_type_id'));
        }

        if ($request->filled('document_framework_id')) {
            $documents->where('documents.document_framework_id', '=', $request->input('document_framework_id'));
        }

        if ($request->filled('document_framework_requirement_id')) {
            $documents->whereHas('frameworkRequirements', function ($query) use ($request) {
                $query->where('document_framework_requirements.id', '=', (int) $request->input('document_framework_requirement_id'));
            });
        }

        if ($request->filled('assigned_user_id')) {
            $documents->whereHas('documentAssignments', function ($query) use ($request) {
                $query->where('assignable_type', \App\Models\User::class)
                    ->where('assignable_id', '=', (int) $request->input('assigned_user_id'));
            });
        }

        if ($request->filled('assigned_asset_id')) {
            $documents->whereHas('documentAssignments', function ($query) use ($request) {
                $query->where('assignable_type', \App\Models\Asset::class)
                    ->where('assignable_id', '=', (int) $request->input('assigned_asset_id'));
            });
        }

        if ($request->filled('assigned_location_id')) {
            $documents->whereHas('documentAssignments', function ($query) use ($request) {
                $query->where('assignable_type', \App\Models\Location::class)
                    ->where('assignable_id', '=', (int) $request->input('assigned_location_id'));
            });
        }

        if ($request->filled('assigned_supplier_id')) {
            $documents->whereHas('documentAssignments', function ($query) use ($request) {
                $query->where('assignable_type', \App\Models\Supplier::class)
                    ->where('assignable_id', '=', (int) $request->input('assigned_supplier_id'));
            });
        }

        $allowedColumns = [
            'id',
            'name',
            'document_number',
            'version',
            'status',
            'classification',
            'issued_at',
            'effective_at',
            'next_review_at',
            'created_at',
            'updated_at',
            'company',
            'owner',
            'framework',
            'document_type',
        ];

        $limit = app('api_limit_value');
        $offset = \App\Helpers\Helper::clampPaginationOffset($request->input('offset'), $documents->count(), $limit);
        $order = $request->input('order') === 'asc' ? 'asc' : 'desc';
        $sort = in_array($request->input('sort'), $allowedColumns) ? $request->input('sort') : 'created_at';

        switch ($sort) {
            case 'company':
                $documents = $documents->OrderByCompany($order);
                break;
            case 'owner':
                $documents = $documents->OrderByOwner($order);
                break;
            case 'framework':
                $documents = $documents->OrderByFramework($order);
                break;
            case 'document_type':
                $documents = $documents->OrderByType($order);
                break;
            default:
                $documents = $documents->orderBy($sort, $order);
                break;
        }

        $total = $documents->count();
        $documents = $documents->skip($offset)->take($limit)->get();

        return (new DocumentsTransformer)->transformDocuments($documents, $total);
    }

    public function show(Document $document): JsonResponse|array
    {
        $this->authorize('view', $document);

        return response()->json((new DocumentsTransformer)->transformDocument($document));
    }

    public function store(StoreDocumentRequest $request): JsonResponse|array
    {
        $this->authorize('create', Document::class);

        $document = new Document;
        $document->fill($request->all());
        $document->created_by = auth()->id();

        if ($document->save()) {
            $this->syncRequirementMappings($document, $request);
            return response()->json(Helper::formatStandardApiResponse('success', (new DocumentsTransformer)->transformDocument($document), trans('admin/documents/message.create.success')));
        }

        return response()->json(Helper::formatStandardApiResponse('error', null, $document->getErrors()), 422);
    }

    public function update(StoreDocumentRequest $request, Document $document): JsonResponse|array
    {
        $this->authorize('update', $document);

        $document->fill($request->all());

        if ($document->save()) {
            $this->syncRequirementMappings($document, $request);
            return response()->json(Helper::formatStandardApiResponse('success', (new DocumentsTransformer)->transformDocument($document), trans('admin/documents/message.update.success')));
        }

        return response()->json(Helper::formatStandardApiResponse('error', null, $document->getErrors()), 422);
    }

    public function destroy(Document $document): JsonResponse|array
    {
        $this->authorize('delete', $document);

        $document->delete();

        return response()->json(Helper::formatStandardApiResponse('success', null, trans('admin/documents/message.delete.success')));
    }

    public function history(Request $request, Document $document): JsonResponse|array
    {
        $this->authorize('view', $document);

        $history = $document->getHistory($request);
        $total = $history->count();
        $history = $history->skip(app('api_offset_value'))->take(app('api_limit_value'))->get();

        return response()->json((new ActionlogsTransformer)->transformActionlogs($history, $total), 200, ['Content-Type' => 'application/json;charset=utf8'], JSON_UNESCAPED_UNICODE);
    }

    private function syncRequirementMappings(Document $document, StoreDocumentRequest $request): void
    {
        $syncData = [];
        $evidence = collect($request->input('requirement_evidence', []));

        foreach (collect($request->input('primary_requirement_ids', []))->filter() as $requirementId) {
            $evidenceData = collect($evidence->get((string) $requirementId, $evidence->get((int) $requirementId, [])));
            $syncData[(int) $requirementId] = [
                'coverage_role' => Document::COVERAGE_PRIMARY,
                'notes' => $evidenceData->get('notes') ?: null,
                'covered_at' => $evidenceData->get('covered_at') ?: now(),
                'created_by' => auth()->id(),
            ];
        }

        foreach (collect($request->input('supporting_requirement_ids', []))->filter() as $requirementId) {
            $evidenceData = collect($evidence->get((string) $requirementId, $evidence->get((int) $requirementId, [])));
            $syncData[(int) $requirementId] = [
                'coverage_role' => Document::COVERAGE_SUPPORTING,
                'notes' => $evidenceData->get('notes') ?: null,
                'covered_at' => $evidenceData->get('covered_at') ?: now(),
                'created_by' => auth()->id(),
            ];
        }

        $document->frameworkRequirements()->sync($syncData);
    }
}
