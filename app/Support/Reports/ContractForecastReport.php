<?php

namespace App\Support\Reports;

use App\Models\ContractCostLine;
use App\Models\ContractSubscription;
use App\Models\CustomerContract;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class ContractForecastReport
{
    public function build(?array $companyIds = null, ?string $from = null, ?string $to = null): array
    {
        $start = $from ? Carbon::createFromFormat('Y-m-d', $from)->startOfMonth() : now()->startOfMonth();
        $end = $to ? Carbon::createFromFormat('Y-m-d', $to)->startOfMonth() : $start->copy()->addMonths(11);

        if ($end->lt($start)) {
            [$start, $end] = [$end, $start];
        }

        $contracts = $this->contracts($companyIds);
        $months = collect(CarbonPeriod::create($start, '1 month', $end))
            ->map(fn (Carbon $month) => $month->copy()->startOfMonth());

        $currencies = $contracts
            ->pluck('currency')
            ->filter()
            ->map(fn ($currency) => strtoupper((string) $currency))
            ->unique()
            ->sort()
            ->values();

        $monthlyRows = $months
            ->flatMap(fn (Carbon $month) => $currencies->map(fn (string $currency) => $this->monthRow(
                $month,
                $contracts->filter(fn (CustomerContract $contract) => strtoupper((string) $contract->currency) === $currency),
                $currency
            )))
            ->values();

        $contractRows = $contracts
            ->map(fn (CustomerContract $contract) => $this->contractRow($contract, $months))
            ->values();

        return [
            'from' => $start,
            'to' => $end,
            'summary' => [
                'contracts_count' => $contracts->count(),
            ],
            'summaryRows' => $this->summaryRows($monthlyRows, $contracts),
            'monthlyRows' => $monthlyRows,
            'quarterRows' => $this->aggregateRows($monthlyRows, 'quarter_key', 'quarter_label'),
            'yearRows' => $this->aggregateRows($monthlyRows, 'year_key', 'year_label'),
            'contractRows' => $contractRows,
        ];
    }

    private function contracts(?array $companyIds): Collection
    {
        return CustomerContract::query()
            ->with(['company', 'customer', 'subscriptions.costLines.supplier'])
            ->where('status', CustomerContract::STATUS_ACTIVE)
            ->when(! is_null($companyIds), function (Builder $query) use ($companyIds) {
                if ($companyIds === []) {
                    $query->whereRaw('1 = 0');

                    return;
                }

                $query->whereIn('company_id', $companyIds);
            })
            ->orderBy('name')
            ->get();
    }

    private function monthRow(Carbon $month, Collection $contracts, string $currency): array
    {
        $revenue = 0.0;
        $cost = 0.0;

        foreach ($contracts as $contract) {
            foreach ($contract->subscriptions as $subscription) {
                $revenue += $this->subscriptionAmountForMonth($subscription, $month);

                foreach ($subscription->costLines as $costLine) {
                    $cost += $this->costAmountForMonth($costLine, $month);
                }
            }
        }

        return [
            'month' => $month,
            'currency' => $currency,
            'month_key' => $month->format('Y-m'),
            'month_label' => $month->translatedFormat('F Y'),
            'quarter_key' => $month->format('Y').'-Q'.$month->quarter,
            'quarter_label' => $month->format('Y').' Q'.$month->quarter,
            'year_key' => $month->format('Y'),
            'year_label' => $month->format('Y'),
            'revenue' => round($revenue, 2),
            'cost' => round($cost, 2),
            'net' => round($revenue - $cost, 2),
        ];
    }

    private function contractRow(CustomerContract $contract, Collection $months): array
    {
        $revenue = 0.0;
        $cost = 0.0;

        foreach ($months as $month) {
            foreach ($contract->subscriptions as $subscription) {
                $revenue += $this->subscriptionAmountForMonth($subscription, $month);

                foreach ($subscription->costLines as $costLine) {
                    $cost += $this->costAmountForMonth($costLine, $month);
                }
            }
        }

        return [
            'contract' => $contract,
            'customer' => $contract->customer,
            'company' => $contract->company,
            'currency' => strtoupper((string) ($contract->currency ?: 'EUR')),
            'revenue' => round($revenue, 2),
            'cost' => round($cost, 2),
            'net' => round($revenue - $cost, 2),
            'margin_percent' => $revenue > 0 ? round((($revenue - $cost) / $revenue) * 100, 2) : null,
        ];
    }

    private function aggregateRows(Collection $rows, string $keyField, string $labelField): Collection
    {
        return $rows
            ->groupBy(fn (array $row) => $row[$keyField].'|'.$row['currency'])
            ->map(function (Collection $rows) use ($labelField) {
                $first = $rows->first();
                $revenue = $rows->sum('revenue');
                $cost = $rows->sum('cost');

                return [
                    'key' => $first[$labelField],
                    'label' => $first[$labelField],
                    'currency' => $first['currency'],
                    'revenue' => round($revenue, 2),
                    'cost' => round($cost, 2),
                    'net' => round($revenue - $cost, 2),
                ];
            })
            ->values();
    }

    private function summaryRows(Collection $monthlyRows, Collection $contracts): Collection
    {
        return $monthlyRows
            ->groupBy('currency')
            ->map(function (Collection $rows, string $currency) use ($contracts) {
                $revenue = $rows->sum('revenue');
                $cost = $rows->sum('cost');

                return [
                    'currency' => $currency,
                    'revenue' => round($revenue, 2),
                    'cost' => round($cost, 2),
                    'net' => round($revenue - $cost, 2),
                    'contracts_count' => $contracts->filter(fn (CustomerContract $contract) => strtoupper((string) $contract->currency) === $currency)->count(),
                ];
            })
            ->values();
    }

    private function subscriptionAmountForMonth(ContractSubscription $subscription, Carbon $month): float
    {
        if (! $subscription->is_active || ! $this->activeForMonth($subscription->starts_at, $subscription->ends_at, $month)) {
            return 0.0;
        }

        $amount = (float) $subscription->quantity * (float) $subscription->unit_price;

        if ($subscription->billing_frequency === ContractSubscription::FREQUENCY_ONE_TIME) {
            return $subscription->starts_at && $subscription->starts_at->isSameMonth($month) ? $amount : 0.0;
        }

        return ContractSubscription::monthlyAmount($amount, $subscription->billing_frequency);
    }

    private function costAmountForMonth(ContractCostLine $costLine, Carbon $month): float
    {
        if (! $costLine->is_active || ! $this->activeForMonth($costLine->starts_at, $costLine->ends_at, $month)) {
            return 0.0;
        }

        $amount = (float) $costLine->quantity * (float) $costLine->unit_cost;

        if ($costLine->cost_frequency === ContractSubscription::FREQUENCY_ONE_TIME) {
            return $costLine->starts_at && $costLine->starts_at->isSameMonth($month) ? $amount : 0.0;
        }

        return ContractSubscription::monthlyAmount($amount, $costLine->cost_frequency);
    }

    private function activeForMonth($startsAt, $endsAt, Carbon $month): bool
    {
        $monthStart = $month->copy()->startOfMonth();
        $monthEnd = $month->copy()->endOfMonth();

        if ($startsAt && $startsAt->gt($monthEnd)) {
            return false;
        }

        if ($endsAt && $endsAt->lt($monthStart)) {
            return false;
        }

        return true;
    }
}
