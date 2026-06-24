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
            ->select(['id', 'name', 'macro_area', 'tenant_id', 'company_id'])
            ->when(
                $tenantId,
                fn ($query) => $query->where('tenant_id', $tenantId),
                fn ($query) => $query->whereRaw('1 = 0'),
            )
            ->active();

        // When a specific company is chosen (the asset form once a company is picked),
        // only offer that company's services plus tenant-wide ones — exactly what can
        // actually be linked (see TenantService::validIdsForCompany).
        if ($companyId && $tenantId) {
            $services->where(
                fn ($query) => $query->whereNull('company_id')->orWhere('company_id', $companyId)
            );
        }

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
        // No company chosen yet (e.g. the asset CREATE form before a company is picked):
        // fall back to the current user's own tenant so their services still show,
        // instead of returning an empty list.
        if (! $companyId) {
            return $this->currentUserFallbackTenantId();
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

    /**
     * Best-effort tenant for the current user when no company is in scope: their own
     * company's tenant, otherwise the single tenant they can access (null if ambiguous).
     */
    private function currentUserFallbackTenantId(): ?int
    {
        $user = auth()->user();

        if (! $user) {
            return null;
        }

        $userCompanyId = (int) ($user->company_id ?? 0);

        if ($userCompanyId) {
            $tenantId = TenantRecordGuard::companyTenantId($userCompanyId);

            if ($tenantId) {
                return (int) $tenantId;
            }
        }

        $accessible = Tenant::accessibleTenantIdsForCurrentUser();

        return count($accessible) === 1 ? (int) $accessible[0] : null;
    }
}
