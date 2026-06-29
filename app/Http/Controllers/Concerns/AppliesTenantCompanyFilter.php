<?php

namespace App\Http\Controllers\Concerns;

use App\Models\Tenant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

trait AppliesTenantCompanyFilter
{
    protected ?Tenant $tenantFilter = null;
    protected bool $tenantFilterResolved = false;

    protected function tenantFromRequest(Request $request): ?Tenant
    {
        if (! $request->filled('tenant_id')) {
            return null;
        }

        if ($this->tenantFilterResolved) {
            return $this->tenantFilter;
        }

        $tenant = Tenant::findOrFail((int) $request->input('tenant_id'));
        abort_unless(Tenant::canCurrentUserViewTenant($tenant), 403);

        $this->tenantFilter = $tenant;
        $this->tenantFilterResolved = true;

        return $tenant;
    }

    protected function tenantCompanyIdsFromRequest(Request $request): ?array
    {
        $tenant = $this->tenantFromRequest($request);

        if (is_null($tenant)) {
            return null;
        }

        return $tenant->activeCompanyIds();
    }

    protected function applyTenantCompanyFilter(Builder $query, Request $request, string $column = 'company_id'): void
    {
        $companyIds = $this->tenantCompanyIdsFromRequest($request);

        if (is_null($companyIds)) {
            return;
        }

        if (count($companyIds) === 0) {
            $query->whereRaw('1 = 0');

            return;
        }

        $query->whereIn($column, $companyIds);
    }

    /**
     * 403 unless a route-model-bound record belongs to a company the caller may touch.
     * Route-model binding resolves by primary key only, so edit/update/destroy must call
     * this to prevent cross-tenant IDOR (the class-level authorize() check is not
     * company-scoped). A null scope means unrestricted (superuser / no active tenant).
     */
    protected function assertCompanyAccessible(?array $scope, ?int $companyId): void
    {
        if (is_null($scope)) {
            return;
        }

        abort_unless(! is_null($companyId) && in_array((int) $companyId, $scope, true), 403);
    }

    /**
     * The company_id to persist on write, clamped to the caller's scope so a record can
     * never be created or moved into another tenant's company via a forged request field.
     * A request-supplied id is honoured only if in scope; otherwise the existing value (if
     * still in scope) or the caller's first allowed company. Null scope honours the request.
     */
    protected function resolveScopedCompanyId(?array $scope, ?int $requested, ?int $current = null): ?int
    {
        if (is_null($scope)) {
            return $requested ?? $current;
        }

        if (! is_null($requested) && in_array((int) $requested, $scope, true)) {
            return (int) $requested;
        }

        if (! is_null($current) && in_array((int) $current, $scope, true)) {
            return (int) $current;
        }

        return $scope[0] ?? null;
    }
}
