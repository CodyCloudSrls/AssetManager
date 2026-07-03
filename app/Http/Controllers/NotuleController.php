<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\AppliesTenantCompanyFilter;
use App\Models\CustomerContract;
use App\Models\Notula;
use App\Models\Supplier;
use App\Models\Tenant;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Gestionale notule — payments owed to professionals before they issue a fiscal
 * invoice. Gated by the ERP feature; authorization reuses the ERP contracts ability.
 */
class NotuleController extends Controller
{
    use AppliesTenantCompanyFilter;

    public function index(Request $request): View
    {
        $this->authorize('view', CustomerContract::class);

        $companyIds = $this->notuleCompanyIds($request);
        $notule = Notula::forCompanies($companyIds)->with('supplier')
            ->orderByDesc('competence_date')->orderByDesc('id')->paginate(50);

        $totals = [
            'pending' => Notula::outstandingTotal($companyIds),
            'all' => (float) Notula::forCompanies($companyIds)->whereIn('status', [Notula::STATUS_UNPAID, Notula::STATUS_PAID])->sum('amount'),
        ];

        return view('erp.notule.index', compact('notule', 'totals'));
    }

    public function create(): View
    {
        $this->authorize('update', CustomerContract::class);

        return view('erp.notule.edit', ['item' => new Notula(['status' => Notula::STATUS_UNPAID])]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('update', CustomerContract::class);

        $data = $this->validateNotula($request);
        $notula = new Notula();
        $this->fill($notula, $data);
        $notula->company_id = $this->resolveScopedCompanyId($this->notuleCompanyIds($request), $data['company_id'] ?? null);
        $notula->created_by = auth()->id();
        $notula->save();

        return redirect()->route('erp.notule.index')->with('success', trans('erp/notule.created'));
    }

    public function edit(Request $request, Notula $notula): View
    {
        $this->authorize('update', CustomerContract::class);
        $this->assertCompanyAccessible($this->notuleCompanyIds($request), $notula->company_id);

        return view('erp.notule.edit', ['item' => $notula]);
    }

    public function update(Request $request, Notula $notula): RedirectResponse
    {
        $this->authorize('update', CustomerContract::class);
        $scope = $this->notuleCompanyIds($request);
        $this->assertCompanyAccessible($scope, $notula->company_id);

        $data = $this->validateNotula($request);
        $current = $notula->company_id;
        $this->fill($notula, $data);
        $notula->company_id = $this->resolveScopedCompanyId($scope, $data['company_id'] ?? null, $current);
        $notula->save();

        return redirect()->route('erp.notule.index')->with('success', trans('erp/notule.updated'));
    }

    public function destroy(Request $request, Notula $notula): RedirectResponse
    {
        $this->authorize('update', CustomerContract::class);
        $this->assertCompanyAccessible($this->notuleCompanyIds($request), $notula->company_id);

        $notula->delete();

        return redirect()->route('erp.notule.index')->with('success', trans('erp/notule.deleted'));
    }

    private function validateNotula(Request $request): array
    {
        return $request->validate([
            'supplier_id' => 'nullable|integer|exists:suppliers,id',
            'professional_name' => 'nullable|string|max:191|required_without:supplier_id',
            'description' => 'nullable|string|max:191',
            'amount' => 'required|numeric|min:0',
            'paid_amount' => 'nullable|numeric|min:0|lte:amount',
            'competence_date' => 'nullable|date',
            'expected_invoice_date' => 'nullable|date',
            'status' => 'required|string|in:'.implode(',', array_keys(Notula::statusOptions())),
            'invoice_received' => 'nullable|boolean',
            'paid_at' => 'nullable|date',
            'company_id' => 'nullable|integer|exists:companies,id',
            'notes' => 'nullable|string|max:65535',
        ]);
    }

    private function fill(Notula $notula, array $data): void
    {
        $notula->fill($data);
        // Checkbox: absent when unchecked, so coerce to a definite boolean.
        $notula->invoice_received = (bool) ($data['invoice_received'] ?? false);
        // Only a paid notula keeps a payment date.
        if ($notula->status !== Notula::STATUS_PAID) {
            $notula->paid_at = null;
        }
    }

    private function notuleCompanyIds(Request $request): ?array
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
