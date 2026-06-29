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
            'all' => (float) Notula::forCompanies($companyIds)->whereIn('status', [Notula::STATUS_PENDING, Notula::STATUS_INVOICED, Notula::STATUS_PAID])->sum('amount'),
        ];

        return view('erp.notule.index', compact('notule', 'totals'));
    }

    public function create(): View
    {
        $this->authorize('update', CustomerContract::class);

        return view('erp.notule.edit', ['item' => new Notula(['status' => Notula::STATUS_PENDING])]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('update', CustomerContract::class);

        $notula = new Notula();
        $this->fill($notula, $this->validateNotula($request));
        $notula->created_by = auth()->id();
        $notula->save();

        return redirect()->route('erp.notule.index')->with('success', trans('erp/notule.created'));
    }

    public function edit(Notula $notula): View
    {
        $this->authorize('update', CustomerContract::class);

        return view('erp.notule.edit', ['item' => $notula]);
    }

    public function update(Request $request, Notula $notula): RedirectResponse
    {
        $this->authorize('update', CustomerContract::class);

        $this->fill($notula, $this->validateNotula($request));
        $notula->save();

        return redirect()->route('erp.notule.index')->with('success', trans('erp/notule.updated'));
    }

    public function destroy(Notula $notula): RedirectResponse
    {
        $this->authorize('update', CustomerContract::class);

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
            'competence_date' => 'nullable|date',
            'expected_invoice_date' => 'nullable|date',
            'status' => 'required|string|in:'.implode(',', array_keys(Notula::statusOptions())),
            'paid_at' => 'nullable|date',
            'company_id' => 'nullable|integer|exists:companies,id',
            'notes' => 'nullable|string',
        ]);
    }

    private function fill(Notula $notula, array $data): void
    {
        $notula->fill($data);
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

        return is_null($user?->company_id) ? null : [(int) $user->company_id];
    }
}
