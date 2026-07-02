<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\AppliesTenantCompanyFilter;
use App\Models\BilancioUfficiale;
use App\Models\CustomerContract;
use App\Models\Tenant;
use App\Support\Bilanci\BilancioPdfExtractor;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

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
        $bilancio = new BilancioUfficiale;
        $bilancio->fill($data);
        $bilancio->company_id = $this->resolveScopedCompanyId($this->companyIds($request), $data['company_id'] ?? null);
        $bilancio->created_by = auth()->id();
        $bilancio->save();

        // Land on edit so the official PDF can be attached right away.
        return redirect()->route('erp.bilanci.edit', $bilancio)->with('success', trans('erp/bilanci.saved'));
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

    /**
     * Extract the Conto Economico figures from the bilancio's attached PDF (Registro Imprese
     * layout) and pre-fill the edit form for review. Never overwrites saved data directly —
     * the values are flashed as form input so the user checks them before saving.
     */
    public function extractFromPdf(Request $request, BilancioUfficiale $bilancio, BilancioPdfExtractor $extractor): RedirectResponse
    {
        $this->authorize('update', CustomerContract::class);
        $this->assertCompanyAccessible($this->companyIds($request), $bilancio->company_id);

        $upload = $bilancio->uploads()->orderByDesc('created_at')->first();
        if (! $upload) {
            return redirect()->route('erp.bilanci.edit', $bilancio)->with('error', trans('erp/bilanci.extract_no_pdf'));
        }

        $relativePath = 'private_uploads/bilanci/'.$upload->filename;
        if (! Storage::exists($relativePath)) {
            return redirect()->route('erp.bilanci.edit', $bilancio)->with('error', trans('erp/bilanci.extract_no_pdf'));
        }

        $data = $extractor->extract(Storage::path($relativePath));

        // Need at least the top-line figures; otherwise the PDF is likely scanned or unusual.
        if (is_null($data['ricavi']) && is_null($data['costi']) && is_null($data['utile'])) {
            return redirect()->route('erp.bilanci.edit', $bilancio)->with('error', trans('erp/bilanci.extract_failed'));
        }

        // Absent Conto Economico cost lines legitimately mean 0 (e.g. no personnel).
        $input = [
            'anno' => $data['anno'] ?? $bilancio->anno,
            'ricavi' => $data['ricavi'],
            'costi' => $data['costi'],
            'costo_personale' => $data['costo_personale'] ?? 0,
            'ammortamenti' => $data['ammortamenti'] ?? 0,
            'imposte' => $data['imposte'] ?? 0,
            'utile' => $data['utile'],
            'is_deposited' => 1,
        ];
        $input = array_filter($input, fn ($v) => ! is_null($v));

        return redirect()->route('erp.bilanci.edit', $bilancio)
            ->withInput($input)
            ->with('success', trans('erp/bilanci.extract_done'));
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
