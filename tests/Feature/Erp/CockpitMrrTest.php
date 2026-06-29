<?php

namespace Tests\Feature\Erp;

use App\Models\Company;
use App\Models\ContractSubscription;
use App\Models\Customer;
use App\Models\CustomerContract;
use App\Models\User;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class CockpitMrrTest extends TestCase
{
    public function test_mrr_counts_only_current_recurring_subscriptions(): void
    {
        $company = Company::factory()->create();
        $customer = Customer::create(['company_id' => $company->id, 'name' => 'Cliente', 'status' => 'active']);
        $contract = CustomerContract::create([
            'company_id' => $company->id, 'customer_id' => $customer->id, 'name' => 'Contratto', 'status' => CustomerContract::STATUS_ACTIVE,
        ]);

        // Active monthly 100/month -> counts (100).
        ContractSubscription::create(['company_id' => $company->id, 'customer_contract_id' => $contract->id, 'name' => 'Hosting', 'quantity' => 1, 'unit_price' => 100, 'billing_frequency' => 'monthly', 'is_active' => true]);
        // Ended annual 1200/year -> excluded (already ended).
        ContractSubscription::create(['company_id' => $company->id, 'customer_contract_id' => $contract->id, 'name' => 'Vecchio', 'quantity' => 1, 'unit_price' => 1200, 'billing_frequency' => 'annual', 'is_active' => true, 'ends_at' => Carbon::now()->subDay()]);
        // One-off 5000 -> excluded (not recurring).
        ContractSubscription::create(['company_id' => $company->id, 'customer_contract_id' => $contract->id, 'name' => 'Setup', 'quantity' => 1, 'unit_price' => 5000, 'billing_frequency' => 'one_time', 'is_active' => true]);

        $response = $this->actingAs(User::factory()->superuser()->create())->get(route('erp.index'));

        $response->assertOk();
        $this->assertEqualsWithDelta(100, $response->viewData('kpis')['mrr'], 0.01);
        $this->assertEqualsWithDelta(1200, $response->viewData('kpis')['arr'], 0.01);
    }
}
