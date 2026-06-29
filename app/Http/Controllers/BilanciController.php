<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\AppliesTenantCompanyFilter;
use App\Models\BilancioUfficiale;
use App\Models\CustomerContract;
use App\Models\Tenant;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Storico bilanci ufficiali (deposited yearly accounts). Authoritative figures used by
 * the controllo di gestione (payroll precedence) and the Fotografia Finanziaria.
 * Gated by the ERP feature; write reuses the ERP contracts ability.
 */
class BilanciController extends Controller
{
    use AppliesTenantCompanyFilter;

    public function index(Request $request): View
    {
        $this->authorize('view', CustomerContract::class);

        $companyIds = $this->companyIds($request);
        $bilanci = BilancioUfficiale::forCompanies($companyIds)->orderBy('anno')->get();

        return view('erp.bilanci.index', [
            'bilanci' => $bilanci,
            'utileCumulato' => round((float) $bilanci->sum('utile'), 2),
        ]);
    }

    public function create(): View
    {
        $this->authorize('update', CustomerContract::class);

        return view('erp.bilanci.edit', ['item' => new BilancioUfficiale(['anno' => (int) now()->year - 1, 'is_deposited' => true])]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('update', CustomerContract::class);

        $data = $this->validated($request);
        $bilancio = new BilancioUfficiale();
        $bilancio->fill($data);
        $bilancio->company_id = $this->resolveScopedCompanyId($this->companyIds($request), $data['company_id'] ?? null);
        $bilancio->created_by = auth()->id();
        $bilancio->save();

        return redirect()->route('erp.bilanci.index')->with('success', trans('erp/bilanci.saved'));
    }

    public function edit(Request $request, BilancioUfficiale $bilancio): View
    {
        $this->authorize('update', CustomerContract::class);
        $this->assertCompanyAccessible($this->companyIds($request), $bilancio->company_id);

        return view('erp.bilanci.edit', ['item' => $bilancio]);
    }

    public function update(Request $request, BilancioUfficiale $bilancio): RedirectResponse
    {
        $this->authorize('update', CustomerContract::class);
        $scope = $this->companyIds($request);
        $this->assertCompanyAccessible($scope, $bilancio->company_id);

        $data = $this->validated($request);
        $current = $bilancio->company_id;
        $bilancio->fill($data);
        $bilancio->company_id = $this->resolveScopedCompanyId($scope, $data['company_id'] ?? null, $current);
        $bilancio->save();

        return redirect()->route('erp.bilanci.index')->with('success', trans('erp/bilanci.saved'));
    }

    public function destroy(Request $request, BilancioUfficiale $bilancio): RedirectResponse
    {
        $this->authorize('update', CustomerContract::class);
        $this->assertCompanyAccessible($this->companyIds($request), $bilancio->company_id);

        $bilancio->delete();

        return redirect()->route('erp.bilanci.index')->with('success', trans('erp/bilanci.deleted'));
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'anno' => 'required|integer|min:2000|max:2100',
            'ricavi' => 'nullable|numeric|min:0|max:9999999999',
            'costi' => 'nullable|numeric|min:0|max:9999999999',
            'costo_personale' => 'nullable|numeric|min:0|max:9999999999',
            'ammortamenti' => 'nullable|numeric|min:0|max:9999999999',
            'utile' => 'nullable|numeric|min:-9999999999|max:9999999999',
            'imposte' => 'nullable|numeric|min:-9999999999|max:9999999999',
            'is_deposited' => 'nullable|boolean',
            'company_id' => 'nullable|integer|exists:companies,id',
            'notes' => 'nullable|string|max:65535',
        ]);
    }

    private function companyIds(Request $request): ?array
    {
        $companyIds = $this->tenantCompanyIdsFromRequest($request);
        if (! is_null($companyIds)) {
            return $companyIds;
        }
        if ($activeTenant = Tenant::activeTenant()) {
            return $activeTenant->activeCompanyIds();
        }
        $user = auth()->user();
        if ($user?->isSuperUser()) {
            return null;
        }

        return is_null($user?->company_id) ? null : [(int) $user->company_id];
    }
}
