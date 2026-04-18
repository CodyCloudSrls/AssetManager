<?php

namespace App\Http\Controllers\Api;

use App\Helpers\Helper;
use App\Http\Controllers\Controller;
use App\Http\Requests\FilterRequest;
use App\Http\Requests\StoreDocumentFrameworkRequest;
use App\Http\Transformers\DocumentFrameworksTransformer;
use App\Http\Transformers\SelectlistTransformer;
use App\Models\DocumentFramework;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DocumentFrameworksController extends Controller
{
    public function index(FilterRequest $request): array
    {
        $this->authorize('view', DocumentFramework::class);

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

        $documentFrameworks = DocumentFramework::select([
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
            $documentFrameworks->onlyTrashed();
        }

        if ($request->filled('filter') || $request->filled('search')) {
            $documentFrameworks->TextSearch($request->input('filter') ?: $request->input('search'));
        }

        if ($request->filled('is_active')) {
            $documentFrameworks->where('is_active', '=', $request->boolean('is_active'));
        }

        $limit = app('api_limit_value');
        $offset = \App\Helpers\Helper::clampPaginationOffset($request->input('offset'), $documentFrameworks->count(), $limit);
        $order = $request->input('order') === 'asc' ? 'asc' : 'desc';
        $sortOverride = $request->input('sort');
        $columnSort = in_array($sortOverride, $allowedColumns) ? $sortOverride : 'sort_order';

        if ($sortOverride === 'created_by') {
            $documentFrameworks = $documentFrameworks->OrderByCreatedBy($order);
        } else {
            $documentFrameworks = $documentFrameworks->orderBy($columnSort, $order);
        }

        $total = $documentFrameworks->count();
        $documentFrameworks = $documentFrameworks->skip($offset)->take($limit)->get();

        return (new DocumentFrameworksTransformer)->transformDocumentFrameworks($documentFrameworks, $total);
    }

    public function store(StoreDocumentFrameworkRequest $request): JsonResponse
    {
        $this->authorize('create', DocumentFramework::class);

        $documentFramework = new DocumentFramework;
        $documentFramework->fill($request->all());
        $documentFramework->created_by = auth()->id();
        $documentFramework->is_active = $request->boolean('is_active', true);

        if ($documentFramework->save()) {
            return response()->json(Helper::formatStandardApiResponse('success', $documentFramework, trans('admin/documentframeworks/message.create.success')));
        }

        return response()->json(Helper::formatStandardApiResponse('error', null, $documentFramework->getErrors()));
    }

    public function show(DocumentFramework $documentframework): array
    {
        $this->authorize('view', $documentframework);

        $documentframework->load('adminuser')->loadCount('documents');

        return (new DocumentFrameworksTransformer)->transformDocumentFramework($documentframework);
    }

    public function update(StoreDocumentFrameworkRequest $request, DocumentFramework $documentframework): JsonResponse
    {
        $this->authorize('update', $documentframework);

        $documentframework->fill($request->all());
        $documentframework->is_active = $request->boolean('is_active');

        if ($documentframework->save()) {
            return response()->json(Helper::formatStandardApiResponse('success', $documentframework, trans('admin/documentframeworks/message.update.success')));
        }

        return response()->json(Helper::formatStandardApiResponse('error', null, $documentframework->getErrors()));
    }

    public function destroy(DocumentFramework $documentframework): JsonResponse
    {
        $this->authorize('delete', $documentframework);

        if (! $documentframework->isDeletable()) {
            return response()->json(Helper::formatStandardApiResponse('error', null, trans('admin/documentframeworks/message.delete.associated_documents')));
        }

        $documentframework->delete();

        return response()->json(Helper::formatStandardApiResponse('success', null, trans('admin/documentframeworks/message.delete.success')));
    }

    public function restore(int $id): JsonResponse
    {
        $this->authorize('delete', DocumentFramework::class);

        $documentframework = DocumentFramework::withTrashed()->findOrFail($id);
        $this->authorize('delete', $documentframework);

        if ($documentframework->restore()) {
            return response()->json(Helper::formatStandardApiResponse('success', null, trans('admin/documentframeworks/message.restore.success')));
        }

        return response()->json(Helper::formatStandardApiResponse('error', null, trans('general.could_not_restore', ['item_type' => trans('general.document_framework'), 'error' => $documentframework->getErrors()->first()])));
    }

    public function selectlist(Request $request): array
    {
        $this->authorize('view.selectlists');

        $documentFrameworks = DocumentFramework::select([
            'id',
            'name',
        ])->active()->ordered();

        if ($request->filled('search')) {
            $documentFrameworks->where('name', 'LIKE', '%'.$request->input('search').'%');
        }

        return (new SelectlistTransformer)->transformSelectlist($documentFrameworks->paginate(50));
    }
}
