<?php

namespace App\Http\Controllers\Api;

use App\Helpers\Helper;
use App\Http\Controllers\Concerns\AppliesTenantCompanyFilter;
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
    use AppliesTenantCompanyFilter;

    public function index(FilterRequest $request): array
    {
        $this->authorize('view', DocumentFramework::class);

        $allowedColumns = [
            'id',
            'name',
            'slug',
            'framework_code',
            'status',
            'compliance_domain',
            'description',
            'sort_order',
            'is_active',
            'documents_count',
            'requirements_count',
            'created_at',
            'updated_at',
            'is_system_template',
            'source_pack_key',
            'source_pack_version',
            'locale',
        ];

        $documentFrameworks = DocumentFramework::select([
            'id',
            'name',
            'slug',
            'authority_name',
            'framework_code',
                'framework_type',
                'compliance_domain',
                'jurisdiction',
            'version',
            'effective_from',
            'effective_to',
            'owner_id',
            'review_cadence_months',
            'status',
                'external_reference_url',
                'description',
                'compliance_objective',
                'sort_order',
            'is_active',
            'is_system_template',
            'source_framework_id',
            'source_pack_key',
            'source_pack_version',
            'locale',
            'created_by',
            'company_id',
            'visibility_type',
            'created_at',
            'updated_at',
            'deleted_at',
        ])
            ->with('adminuser', 'company', 'owner')
            ->withCount(['documents as documents_count', 'requirements as requirements_count']);

        if (! $request->boolean('system_templates')) {
            $documentFrameworks->operational();
        } else {
            abort_unless(auth()->user()?->isSuperUser() && is_null(\App\Models\Tenant::activeTenantId()), 403);
            $documentFrameworks->systemTemplates();
        }

        if ($request->input('deleted') == 'true' || $request->input('status') == 'deleted') {
            $documentFrameworks->onlyTrashed();
        } elseif ($request->filled('status')) {
            $documentFrameworks->where('status', '=', $request->input('status'));
        }

        if ($request->filled('filter') || $request->filled('search')) {
            $documentFrameworks->TextSearch($request->input('filter') ?: $request->input('search'));
        }

        if ($request->filled('is_active')) {
            $documentFrameworks->where('is_active', '=', $request->boolean('is_active'));
        }

        $this->applyTenantCompanyFilter($documentFrameworks, $request, 'company_id');

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
        $documentFramework->fill($request->validated());
        $documentFramework->created_by = auth()->id();
        $documentFramework->status = $request->input('status', 'active');
        $documentFramework->is_active = $request->boolean('is_active', true);

        if ($documentFramework->save()) {
            return response()->json(Helper::formatStandardApiResponse('success', $documentFramework, trans('admin/documentframeworks/message.create.success')));
        }

        return response()->json(Helper::formatStandardApiResponse('error', null, $documentFramework->getErrors()));
    }

    public function show(DocumentFramework $documentframework): array
    {
        $this->authorize('view', $documentframework);

        $documentframework->load('adminuser', 'owner')->loadCount(['documents', 'requirements']);

        return (new DocumentFrameworksTransformer)->transformDocumentFramework($documentframework);
    }

    public function update(StoreDocumentFrameworkRequest $request, DocumentFramework $documentframework): JsonResponse
    {
        $this->authorize('update', $documentframework);

        $documentframework->fill($request->validated());
        $documentframework->status = $request->input('status', 'active');
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
        ])->operational()->active()->ordered();

        if ($request->filled('search')) {
            $documentFrameworks->where('name', 'LIKE', '%'.$request->input('search').'%');
        }

        return (new SelectlistTransformer)->transformSelectlist($documentFrameworks->paginate(50));
    }
}
