<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\AppliesTenantCompanyFilter;
use App\Models\Company;
use App\Models\Customer;
use App\Models\CustomerContract;
use App\Models\Supplier;
use App\Models\Tenant;
use App\Support\Fic\FicClient;
use App\Support\Reports\ContractForecastReport;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

/**
 * ERP / Management-control cockpit. Built ON TOP of existing asset functionality —
 * it REUSES the contract forecast engine, contract subscriptions (MRR), the tenant/
 * company scoping and the currency formatter rather than reinventing them. Gated by
 * the per-tenant "erp" feature flag.
 */
class ErpController extends Controller
{
    use AppliesTenantCompanyFilter;

    public function index(Request $request, ContractForecastReport $forecast): View
    {
        $this->authorize('view', CustomerContract::class);

        $companyIds = $this->cockpitCompanyIds($request);

        // Reuse the existing forecast engine for revenue / cost / net / margin.
        $report = $forecast->build($companyIds);

        $mrr = $this->monthlyRecurringRevenue($companyIds);

        $kpis = [
            'mrr' => $mrr,
            'arr' => $mrr * 12,
            'active_contracts' => $this->scopeCompanies(
                CustomerContract::query()->where('status', CustomerContract::STATUS_ACTIVE), $companyIds
            )->count(),
            'expiring_contracts' => $this->scopeCompanies(
                CustomerContract::query()
                    ->where('status', CustomerContract::STATUS_ACTIVE)
                    ->whereNotNull('renewal_due_at')
                    ->whereBetween('renewal_due_at', [Carbon::now(), Carbon::now()->addDays(30)]),
                $companyIds
            )->count(),
            'customers' => $this->scopeCompanies(Customer::query(), $companyIds)->count(),
            'suppliers' => $this->scopeCompanies(Supplier::query(), $companyIds)->count(),
        ];

        return view('erp.index', [
            'report' => $report,
            'kpis' => $kpis,
            'ficConfigured' => app(FicClient::class)->isConfigured(),
        ]);
    }

    /**
     * Company scope for the cockpit, mirroring the reports' resolution order:
     * explicit tenant in the request -> active tenant in session -> the user's own
     * company -> null (all companies the global scope already allows).
     */
    private function cockpitCompanyIds(Request $request): ?array
    {
        $companyIds = $this->tenantCompanyIdsFromRequest($request);
        if (! is_null($companyIds)) {
            return $companyIds;
        }

        if ($activeTenant = Tenant::activeTenant()) {
            return $activeTenant->activeCompanyIds();
        }

        $userCompanyId = auth()->user()?->company_id;
        if (! is_null($userCompanyId)) {
            return [(int) $userCompanyId];
        }

        return null;
    }

    /** Apply the same withoutGlobalScopes + whereIn(company_id) pattern used across the app. */
    private function scopeCompanies(Builder $query, ?array $companyIds): Builder
    {
        if (is_null($companyIds)) {
            return $query;
        }

        if ($companyIds === []) {
            return $query->whereRaw('1 = 0');
        }

        return $query->withoutGlobalScopes()->whereIn($query->getModel()->getTable().'.company_id', $companyIds);
    }

    /** MRR from active subscriptions of active contracts, reusing the monthly_revenue accessor. */
    private function monthlyRecurringRevenue(?array $companyIds): float
    {
        $contracts = $this->scopeCompanies(
            CustomerContract::query()->where('status', CustomerContract::STATUS_ACTIVE),
            $companyIds
        )->with('subscriptions')->get();

        $mrr = 0.0;
        foreach ($contracts as $contract) {
            foreach ($contract->subscriptions as $subscription) {
                if ($subscription->is_active ?? true) {
                    $mrr += (float) $subscription->monthly_revenue;
                }
            }
        }

        return round($mrr, 2);
    }
}
