<?php

namespace Tests\Feature\Contracts\Ui;

use App\Models\Company;
use App\Models\User;
use Tests\TestCase;

class ContractFormTest extends TestCase
{
    public function test_create_form_uses_ajax_tenant_services_and_subscription_datepickers(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->superuser()->for($company)->create();

        $response = $this->actingAs($user)
            ->get(route('contracts.create'))
            ->assertOk();

        // Tenant services field loads via the AJAX selectlist (fixes the empty
        // dropdown on creation) instead of a static server-rendered <select>.
        $response->assertSee('data-endpoint="tenantservices"', false);
        $response->assertSee('id="tenant_service_ids"', false);

        // Subscription date fields use the datepicker component (calendar + manual,
        // yyyy-mm-dd) like the contract-level dates.
        $response->assertSee('data-provide="datepicker"', false);
        $response->assertSee('subscriptions[new_1][starts_at]', false);
    }
}
