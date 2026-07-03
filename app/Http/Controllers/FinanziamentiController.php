<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\AppliesTenantCompanyFilter;
use App\Models\CustomerContract;
use App\Models\Finanziamento;
use App\Models\Tenant;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Finanziamenti / rate loans (telematica, etc.). ERP-gated; write reuses the ERP
 * contracts ability. Feeds the PFN in the Fotografia Finanziaria.
 */
class FinanziamentiController extends Controller
{
    use AppliesTenantCompanyFilter;

    public function index(Request $request): View
    {
        $this->authorize('view', CustomerContract::class);

        $companyIds = $this->companyIds($request);
        $finanziamenti = Finanziamento::forCompanies($companyIds)->orderBy('nome')->get();

        return view('erp.finanziamenti.index', [
            'finanziamenti' => $finanziamenti,
            'totResiduo' => round((float) $finanziamenti->sum('residuo'), 2),
        ]);
    }

    public function create(): View
    {
        $this->authorize('update', CustomerContract::class);

        return view('erp.finanziamenti.edit', ['item' => new Finanziamento(['stato' => Finanziamento::STATO_CONFERMATO])]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('update', CustomerContract::class);

        $data = $this->validated($request);
        $f = new Finanziamento();
        $f->fill($data);
        $f->company_id = $this->resolveScopedCompanyId($this->companyIds($request), $data['company_id'] ?? null);
        $f->created_by = auth()->id();
        $f->save();

        return redirect()->route('erp.finanziamenti.index')->with('success', trans('erp/finanziamenti.saved'));
    }

    public function edit(Request $request, Finanziamento $finanziamento): View
    {
        $this->authorize('update', CustomerContract::class);
        $this->assertCompanyAccessible($this->companyIds($request), $finanziamento->company_id);

        return view('erp.finanziamenti.edit', ['item' => $finanziamento]);
    }

    public function update(Request $request, Finanziamento $finanziamento): RedirectResponse
    {
        $this->authorize('update', CustomerContract::class);
        $scope = $this->companyIds($request);
        $this->assertCompanyAccessible($scope, $finanziamento->company_id);

        $data = $this->validated($request);
        $current = $finanziamento->company_id;
        $finanziamento->fill($data);
        $finanziamento->company_id = $this->resolveScopedCompanyId($scope, $data['company_id'] ?? null, $current);
        $finanziamento->save();

        return redirect()->route('erp.finanziamenti.index')->with('success', trans('erp/finanziamenti.saved'));
    }

    public function destroy(Request $request, Finanziamento $finanziamento): RedirectResponse
    {
        $this->authorize('update', CustomerContract::class);
        $this->assertCompanyAccessible($this->companyIds($request), $finanziamento->company_id);

        $finanziamento->delete();

        return redirect()->route('erp.finanziamenti.index')->with('success', trans('erp/finanziamenti.deleted'));
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'nome' => 'required|string|max:191',
            'rata_mensile' => 'required|numeric|min:0',
            'rate_totali' => 'required|integer|min:0|max:600',
            'rate_pagate' => 'required|integer|min:0|max:600',
            'stato' => 'required|string|in:confermato,da_confermare',
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

        // No superuser, no active tenant, no company: an empty scope (see/touch nothing),
        // never null — null means "unrestricted" and would leak every tenant's records.
        return is_null($user?->company_id) ? [] : [(int) $user->company_id];
    }
}
