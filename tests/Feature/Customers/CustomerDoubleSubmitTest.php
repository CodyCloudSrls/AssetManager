<?php

namespace Tests\Feature\Customers;

use App\Models\Company;
use App\Models\Customer;
use App\Models\User;
use Tests\TestCase;

/**
 * Clicking "Salva" twice used to create the customer on the first request and then bounce the
 * second with a confusing "cliente omonimo" (name already taken) error. The one-time _submit_nonce
 * makes the duplicate a clean no-op, while a GENUINE same-name attempt (a different page/nonce)
 * still gets the validation error — that protection must not weaken.
 */
class CustomerDoubleSubmitTest extends TestCase
{
    private function payload(Company $company, array $extra = []): array
    {
        return array_merge([
            'company_id' => $company->id,
            'name' => 'ACME Srl',
            'status' => 'active',
        ], $extra);
    }

    public function test_double_click_creates_one_customer_without_the_omonimo_error(): void
    {
        $this->actingAs(User::factory()->superuser()->create());
        $company = Company::factory()->create();

        $this->post(route('customers.store'), $this->payload($company, ['_submit_nonce' => 'cust-1']))
            ->assertRedirect(route('customers.index'))
            ->assertSessionHasNoErrors();

        // Same nonce (same page) -> ignored, and crucially NOT the "name already taken" bounce.
        $this->post(route('customers.store'), $this->payload($company, ['_submit_nonce' => 'cust-1']))
            ->assertRedirect(route('customers.index'))
            ->assertSessionHasNoErrors();

        $this->assertSame(1, Customer::withoutGlobalScopes()->where('name', 'ACME Srl')->count());
    }

    public function test_a_genuine_duplicate_name_still_fails_validation(): void
    {
        $this->actingAs(User::factory()->superuser()->create());
        $company = Company::factory()->create();

        // First creation with its own nonce.
        $this->post(route('customers.store'), $this->payload($company, ['_submit_nonce' => 'real-1']))
            ->assertRedirect(route('customers.index'))
            ->assertSessionHasNoErrors();

        // A genuinely separate attempt (fresh page => different nonce) at the SAME name must still
        // be rejected by the unique rule — the nonce guard must not have masked that.
        $this->post(route('customers.store'), $this->payload($company, ['_submit_nonce' => 'real-2']))
            ->assertSessionHasErrors('name');

        $this->assertSame(1, Customer::withoutGlobalScopes()->where('name', 'ACME Srl')->count());
    }
}
