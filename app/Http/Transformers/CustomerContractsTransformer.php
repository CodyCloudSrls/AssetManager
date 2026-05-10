<?php

namespace App\Http\Transformers;

use App\Helpers\Helper;
use App\Models\CustomerContract;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Gate;

class CustomerContractsTransformer
{
    public function transformContracts(Collection $contracts, int $total): array
    {
        $array = [];

        foreach ($contracts as $contract) {
            $array[] = $this->transformContract($contract);
        }

        return (new DatatablesTransformer)->transformDatatables($array, $total);
    }

    public function transformContract(CustomerContract $contract): array
    {
        $monthlyRevenue = $contract->subscriptions->sum(fn ($subscription) => (float) $subscription->monthly_revenue);
        $monthlyCost = $contract->subscriptions->sum(fn ($subscription) => $subscription->costLines->sum(fn ($costLine) => (float) $costLine->monthly_cost));

        return [
            'id' => (int) $contract->id,
            'name' => e($contract->name),
            'contract_number' => e($contract->contract_number),
            'company' => $contract->company ? [
                'id' => (int) $contract->company->id,
                'name' => e($contract->company->name),
            ] : null,
            'customer' => $contract->customer ? [
                'id' => (int) $contract->customer->id,
                'name' => e($contract->customer->name),
            ] : null,
            'document' => $contract->document ? [
                'id' => (int) $contract->document->id,
                'name' => e($contract->document->name),
            ] : null,
            'status' => e($contract->status),
            'status_label' => e($contract->status_label),
            'currency' => e($contract->currency),
            'monthly_revenue' => Helper::formatCurrencyOutput($monthlyRevenue),
            'monthly_cost' => Helper::formatCurrencyOutput($monthlyCost),
            'monthly_net' => Helper::formatCurrencyOutput($monthlyRevenue - $monthlyCost),
            'signed_at' => Helper::getFormattedDateObject($contract->signed_at, 'date'),
            'starts_at' => Helper::getFormattedDateObject($contract->starts_at, 'date'),
            'ends_at' => Helper::getFormattedDateObject($contract->ends_at, 'date'),
            'renewal_due_at' => Helper::getFormattedDateObject($contract->renewal_due_at, 'date'),
            'notice_due_at' => Helper::getFormattedDateObject($contract->notice_due_at, 'date'),
            'available_actions' => [
                'update' => Gate::allows('update', $contract),
                'delete' => Gate::allows('delete', $contract) && $contract->isDeletable(),
            ],
        ];
    }
}
