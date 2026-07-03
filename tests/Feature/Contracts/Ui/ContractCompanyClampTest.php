<?php

namespace Tests\Feature\Contracts\Ui;

use App\Models\Company;
use App\Models\Customer;
use App\Models\CustomerContract;
use App\Models\User;
use Tests\TestCase;

/**
 * A contract's company_id is filled from the request, and create/update authorize on the
 * CustomerContract CLASS (not the instance), so without a clamp a user holding the global
 * contracts ability could forge company_id and create — or move — a contract (with its
 * subscriptions and cost lines) into another tenant's company. store()/update() clamp it
 * via Company::getIdForCurrentUser before validation.
 */
class ContractCompanyClampTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        // The clamp only bites when company scoping is active — which it is in production
        // (full_multiple_companies_support = 1). Tests default it off, so enable it here.
        $this->settings->enableMultipleFullCompanySupport();
    }

    private function editorForCompany(int $companyId): User
    {
        return User::factory()->create([
            'company_id' => $companyId,
            'permissions' => json_encode(['contracts.view' => '1', 'contracts.create' => '1', 'contracts.edit' => '1']),
        ]);
    }

    public function test_forged_company_id_is_clamped_to_the_callers_company_on_store(): void
    {
        $companyA = Company::factory()->create();
        $companyB = Company::factory()->create();
        $editorA = $this->editorForCompany($companyA->id);

        $customerA = new Customer(['company_id' => $companyA->id, 'name' => 'Cliente A']);
        $this->assertTrue($customerA->save(), $customerA->getErrors()->toJson());

        $this->actingAs($editorA)->post(route('contracts.store'), [
            'company_id' => $companyB->id,     // forged — belongs to another tenant
            'customer_id' => $customerA->id,
            'name' => 'Contratto clamp',
            'status' => CustomerContract::STATUS_DRAFT,
            'currency' => 'EUR',
        ])->assertRedirect(route('contracts.index'));

        $contract = CustomerContract::where('name', 'Contratto clamp')->firstOrFail();
        $this->assertSame($companyA->id, (int) $contract->company_id);   // clamped to A, not B
    }

    public function test_update_cannot_move_a_contract_into_another_tenant(): void
    {
        $companyA = Company::factory()->create();
        $companyB = Company::factory()->create();
        $editorA = $this->editorForCompany($companyA->id);

        $customerA = new Customer(['company_id' => $companyA->id, 'name' => 'Cliente A2']);
        $this->assertTrue($customerA->save(), $customerA->getErrors()->toJson());

        $contract = CustomerContract::create([
            'company_id' => $companyA->id, 'customer_id' => $customerA->id, 'created_by' => $editorA->id,
            'name' => 'Contratto da spostare', 'status' => CustomerContract::STATUS_DRAFT, 'currency' => 'EUR',
        ]);

        $this->actingAs($editorA)->put(route('contracts.update', $contract), [
            'company_id' => $companyB->id,     // attempt to move to B
            'customer_id' => $customerA->id,
            'name' => 'Contratto da spostare',
            'status' => CustomerContract::STATUS_DRAFT,
            'currency' => 'EUR',
        ])->assertRedirect();

        $this->assertSame($companyA->id, (int) $contract->fresh()->company_id);  // stays in A
    }
}
