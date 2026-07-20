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

        // Closed sets: a forged value is dropped, never handed to the query builder.
        $status = in_array($request->input('status'), array_keys(Notula::statusOptions()), true)
            ? (string) $request->input('status') : null;
        $invoiced = in_array($request->input('invoiced'), ['0', '1'], true)
            ? (string) $request->input('invoiced') : null;
        $professional = (string) $request->input('professional', '');

        // Single factory: the rows AND both total cards derive from the same scoped+filtered
        // query, so the cards can never contradict the visible rows, and no filter can escape
        // forCompanies() (the tenant guard).
        $base = function () use ($companyIds, $status, $invoiced, $professional) {
            $query = Notula::forCompanies($companyIds);

            if (! is_null($status)) {
                $query->where('status', $status);
            }
            if (! is_null($invoiced)) {
                $query->where('invoice_received', $invoiced === '1');
            }
            // display_name prefers the supplier name, so the free-text bucket is only the rows
            // WITHOUT a supplier_id (see Notula::getDisplayNameAttribute()).
            if (str_starts_with($professional, 'sup:')) {
                $query->where('supplier_id', (int) substr($professional, 4));
            } elseif (str_starts_with($professional, 'txt:')) {
                $query->whereNull('supplier_id')->where('professional_name', substr($professional, 4));
            }

            return $query;
        };

        $notule = $base()->with('supplier')->orderByDesc('competence_date')->orderByDesc('id')->get();

        $totals = [
            'pending' => Notula::outstandingSum($base()),
            'all' => (float) $base()->whereIn('status', [Notula::STATUS_UNPAID, Notula::STATUS_PAID])->sum('amount'),
        ];

        $professionals = $this->notuleProfessionalOptions($companyIds);

        return view('erp.notule.index', compact('notule', 'totals', 'professionals'));
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

        // paid_amount is NOT NULL in the DB: an unpaid notula has nothing paid yet (0), a paid
        // one defaults to the full amount unless a partial amount was entered. Guards the 500
        // on creating an unpaid notula (the form omits paid_amount when status = unpaid).
        if ($notula->status === Notula::STATUS_PAID) {
            $notula->paid_amount = $data['paid_amount'] ?? $notula->amount ?? 0;
        } else {
            $notula->paid_amount = $data['paid_amount'] ?? 0;
            // Only a paid notula keeps a payment date.
            $notula->paid_at = null;
        }
    }

    /**
     * Professionals actually used by this tenant's notule. A notula names its professional either
     * through supplier_id (display_name = supplier name) or through the free-text
     * professional_name, so the list is the union of both; option values are prefixed so the two
     * shapes stay distinguishable ("sup:<id>" vs "txt:<name>").
     *
     * @return array<string,string> option value => label
     */
    private function notuleProfessionalOptions(?array $companyIds): array
    {
        $options = [];

        $supplierIds = Notula::forCompanies($companyIds)->whereNotNull('supplier_id')
            ->distinct()->pluck('supplier_id')->all();

        if ($supplierIds !== []) {
            // withTrashed(): Notula::supplier() is withTrashed() too, so a soft-deleted supplier
            // must stay selectable or its rows become unfilterable.
            foreach (Supplier::withTrashed()->whereIn('id', $supplierIds)->get(['id', 'name']) as $supplier) {
                $options['sup:'.$supplier->id] = $supplier->name;
            }
        }

        $freeNames = Notula::forCompanies($companyIds)->whereNull('supplier_id')
            ->whereNotNull('professional_name')->where('professional_name', '<>', '')
            ->distinct()->pluck('professional_name')->all();

        foreach ($freeNames as $name) {
            $options['txt:'.$name] = $name;
        }

        asort($options, SORT_NATURAL | SORT_FLAG_CASE);

        return $options;
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
