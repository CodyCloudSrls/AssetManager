<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\AppliesTenantCompanyFilter;
use App\Models\CustomerContract;
use App\Models\Previsionale;
use App\Models\Tenant;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Previsionale economico (forecast). ERP-gated; write reuses the ERP contracts ability.
 */
class PrevisionaliController extends Controller
{
    use AppliesTenantCompanyFilter;

    public function index(Request $request): View
    {
        $this->authorize('view', CustomerContract::class);

        $previsionali = Previsionale::forCompanies($this->companyIds($request))->orderBy('anno')->get();

        return view('erp.previsionali.index', ['previsionali' => $previsionali]);
    }

    public function create(): View
    {
        $this->authorize('update', CustomerContract::class);

        return view('erp.previsionali.edit', ['item' => new Previsionale(['anno' => (int) now()->year])]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('update', CustomerContract::class);

        $data = $this->validated($request);
        $p = new Previsionale();
        $p->fill($data);
        $p->company_id = $this->resolveScopedCompanyId($this->companyIds($request), $data['company_id'] ?? null);
        $p->created_by = auth()->id();
        $p->save();

        return redirect()->route('erp.previsionali.index')->with('success', trans('erp/previsionali.saved'));
    }

    public function edit(Request $request, Previsionale $previsionale): View
    {
        $this->authorize('update', CustomerContract::class);
        $this->assertCompanyAccessible($this->companyIds($request), $previsionale->company_id);

        return view('erp.previsionali.edit', ['item' => $previsionale]);
    }

    public function update(Request $request, Previsionale $previsionale): RedirectResponse
    {
        $this->authorize('update', CustomerContract::class);
        $scope = $this->companyIds($request);
        $this->assertCompanyAccessible($scope, $previsionale->company_id);

        $data = $this->validated($request);
        $current = $previsionale->company_id;
        $previsionale->fill($data);
        $previsionale->company_id = $this->resolveScopedCompanyId($scope, $data['company_id'] ?? null, $current);
        $previsionale->save();

        return redirect()->route('erp.previsionali.index')->with('success', trans('erp/previsionali.saved'));
    }

    public function destroy(Request $request, Previsionale $previsionale): RedirectResponse
    {
        $this->authorize('update', CustomerContract::class);
        $this->assertCompanyAccessible($this->companyIds($request), $previsionale->company_id);

        $previsionale->delete();

        return redirect()->route('erp.previsionali.index')->with('success', trans('erp/previsionali.deleted'));
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'anno' => 'required|integer|min:2000|max:2100',
            'ricavi' => 'nullable|numeric|min:0|max:9999999999',
            'ricavi_ricorrente' => 'nullable|numeric|min:0|max:9999999999',
            'cogs' => 'nullable|numeric|min:0|max:9999999999',
            'opex' => 'nullable|numeric|min:0|max:9999999999',
            'personale' => 'nullable|numeric|min:0|max:9999999999',
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
