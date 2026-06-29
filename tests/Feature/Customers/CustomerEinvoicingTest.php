<?php

namespace Tests\Feature\Customers;

use App\Models\Company;
use App\Models\Customer;
use App\Models\User;
use Tests\TestCase;

class CustomerEinvoicingTest extends TestCase
{
    public function test_customer_persists_sdi_uppercased_and_pec(): void
    {
        $this->actingAs(User::factory()->superuser()->create());
        $company = Company::factory()->create();

        $this->post(route('customers.store'), [
            'company_id' => $company->id,
            'name' => 'Cliente IT Srl',
            'status' => 'active',
            'sdi_code' => 'abc1234',
            'pec' => 'cliente@pec.it',
        ]);

        $customer = Customer::where('name', 'Cliente IT Srl')->firstOrFail();
        $this->assertEquals('ABC1234', $customer->sdi_code);
        $this->assertEquals('cliente@pec.it', $customer->pec);
    }

    public function test_invalid_sdi_code_is_rejected(): void
    {
        $this->actingAs(User::factory()->superuser()->create());
        $company = Company::factory()->create();

        $this->from(route('customers.create'))
            ->post(route('customers.store'), [
                'company_id' => $company->id,
                'name' => 'Bad SDI Srl',
                'status' => 'active',
                'sdi_code' => 'TOOLONG8', // 8 chars -> fails size:7
            ])
            ->assertSessionHasErrors('sdi_code');

        $this->assertDatabaseMissing('customers', ['name' => 'Bad SDI Srl']);
    }
}
