<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\AppliesTenantCompanyFilter;
use App\Models\BilancioUfficiale;
use App\Models\Company;
use App\Models\Customer;
use App\Models\CustomerContract;
use App\Models\FicCashbookEntry;
use App\Models\FicDocument;
use App\Models\FicPaymentAccount;
use App\Models\Finanziamento;
use App\Models\ManagementInput;
use App\Models\Notula;
use App\Models\Supplier;
use App\Models\Tenant;
use App\Support\Fic\FicClient;
use App\Support\Reports\AmmortamentiReport;
use App\Support\Reports\ContractForecastReport;
use App\Support\Reports\ManagementControlReport;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

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
            'fic' => $this->ficSummary($companyIds),
            // Accrued cost from professionals not yet invoiced (pending notule only,
            // so once they invoice via FiC the cost is not double-counted).
            'notulePending' => Notula::outstandingTotal($companyIds),
        ]);
    }

    /**
     * Fotografia Finanziaria: the assembled real-time company status (bilanci ufficiali,
     * esposizione commerciale, indebitamento finanziario, posizione e PFN), reproducing
     * the reconciled financial snapshot.
     */
    public function fotografia(Request $request, ManagementControlReport $mc): View
    {
        $this->authorize('reports.view');

        $companyIds = $this->cockpitCompanyIds($request);
        $year = (int) Carbon::now()->year;
        $years = range($year - 4, $year);

        $ce = $mc->contoEconomico($companyIds, $years);
        $iva = $mc->iva($companyIds, [$year]);
        $cd = $mc->creditiDebiti($companyIds);
        $flussi = $mc->flussiCassa($companyIds, $year);

        $bilanci = BilancioUfficiale::forCompanies($companyIds)->orderBy('anno')->get();
        $notuleResiduo = Notula::outstandingTotal($companyIds);
        $debitiFic = $cd['tot_debiti'];
        $debitiCommerciali = round($debitiFic + $notuleResiduo, 2);
        $crediti = $cd['tot_crediti'];
        $debitoFinanziario = Finanziamento::totalResiduo($companyIds, true);

        // Real cash from the FiC cashbook (conti correnti) wins over the manual figure.
        $cassaReale = FicPaymentAccount::totalBalance($companyIds);
        $cassa = $cassaReale ?? ManagementInput::getValue($companyIds, ManagementInput::KEY_CASSA);
        $cassaSource = ! is_null($cassaReale) ? 'reale' : (is_null($cassa) ? null : 'manuale');

        return view('erp.fotografia', [
            'conti' => FicPaymentAccount::forCompanies($companyIds)->where('balance', '<>', 0)->orderByDesc('balance')->get(),
            'cassaSource' => $cassaSource,
            'year' => $year,
            'ce' => $ce,
            'years' => $years,
            'bilanci' => $bilanci,
            'utileCumulato' => round((float) $bilanci->sum('utile'), 2),
            'kpi' => [
                'ricavi' => $ce[$year]['ricavi'] ?? 0,
                'ebit' => $ce[$year]['ebit'] ?? 0,
                'personale' => $ce[$year]['personale'] ?? 0,
                'cassa_netta_ytd' => $flussi['netto'],
                'saldo_iva' => $iva[$year]['saldo'] ?? 0,
            ],
            'esposizione' => [
                'debiti_fic' => $debitiFic,
                'notule' => $notuleResiduo,
                'totale' => $debitiCommerciali,
                'top' => $cd['debiti']->take(6),
            ],
            'finanziario' => [
                'debito' => $debitoFinanziario,
                'finanziamenti' => Finanziamento::forCompanies($companyIds)->orderBy('nome')->get(),
            ],
            'posizione' => [
                'crediti' => $crediti,
                'debiti_commerciali' => $debitiCommerciali,
                'saldo_commerciale' => round($crediti - $debitiCommerciali, 2),
                'debito_finanziario' => $debitoFinanziario,
                'cassa' => $cassa,
                'pfn' => is_null($cassa) ? null : round($debitoFinanziario - $cassa, 2),
            ],
        ]);
    }

    /** Persist a manual financial input (cassa/banca attuale). */
    public function saveFotografiaInput(Request $request): RedirectResponse
    {
        $this->authorize('update', CustomerContract::class);
        $request->validate(['cassa_attuale' => 'nullable|numeric']);

        ManagementInput::setValue(
            $this->cockpitCompanyIds($request),
            ManagementInput::KEY_CASSA,
            (float) $request->input('cassa_attuale', 0)
        );

        return redirect()->route('erp.fotografia')->with('success', trans('erp/fotografia.saved'));
    }

    /**
     * Bilancio simulato "ad oggi" — Conto Economico in the Italian civil-code schema
     * (art. 2425 c.c.), computed from real data (FiC reclassified + ammortamenti). When a
     * deposited bilancio exists for the year its imposte win; otherwise a flagged IRES
     * estimate is shown (the fiscal truth stays with FiC + commercialista).
     */
    public function bilancioSimulato(Request $request, ManagementControlReport $mc, AmmortamentiReport $amm): View
    {
        $this->authorize('reports.view');

        $companyIds = $this->cockpitCompanyIds($request);
        $currentYear = (int) Carbon::now()->year;
        $year = (int) $request->input('year', $currentYear);

        $ce = $mc->contoEconomico($companyIds, [$year])[$year];
        $ammortamenti = round((float) ($amm->build($companyIds, $year)['totals']['quota_year'] ?? 0), 2);
        $bilancio = BilancioUfficiale::forCompanies($companyIds)->where('anno', $year)->first();

        $valoreProduzione = (float) $ce['ricavi'];
        $b6 = (float) $ce['cogs'];
        $b7 = (float) $ce['opex'];
        $b9 = (float) $ce['personale'];
        $b10 = $ammortamenti;
        $costiProduzione = round($b6 + $b7 + $b9 + $b10, 2);
        $diffAB = round($valoreProduzione - $costiProduzione, 2);

        if ($bilancio && $bilancio->is_deposited && (float) $bilancio->imposte > 0) {
            $imposte = (float) $bilancio->imposte;
            $impSource = 'reale';
        } else {
            $imposte = round(max(0, $diffAB) * 0.24, 2); // IRES 24% estimate
            $impSource = 'stima';
        }
        $utile = round($diffAB - $imposte, 2);

        return view('erp.bilancio-simulato', [
            'year' => $year,
            'years' => range($currentYear, $currentYear - 4),
            'isCurrent' => $year === $currentYear,
            'rows' => compact('valoreProduzione', 'b6', 'b7', 'b9', 'b10', 'costiProduzione', 'diffAB', 'imposte', 'utile'),
            'impSource' => $impSource,
            'hasBilancio' => (bool) $bilancio,
        ]);
    }

    /**
     * Controllo di gestione: reclassified income statement, IVA, cash flows and
     * receivables/payables from the FiC mirror (the Gestionale Unico cockpit).
     */
    public function controlloGestione(Request $request, ManagementControlReport $report): View
    {
        $this->authorize('reports.view');

        $companyIds = $this->cockpitCompanyIds($request);
        $currentYear = (int) Carbon::now()->year;
        $years = range($currentYear - 4, $currentYear);

        return view('erp.controllo', [
            'years' => $years,
            'ce' => $report->contoEconomico($companyIds, $years),
            'iva' => $report->iva($companyIds, $years),
            'cassa' => $report->flussiCassa($companyIds, $currentYear),
            'creditiDebiti' => $report->creditiDebiti($companyIds),
            'hasData' => $report->hasData($companyIds),
        ]);
    }

    /**
     * Riconciliazione incassi multi-canale: matches money received per channel
     * (TS Pay, Carta, PayPal, banche, ...) against the FiC document each cashbook
     * movement settled, surfacing the incassi not yet tied to an invoice.
     */
    public function riconciliazione(Request $request): View
    {
        $this->authorize('reports.view');

        $companyIds = $this->cockpitCompanyIds($request);
        $currentYear = (int) Carbon::now()->year;
        $year = (int) $request->input('year', $currentYear);
        $channel = $request->filled('channel') ? (string) $request->input('channel') : null;

        $scoped = fn () => FicCashbookEntry::forCompanies($companyIds)->whereYear('entry_date', $year);

        $channels = $scoped()->incassi()
            ->selectRaw('account_name,
                count(*) as movimenti,
                sum(amount) as totale,
                sum(case when document_fic_id is null then amount else 0 end) as non_collegato,
                sum(case when document_fic_id is null then 1 else 0 end) as non_collegati')
            ->groupBy('account_name')
            ->orderByDesc('totale')
            ->get();

        $unmatched = $scoped()->incassi()
            ->whereNull('document_fic_id')
            ->when($channel, fn ($q) => $q->where('account_name', $channel))
            ->orderByDesc('entry_date')
            ->limit(200)
            ->get();

        $detail = $channel
            ? $scoped()->incassi()->where('account_name', $channel)
                ->with('document')
                ->orderByDesc('entry_date')
                ->limit(300)
                ->get()
            : collect();

        return view('erp.riconciliazione', [
            'year' => $year,
            'years' => range($currentYear - 4, $currentYear),
            'channels' => $channels,
            'unmatched' => $unmatched,
            'detail' => $detail,
            'channel' => $channel,
            'hasData' => FicCashbookEntry::forCompanies($companyIds)->exists(),
        ]);
    }

    /**
     * Libro dei cespiti / registro beni ammortizzabili (Italian fixed-asset
     * depreciation register), computed from the asset data via AmmortamentiReport.
     */
    public function ammortamenti(Request $request, AmmortamentiReport $report): View
    {
        $this->authorize('reports.view');

        $companyIds = $this->cockpitCompanyIds($request);
        $currentYear = (int) Carbon::now()->year;
        $year = (int) $request->input('year', $currentYear);

        $data = $report->build($companyIds, $year);
        $data['years'] = range($currentYear, $currentYear - 6);

        return view('erp.ammortamenti', $data);
    }

    /**
     * Fiscal summary from the read-only FiC mirror: revenue, receivables/payables,
     * VAT balance and the upcoming/overdue deadlines (scadenzario). Returns
     * ['enabled' => false] when nothing has been synced yet.
     */
    private function ficSummary(?array $companyIds): array
    {
        if (! FicDocument::query()->forCompanies($companyIds)->exists()) {
            return ['enabled' => false];
        }

        $yearStart = Carbon::now()->startOfYear();
        $outstanding = DB::raw('amount_gross - paid_amount');

        $receivables = (float) FicDocument::issued()->unpaid()->forCompanies($companyIds)->sum($outstanding);
        $payables = (float) FicDocument::received()->unpaid()->forCompanies($companyIds)->sum($outstanding);
        $overdueReceivables = (float) FicDocument::issued()->unpaid()->forCompanies($companyIds)
            ->whereNotNull('due_on')->whereDate('due_on', '<', Carbon::now())->sum($outstanding);

        $revenueYear = (float) FicDocument::issued()->forCompanies($companyIds)
            ->whereDate('issued_on', '>=', $yearStart)->sum('amount_net');
        $vatIssued = (float) FicDocument::issued()->forCompanies($companyIds)
            ->whereDate('issued_on', '>=', $yearStart)->sum('amount_vat');
        $vatReceived = (float) FicDocument::received()->forCompanies($companyIds)
            ->whereDate('issued_on', '>=', $yearStart)->sum('amount_vat');

        // Scadenzario: unpaid documents with a due date, overdue first then nearest.
        $deadlines = FicDocument::query()->forCompanies($companyIds)->unpaid()
            ->whereNotNull('due_on')
            ->where('due_on', '<=', Carbon::now()->addDays(60))
            ->orderBy('due_on')
            ->limit(25)
            ->get();

        return [
            'enabled' => true,
            'last_sync' => FicDocument::forCompanies($companyIds)->max('synced_at'),
            'receivables' => $receivables,
            'payables' => $payables,
            'overdue_receivables' => $overdueReceivables,
            'revenue_year' => $revenueYear,
            'vat_balance' => round($vatIssued - $vatReceived, 2),
            'deadlines' => $deadlines,
        ];
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

        // Superadmins see everything (no company scope), like elsewhere in the app.
        $user = auth()->user();
        if ($user?->isSuperUser()) {
            return null;
        }

        $userCompanyId = $user?->company_id;
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

    /**
     * MRR = monthly-equivalent of the subscriptions that are RECURRING and CURRENTLY in
     * their service period, on active contracts. Excludes ended/not-yet-started lines and
     * one-offs (which are not recurring) so the figure reflects real recurring revenue.
     */
    private function monthlyRecurringRevenue(?array $companyIds): float
    {
        $today = Carbon::now()->startOfDay();
        $contracts = $this->scopeCompanies(
            CustomerContract::query()->where('status', CustomerContract::STATUS_ACTIVE),
            $companyIds
        )->with('subscriptions')->get();

        $mrr = 0.0;
        foreach ($contracts as $contract) {
            foreach ($contract->subscriptions as $subscription) {
                if (! ($subscription->is_active ?? true)) {
                    continue;
                }
                if ($subscription->billing_frequency === \App\Models\ContractSubscription::FREQUENCY_ONE_TIME) {
                    continue; // one-offs are not recurring revenue
                }
                if ($subscription->starts_at && $subscription->starts_at->gt($today)) {
                    continue; // not started yet
                }
                if ($subscription->ends_at && $subscription->ends_at->lt($today)) {
                    continue; // already ended
                }
                $mrr += (float) $subscription->monthly_revenue;
            }
        }

        return round($mrr, 2);
    }
}
