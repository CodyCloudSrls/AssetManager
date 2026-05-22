<?php

namespace App\Http\Controllers\Api;

use App\Helpers\Helper;
use App\Http\Controllers\Controller;
use App\Http\Requests\FilterRequest;
use App\Http\Requests\StoreDocumentTypeRequest;
use App\Http\Transformers\DocumentTypesTransformer;
use App\Http\Transformers\SelectlistTransformer;
use App\Models\DocumentType;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DocumentTypesController extends Controller
{
    public function index(FilterRequest $request): array
    {
        $this->authorize('view', DocumentType::class);

        $allowedColumns = [
            'id',
            'name',
            'slug',
            'description',
            'sort_order',
            'is_active',
            'documents_count',
            'created_at',
            'updated_at',
        ];

        $documentTypes = DocumentType::select([
            'id',
            'name',
            'slug',
            'description',
            'sort_order',
            'is_active',
            'created_by',
            'company_id',
            'visibility_type',
            'created_at',
            'updated_at',
            'deleted_at',
        ])
            ->with('adminuser', 'company')
            ->withCount('documents as documents_count');

        if ($request->input('deleted') == 'true' || $request->input('status') == 'deleted') {
            $documentTypes->onlyTrashed();
        }

        if ($request->filled('filter') || $request->filled('search')) {
            $documentTypes->TextSearch($request->input('filter') ?: $request->input('search'));
        }

        if ($request->filled('is_active')) {
            $documentTypes->where('is_active', '=', $request->boolean('is_active'));
        }

        $limit = app('api_limit_value');
        $offset = \App\Helpers\Helper::clampPaginationOffset($request->input('offset'), $documentTypes->count(), $limit);
        $order = $request->input('order') === 'asc' ? 'asc' : 'desc';
        $sortOverride = $request->input('sort');
        $columnSort = in_array($sortOverride, $allowedColumns) ? $sortOverride : 'sort_order';

        if ($sortOverride === 'created_by') {
            $documentTypes = $documentTypes->OrderByCreatedBy($order);
        } else {
            $documentTypes = $documentTypes->orderBy($columnSort, $order);
        }

        $total = $documentTypes->count();
        $documentTypes = $documentTypes->skip($offset)->take($limit)->get();

        return (new DocumentTypesTransformer)->transformDocumentTypes($documentTypes, $total);
    }

    public function store(StoreDocumentTypeRequest $request): JsonResponse
    {
        $this->authorize('create', DocumentType::class);

        $documentType = new DocumentType;
        $documentType->fill($request->validated());
        $documentType->created_by = auth()->id();
        $documentType->is_active = $request->boolean('is_active', true);

        if ($documentType->save()) {
            return response()->json(Helper::formatStandardApiResponse('success', $documentType, trans('admin/documenttypes/message.create.success')));
        }

        return response()->json(Helper::formatStandardApiResponse('error', null, $documentType->getErrors()));
    }

    public function show(DocumentType $documenttype): array
    {
        $this->authorize('view', $documenttype);

        $documenttype->load('adminuser')->loadCount('documents');

        return (new DocumentTypesTransformer)->transformDocumentType($documenttype);
    }

    public function update(StoreDocumentTypeRequest $request, DocumentType $documenttype): JsonResponse
    {
        $this->authorize('update', $documenttype);

        $documenttype->fill($request->validated());
        $documenttype->is_active = $request->boolean('is_active');

        if ($documenttype->save()) {
            return response()->json(Helper::formatStandardApiResponse('success', $documenttype, trans('admin/documenttypes/message.update.success')));
        }

        return response()->json(Helper::formatStandardApiResponse('error', null, $documenttype->getErrors()));
    }

    public function destroy(DocumentType $documenttype): JsonResponse
    {
        $this->authorize('delete', $documenttype);

        if (! $documenttype->isDeletable()) {
            return response()->json(Helper::formatStandardApiResponse('error', null, trans('admin/documenttypes/message.delete.associated_documents')));
        }

        $documenttype->delete();

        return response()->json(Helper::formatStandardApiResponse('success', null, trans('admin/documenttypes/message.delete.success')));
    }

    public function restore(int $id): JsonResponse
    {
        $this->authorize('delete', DocumentType::class);

        $documenttype = DocumentType::withTrashed()->findOrFail($id);
        $this->authorize('delete', $documenttype);

        if ($documenttype->restore()) {
            return response()->json(Helper::formatStandardApiResponse('success', null, trans('admin/documenttypes/message.restore.success')));
        }

        return response()->json(Helper::formatStandardApiResponse('error', null, trans('general.could_not_restore', ['item_type' => trans('general.document_type'), 'error' => $documenttype->getErrors()->first()])));
    }

    public function selectlist(Request $request): array
    {
        $this->authorize('view.selectlists');

        $documentTypes = DocumentType::select([
            'id',
            'name',
        ])->active()->ordered();

        if ($request->filled('search')) {
            $documentTypes->where('name', 'LIKE', '%'.$request->input('search').'%');
        }

        return (new SelectlistTransformer)->transformSelectlist($documentTypes->paginate(50));
    }
}
