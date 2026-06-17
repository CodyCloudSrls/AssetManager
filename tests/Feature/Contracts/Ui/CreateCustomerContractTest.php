<?php

namespace Tests\Feature\Contracts\Ui;

use App\Models\Company;
use App\Models\ContractSubscription;
use App\Models\Customer;
use App\Models\CustomerContract;
use App\Models\CustomerContractEvent;
use App\Models\Supplier;
use App\Models\User;
use Tests\TestCase;

class CreateCustomerContractTest extends TestCase
{
    public function test_can_create_contract_with_dates_subscription_cost_line_and_audit_event(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->superuser()->for($company)->create();
        $this->actingAs($user);

        $customer = new Customer([
            'company_id' => $company->id,
            'name' => 'NIS2 Customer',
        ]);
        $customer->created_by = $user->id;

        $this->assertTrue($customer->save(), $customer->getErrors()->toJson());

        $supplier = Supplier::factory()->create([
            'company_id' => $company->id,
            'created_by' => $user->id,
        ]);

        $this->post(route('contracts.store'), [
            'company_id' => $company->id,
            'customer_id' => $customer->id,
            'name' => 'NIS2 Service Agreement',
            'status' => CustomerContract::STATUS_DRAFT,
            'currency' => 'EUR',
            'starts_at' => '2026-05-26',
            'ends_at' => '2026-05-28',
            'renewal_due_at' => '2026-05-28',
            'notice_due_at' => '2026-05-27',
            'subscriptions' => [
                'new_0' => [
                    'name' => 'Managed service',
                    'quantity' => '1',
                    'unit_price' => '100.00',
                    'billing_frequency' => ContractSubscription::FREQUENCY_MONTHLY,
                    'starts_at' => '2026-05-26',
                    'ends_at' => '2026-05-28',
                    'cost_supplier_id' => $supplier->id,
                    'cost_description' => 'Delivery cost',
                    'cost_quantity' => '1',
                    'unit_cost' => '20.00',
                    'cost_frequency' => ContractSubscription::FREQUENCY_MONTHLY,
                    'cost_starts_at' => '2026-05-26',
                    'cost_ends_at' => '2026-05-28',
                ],
            ],
        ])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('contracts.index'));

        $contract = CustomerContract::withoutGlobalScopes()
            ->where('name', 'NIS2 Service Agreement')
            ->with('subscriptions.costLines')
            ->firstOrFail();

        $subscription = $contract->subscriptions->first();
        $costLine = $subscription->costLines->first();

        $this->assertSame('2026-05-26', $contract->starts_at->format('Y-m-d'));
        $this->assertSame('2026-05-26', $subscription->starts_at->format('Y-m-d'));
        $this->assertSame('2026-05-26', $costLine->starts_at->format('Y-m-d'));

        $this->assertDatabaseHas('customer_contract_events', [
            'customer_contract_id' => $contract->id,
            'event_type' => CustomerContractEvent::EVENT_CREATED,
        ]);
    }

    public function test_subscription_missing_price_returns_a_field_specific_error(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->superuser()->for($company)->create();
        $this->actingAs($user);

        $customer = new Customer(['company_id' => $company->id, 'name' => 'Cust']);
        $customer->created_by = $user->id;
        $customer->save();

        $this->post(route('contracts.store'), [
            'company_id' => $company->id,
            'customer_id' => $customer->id,
            'name' => 'Contract',
            'status' => CustomerContract::STATUS_DRAFT,
            'currency' => 'EUR',
            'subscriptions' => [
                'new_0' => [
                    'name' => 'Managed service',
                    'quantity' => '1',
                    'unit_price' => '', // omitted price
                    'billing_frequency' => ContractSubscription::FREQUENCY_MONTHLY,
                ],
            ],
        ])
            ->assertStatus(302)
            ->assertSessionHasErrors('subscriptions.new_0.unit_price');
    }

    public function test_subscription_accepts_semiannual_frequency(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->superuser()->for($company)->create();
        $this->actingAs($user);

        $customer = new Customer(['company_id' => $company->id, 'name' => 'Cust']);
        $customer->created_by = $user->id;
        $customer->save();

        $this->post(route('contracts.store'), [
            'company_id' => $company->id,
            'customer_id' => $customer->id,
            'name' => 'Contract',
            'status' => CustomerContract::STATUS_DRAFT,
            'currency' => 'EUR',
            'subscriptions' => [
                'new_0' => [
                    'name' => 'Managed service',
                    'quantity' => '1',
                    'unit_price' => '600.00',
                    'billing_frequency' => ContractSubscription::FREQUENCY_SEMIANNUAL,
                ],
            ],
        ])->assertStatus(302)->assertSessionHasNoErrors();

        $subscription = ContractSubscription::where('name', 'Managed service')->firstOrFail();
        $this->assertSame(ContractSubscription::FREQUENCY_SEMIANNUAL, $subscription->billing_frequency);
        // 600 every 6 months => 100/month
        $this->assertEqualsWithDelta(100.0, $subscription->monthly_revenue, 0.001);
    }
}
