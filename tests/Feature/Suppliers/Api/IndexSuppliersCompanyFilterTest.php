<?php

namespace Tests\Feature\Suppliers\Api;

use App\Models\Company;
use App\Models\Supplier;
use App\Models\User;
use Tests\TestCase;

class IndexSuppliersCompanyFilterTest extends TestCase
{
    public function test_company_id_filter_returns_only_that_companys_suppliers(): void
    {
        $companyA = Company::factory()->create(['name' => 'Suez Italy']);
        $companyB = Company::factory()->create(['name' => 'Ecosistem']);

        $supplierA = Supplier::factory()->create(['company_id' => $companyA->id, 'name' => 'Fornitore A']);
        $supplierB = Supplier::factory()->create(['company_id' => $companyB->id, 'name' => 'Fornitore B']);

        $response = $this->actingAsForApi(User::factory()->superuser()->create())
            ->getJson(route('api.suppliers.index', ['company_id' => $companyA->id]))
            ->assertOk();

        $ids = collect($response->json('rows'))->pluck('id')->all();

        $this->assertContains($supplierA->id, $ids);
        $this->assertNotContains($supplierB->id, $ids);
    }
}
