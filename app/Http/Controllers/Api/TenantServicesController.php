<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Transformers\SelectlistTransformer;
use App\Models\Tenant;
use App\Models\TenantService;
use App\Support\Tenants\TenantRecordGuard;
use Illuminate\Http\Request;

class TenantServicesController extends Controller
{
    /**
     * Rich select2 list of active tenant services for a given company.
     *
     * Tenant services are tenant-scoped, so the company_id is resolved to its
     * tenant and services are only returned when the current user can actually
     * view that tenant, preserving tenant isolation.
     */
    public function selectlist(Request $request): array
    {
        $this->authorize('view.selectlists');

        $companyId = $request->filled('company_id')
            ? (int) $request->input('company_id')
            : ($request->filled('companyId') ? (int) $request->input('companyId') : null);

        $tenantId = $this->viewableTenantIdForCompany($companyId);

        $services = TenantService::query()
            ->select(['id', 'name', 'macro_area', 'tenant_id'])
            ->when(
                $tenantId,
                fn ($query) => $query->where('tenant_id', $tenantId),
                fn ($query) => $query->whereRaw('1 = 0'),
            )
            ->active();

        if ($request->filled('search')) {
            $services->where('name', 'LIKE', '%'.$request->input('search').'%');
        }

        $services = $services->orderBy('macro_area')->orderBy('name')->paginate(50);

        foreach ($services as $service) {
            $service->use_text = trim($service->macro_area_label.' - '.$service->name);
        }

        return (new SelectlistTransformer)->transformSelectlist($services);
    }

    private function viewableTenantIdForCompany(?int $companyId): ?int
    {
        if (! $companyId) {
            return null;
        }

        $tenantId = TenantRecordGuard::companyTenantId($companyId);

        if (! $tenantId) {
            return null;
        }

        $tenant = Tenant::find($tenantId);

        if (! $tenant || ! Tenant::canCurrentUserViewTenant($tenant)) {
            return null;
        }

        return $tenantId;
    }
}
