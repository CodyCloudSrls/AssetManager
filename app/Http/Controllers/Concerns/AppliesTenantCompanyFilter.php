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
}
