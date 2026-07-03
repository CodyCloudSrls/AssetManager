<?php

namespace Tests\Feature\Customers;

use App\Models\Company;
use App\Models\Customer;
use App\Models\User;
use Tests\TestCase;

/**
 * fillCustomer() must never persist a request-supplied company_id verbatim: create/update
 * authorize on the Customer CLASS, so a user with the global customers ability could forge
 * company_id and inject/move a customer into another tenant. It is clamped via
 * Company::getIdForCurrentUser to a company the caller may actually manage.
 */
class CustomerCompanyClampTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        // The clamp only bites when company scoping is active — which it is in production
        // (full_multiple_companies_support = 1). Tests default it off, so enable it here.
        $this->settings->enableMultipleFullCompanySupport();
    }

    public function test_forged_company_id_is_clamped_to_the_callers_company_on_store(): void
    {
        $companyA = Company::factory()->create();
        $companyB = Company::factory()->create();
        $editorA = User::factory()->create([
            'company_id' => $companyA->id,
            'permissions' => json_encode(['customers.view' => '1', 'customers.create' => '1', 'customers.edit' => '1']),
        ]);

        $this->actingAs($editorA)->post(route('customers.store'), [
            'company_id' => $companyB->id,     // forged — another tenant's company
            'name' => 'Cliente Clamp',
            'status' => 'active',
        ])->assertRedirect(route('customers.index'));

        $customer = Customer::withoutGlobalScopes()->where('name', 'Cliente Clamp')->firstOrFail();
        $this->assertSame($companyA->id, (int) $customer->company_id);   // A, never B
    }

    public function test_update_cannot_move_a_customer_into_another_tenant(): void
    {
        $companyA = Company::factory()->create();
        $companyB = Company::factory()->create();
        $editorA = User::factory()->create([
            'company_id' => $companyA->id,
            'permissions' => json_encode(['customers.view' => '1', 'customers.create' => '1', 'customers.edit' => '1']),
        ]);

        $customer = new Customer(['company_id' => $companyA->id, 'name' => 'Cliente A muovibile']);
        $this->assertTrue($customer->save(), $customer->getErrors()->toJson());

        $this->actingAs($editorA)->put(route('customers.update', $customer), [
            'company_id' => $companyB->id,     // attempt to move to B
            'name' => 'Cliente A muovibile',
            'status' => 'active',
        ])->assertRedirect(route('customers.index'));

        $this->assertSame($companyA->id, (int) $customer->fresh()->company_id);  // stays in A
    }
}
