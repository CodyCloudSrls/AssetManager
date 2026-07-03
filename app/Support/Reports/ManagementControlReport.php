<?php

namespace App\Support\Reports;

use App\Models\BilancioUfficiale;
use App\Models\FicCostCategory;
use App\Models\FicDocument;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Controllo di gestione engine. Reproduces the legacy "Gestionale Unico" cockpit from
 * the read-only FiC mirror: Conto Economico riclassificato (COGS/OPEX/LABOR), IVA,
 * flussi di cassa and crediti/debiti. FiC stays the fiscal source of truth — this is
 * the analytical layer only.
 */
class ManagementControlReport
{
    /** Category -> bucket map (DB overrides, fallback OPEX). */
    private function bucketMap(): array
    {
        return FicCostCategory::query()->pluck('bucket', 'category')->all();
    }

    /**
     * Reclassified income statement per year.
     *
     * @param  int[]  $years
     */
    public function contoEconomico(?array $companyIds, array $years): array
    {
        $map = $this->bucketMap();
        $officialPayroll = BilancioUfficiale::payrollByYear($companyIds);
        $rows = [];

        foreach ($years as $year) {
            $ricavi = (float) FicDocument::issued()->forCompanies($companyIds)
                ->whereYear('issued_on', $year)->sum('amount_net');

            $cogs = $opex = $laborFic = 0.0;
            $received = FicDocument::received()->forCompanies($companyIds)
                ->whereYear('issued_on', $year)->get(['category', 'amount_net']);

            foreach ($received as $doc) {
                $bucket = $map[$doc->category] ?? FicCostCategory::BUCKET_OPEX;
                $alloc = FicCostCategory::allocate($bucket, (float) $doc->amount_net);
                $cogs += $alloc['cogs'];
                $opex += $alloc['opex'];
                $laborFic += $alloc['labor'];
            }

            // Authoritative precedence: a deposited bilancio's payroll wins over FiC.
            $hasOfficial = array_key_exists($year, $officialPayroll);
            $personale = $hasOfficial ? $officialPayroll[$year] : $laborFic;

            $margine = round($ricavi - $cogs, 2);
            $rows[$year] = [
                'year' => $year,
                'ricavi' => round($ricavi, 2),
                'cogs' => round($cogs, 2),
                'margine_lordo' => $margine,
                'margine_pct' => $ricavi > 0 ? round($margine / $ricavi * 100, 1) : null,
                'opex' => round($opex, 2),
                'personale' => round($personale, 2),
                'personale_source' => $hasOfficial ? 'reale' : 'fic',
                // Pre-depreciation operating result = EBITDA / MOL (depreciation is not in
                // the FiC cost data — it lives in the asset register / bilancio simulato).
                // Labelled "MOL (EBITDA)" in the UI; the key stays 'ebit' for compatibility.
                'ebit' => round($margine - $opex - $personale, 2),
            ];
        }

        return $rows;
    }

    /** IVA debito/credito/saldo per year (saldo > 0 = da versare). */
    public function iva(?array $companyIds, array $years): array
    {
        $rows = [];
        foreach ($years as $year) {
            $debito = (float) FicDocument::issued()->forCompanies($companyIds)->whereYear('issued_on', $year)->sum('amount_vat');
            $credito = (float) FicDocument::received()->forCompanies($companyIds)->whereYear('issued_on', $year)->sum('amount_vat');
            $rows[$year] = [
                'year' => $year,
                'debito' => round($debito, 2),
                'credito' => round($credito, 2),
                'saldo' => round($debito - $credito, 2),
            ];
        }

        return $rows;
    }

    /** Flussi di cassa: per month (realized by paid_on) + year totals + cumulato. */
    public function flussiCassa(?array $companyIds, int $year): array
    {
        $months = [];
        $cumulato = 0.0;
        for ($m = 1; $m <= 12; $m++) {
            $incassi = (float) FicDocument::issued()->forCompanies($companyIds)
                ->whereYear('paid_on', $year)->whereMonth('paid_on', $m)->sum('paid_amount');
            $pagamenti = (float) FicDocument::received()->forCompanies($companyIds)
                ->whereYear('paid_on', $year)->whereMonth('paid_on', $m)->sum('paid_amount');
            $netto = round($incassi - $pagamenti, 2);
            $cumulato = round($cumulato + $netto, 2);
            $months[] = [
                'month' => $m,
                'label' => Carbon::create($year, $m, 1)->isoFormat('MMM'),
                'incassi' => round($incassi, 2),
                'pagamenti' => round($pagamenti, 2),
                'netto' => $netto,
                'cumulato' => $cumulato,
            ];
        }

        return [
            'year' => $year,
            'months' => $months,
            'incassi' => round(array_sum(array_column($months, 'incassi')), 2),
            'pagamenti' => round(array_sum(array_column($months, 'pagamenti')), 2),
            'netto' => round(array_sum(array_column($months, 'netto')), 2),
        ];
    }

    /** Open receivables / payables by counterparty. */
    public function creditiDebiti(?array $companyIds): array
    {
        // Group by canonical counterparty: VAT number when present, else the
        // case/space-normalized name — so "ITALWAY S.R.L." and "Italway S.r.l." collapse
        // into one row instead of showing dirty duplicates.
        $canonical = "COALESCE(NULLIF(TRIM(entity_vat), ''), UPPER(TRIM(entity_name)))";
        $aggregate = fn (string $direction) => FicDocument::query()
            ->where('direction', $direction)->unpaid()->forCompanies($companyIds)
            ->select(DB::raw('MAX(entity_name) as entity_name'), DB::raw('SUM(amount_gross - paid_amount) as aperto'))
            ->groupBy(DB::raw($canonical))->havingRaw('SUM(amount_gross - paid_amount) > 0')
            ->orderByDesc('aperto')->get();

        $crediti = $aggregate(FicDocument::DIRECTION_ISSUED);
        $debiti = $aggregate(FicDocument::DIRECTION_RECEIVED);

        return [
            'crediti' => $crediti,
            'debiti' => $debiti,
            'tot_crediti' => round((float) $crediti->sum('aperto'), 2),
            'tot_debiti' => round((float) $debiti->sum('aperto'), 2),
        ];
    }

    public function hasData(?array $companyIds): bool
    {
        return FicDocument::query()->forCompanies($companyIds)->exists();
    }
}
