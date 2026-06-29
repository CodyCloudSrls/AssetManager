<?php

namespace App\Support\Reports;

use App\Models\Asset;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Italian fixed-asset depreciation register (libro dei cespiti / registro beni
 * ammortizzabili). Built on top of the existing asset data — purchase cost, purchase
 * date and the asset model's depreciation schedule — applying Italian conventions:
 *
 *   - quota annua = costo storico × coefficiente (coefficiente derived from the
 *     scheme's useful life: 12 / months),
 *   - prima quota ridotta al 50% nel primo esercizio (DPR 917/86 art. 102 c.2),
 *   - fondo ammortamento cumulato e valore residuo (netto contabile), il fondo non
 *     può eccedere il costo storico.
 *
 * No new data is invented: assets without a purchase cost/date or without a
 * depreciation scheme are excluded (not depreciable here).
 */
class AmmortamentiReport
{
    public function build(?array $companyIds = null, ?int $year = null): array
    {
        $year = $year ?: (int) Carbon::now()->year;

        $query = Asset::query()
            ->whereNotNull('purchase_cost')
            ->where('purchase_cost', '>', 0)
            ->whereNotNull('purchase_date')
            ->with(['model.depreciation', 'model.category']);

        if (! is_null($companyIds)) {
            $query = $companyIds === []
                ? $query->whereRaw('1 = 0')
                : $query->withoutGlobalScopes()->whereIn('assets.company_id', $companyIds);
        }

        $rows = new Collection();
        $totals = ['cost' => 0.0, 'fondo' => 0.0, 'residuo' => 0.0, 'quota_year' => 0.0];

        foreach ($query->get() as $asset) {
            $depreciation = optional($asset->model)->depreciation;
            $months = $depreciation->months ?? null;
            $coefficiente = $depreciation->coefficiente_annuo ?? null;

            // Italian ministerial coefficient when set; otherwise derive from useful life.
            if ($coefficiente !== null && (float) $coefficiente > 0) {
                $coeff = (float) $coefficiente / 100;
            } elseif ($months && $months > 0) {
                $coeff = 12 / (int) $months; // e.g. 60 months -> 0.20
            } else {
                continue; // not a depreciable cespite
            }

            $cost = (float) $asset->purchase_cost;
            $purchaseYear = (int) Carbon::parse($asset->purchase_date)->year;

            [$fondo, $quotaYear] = $this->fondoAndQuota($cost, $coeff, $purchaseYear, $year);
            $residuo = round($cost - $fondo, 2);

            $rows->push([
                'asset' => $asset,
                'category' => optional(optional($asset->model)->category)->name,
                'cost' => round($cost, 2),
                'coefficiente' => round($coeff * 100, 2),
                'purchase_year' => $purchaseYear,
                'quota_year' => $quotaYear,
                'fondo' => $fondo,
                'residuo' => $residuo,
                'fully_depreciated' => $residuo <= 0.0,
            ]);

            $totals['cost'] += $cost;
            $totals['fondo'] += $fondo;
            $totals['residuo'] += $residuo;
            $totals['quota_year'] += $quotaYear;
        }

        return [
            'year' => $year,
            'rows' => $rows->sortByDesc('cost')->values(),
            'totals' => array_map(fn ($v) => round($v, 2), $totals),
        ];
    }

    /**
     * Accumulated fund up to $year (first year halved, capped at cost) and the quota
     * charged in $year specifically.
     *
     * @return array{0:float,1:float} [fondo, quotaForYear]
     */
    private function fondoAndQuota(float $cost, float $coeff, int $purchaseYear, int $year): array
    {
        if ($year < $purchaseYear) {
            return [0.0, 0.0];
        }

        $fullQuota = round($cost * $coeff, 2);
        $fondo = 0.0;
        $quotaForYear = 0.0;

        for ($y = $purchaseYear; $y <= $year; $y++) {
            $quota = ($y === $purchaseYear) ? round($fullQuota / 2, 2) : $fullQuota;
            $quota = min($quota, round($cost - $fondo, 2)); // never depreciate below zero
            if ($quota < 0) {
                $quota = 0.0;
            }
            $fondo = round($fondo + $quota, 2);
            if ($y === $year) {
                $quotaForYear = $quota;
            }
        }

        return [$fondo, $quotaForYear];
    }
}
