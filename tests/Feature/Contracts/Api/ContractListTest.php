<?php

namespace Tests\Feature\Contracts\Api;

use App\Models\Company;
use App\Models\Customer;
use App\Models\CustomerContract;
use App\Models\User;
use Tests\TestCase;

class ContractListTest extends TestCase
{
    private function contract(Company $company, User $creator, string $name, string $status): CustomerContract
    {
        $customer = new Customer([
            'company_id' => $company->id,
            'name' => $name.' Customer',
        ]);
        $customer->created_by = $creator->id;
        $this->assertTrue($customer->save(), $customer->getErrors()->toJson());

        $contract = new CustomerContract([
            'company_id' => $company->id,
            'customer_id' => $customer->id,
            'name' => $name,
            'status' => $status,
            'currency' => 'EUR',
        ]);
        $contract->created_by = $creator->id;
        $this->assertTrue($contract->save(), $contract->getErrors()->toJson());

        return $contract;
    }

    public function test_contract_list_exposes_created_at(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->superuser()->for($company)->create();
        $this->contract($company, $user, 'Created At Contract', CustomerContract::STATUS_DRAFT);

        $this->actingAsForApi($user)
            ->getJson(route('api.contracts.index'))
            ->assertOk()
            ->assertJsonStructure(['total', 'rows' => [['id', 'name', 'created_at']]])
            ->assertJsonPath('rows.0.name', 'Created At Contract');
    }

    public function test_contract_list_can_be_filtered_by_status(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->superuser()->for($company)->create();
        $this->contract($company, $user, 'Draft Contract', CustomerContract::STATUS_DRAFT);
        $this->contract($company, $user, 'Active Contract', CustomerContract::STATUS_ACTIVE);

        $this->actingAsForApi($user)
            ->getJson(route('api.contracts.index', ['status' => CustomerContract::STATUS_ACTIVE]))
            ->assertOk()
            ->assertJsonPath('total', 1)
            ->assertJsonPath('rows.0.name', 'Active Contract');
    }
}
