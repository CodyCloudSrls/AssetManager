<?php

namespace App\Http\Controllers\Api;

use App\Helpers\Helper;
use App\Http\Controllers\Controller;
use App\Http\Requests\FilterRequest;
use App\Http\Transformers\ComplianceDomainsTransformer;
use App\Http\Transformers\SelectlistTransformer;
use App\Models\ComplianceDomain;
use App\Models\DocumentFramework;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ComplianceDomainsController extends Controller
{
    public function index(FilterRequest $request): array
    {
        $this->authorize('view', ComplianceDomain::class);

        $allowedColumns = ['id', 'key', 'name', 'is_active', 'is_system', 'sort_order', 'created_at', 'updated_at'];

        $domains = ComplianceDomain::select([
            'id',
            'key',
            'name',
            'description',
            'is_active',
            'is_system',
            'sort_order',
            'created_by',
            'created_at',
            'updated_at',
            'deleted_at',
        ])->with('adminuser');

        if ($request->input('deleted') === 'true' || $request->input('status') === 'deleted') {
            $domains->onlyTrashed();
        }

        if ($request->filled('is_active')) {
            $domains->where('is_active', $request->boolean('is_active'));
        }

        if ($request->filled('filter') || $request->filled('search')) {
            $domains->TextSearch($request->input('filter') ?: $request->input('search'));
        }

        $limit = app('api_limit_value');
        $offset = Helper::clampPaginationOffset($request->input('offset'), $domains->count(), $limit);
        $order = $request->input('order') === 'asc' ? 'asc' : 'desc';
        $sort = in_array($request->input('sort'), $allowedColumns, true) ? $request->input('sort') : 'sort_order';

        $total = $domains->count();
        $domains = $domains->orderBy($sort, $order)->skip($offset)->take($limit)->get();

        return (new ComplianceDomainsTransformer)->transformComplianceDomains($domains, $total);
    }

    public function store(Request $request): JsonResponse
    {
        $this->authorize('create', ComplianceDomain::class);

        $validated = $this->validatedPayload($request);

        $domain = new ComplianceDomain;
        $domain->fill($validated);
        $domain->is_active = $request->boolean('is_active', true);
        $domain->is_system = false;
        $domain->created_by = auth()->id();

        if ($domain->save()) {
            return response()->json(Helper::formatStandardApiResponse('success', (new ComplianceDomainsTransformer)->transformComplianceDomain($domain), trans('admin/compliancedomains/message.create.success')));
        }

        return response()->json(Helper::formatStandardApiResponse('error', null, $domain->getErrors()), 422);
    }

    public function show(ComplianceDomain $compliancedomain): array
    {
        $this->authorize('view', $compliancedomain);

        return (new ComplianceDomainsTransformer)->transformComplianceDomain($compliancedomain);
    }

    public function update(Request $request, ComplianceDomain $compliancedomain): JsonResponse
    {
        $this->authorize('update', $compliancedomain);

        $validated = $this->validatedPayload($request, $compliancedomain);

        if ($this->keyIsImmutable($compliancedomain) && $validated['key'] !== $compliancedomain->key) {
            return response()->json(Helper::formatStandardApiResponse('error', null, trans('admin/compliancedomains/message.update.key_immutable')), 422);
        }

        if ($this->isDeactivationBlocked($request, $compliancedomain)) {
            return response()->json(Helper::formatStandardApiResponse('error', null, trans('admin/compliancedomains/message.update.deactivation_blocked')), 422);
        }

        $compliancedomain->fill($validated);
        $compliancedomain->is_active = $request->boolean('is_active');

        if ($compliancedomain->save()) {
            return response()->json(Helper::formatStandardApiResponse('success', (new ComplianceDomainsTransformer)->transformComplianceDomain($compliancedomain), trans('admin/compliancedomains/message.update.success')));
        }

        return response()->json(Helper::formatStandardApiResponse('error', null, $compliancedomain->getErrors()), 422);
    }

    public function destroy(ComplianceDomain $compliancedomain): JsonResponse
    {
        $this->authorize('delete', $compliancedomain);

        if (! $compliancedomain->isDeletable()) {
            return response()->json(Helper::formatStandardApiResponse('error', null, trans('admin/compliancedomains/message.delete.associated_frameworks')), 422);
        }

        $compliancedomain->delete();

        return response()->json(Helper::formatStandardApiResponse('success', null, trans('admin/compliancedomains/message.delete.success')));
    }

    public function selectlist(Request $request): array
    {
        $this->authorize('view.selectlists');

        $domains = ComplianceDomain::select(['id', 'name'])->active()->ordered();

        if ($request->filled('search')) {
            $domains->where('name', 'LIKE', '%'.$request->input('search').'%');
        }

        return (new SelectlistTransformer)->transformSelectlist($domains->paginate(50));
    }

    private function validatedPayload(Request $request, ?ComplianceDomain $complianceDomain = null): array
    {
        $request->merge([
            'key' => ComplianceDomain::normalizeKey($request->input('key')),
        ]);

        return $request->validate([
            'key' => [
                'required',
                'string',
                'max:80',
                'regex:/^[a-z0-9_]+$/',
                Rule::unique('compliance_domains', 'key')->ignore($complianceDomain?->id),
            ],
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:65535',
            'sort_order' => 'nullable|integer|min:0|max:65535',
            'is_active' => 'boolean',
        ]);
    }

    private function keyIsImmutable(ComplianceDomain $complianceDomain): bool
    {
        return $complianceDomain->is_system
            || DocumentFramework::where('compliance_domain', $complianceDomain->key)->exists();
    }

    private function isDeactivationBlocked(Request $request, ComplianceDomain $complianceDomain): bool
    {
        return $complianceDomain->is_active
            && ! $request->boolean('is_active')
            && DocumentFramework::where('compliance_domain', $complianceDomain->key)->exists();
    }
}
