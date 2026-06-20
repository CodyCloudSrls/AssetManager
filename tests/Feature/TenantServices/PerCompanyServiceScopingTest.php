<?php

namespace Tests\Feature\TenantServices;

use App\Models\Company;
use App\Models\Tenant;
use App\Models\TenantService;
use Illuminate\Support\Str;
use Tests\TestCase;

class PerCompanyServiceScopingTest extends TestCase
{
    private function service(int $tenantId, ?int $companyId, string $name): TenantService
    {
        $service = new TenantService([
            'tenant_id' => $tenantId,
            'company_id' => $companyId,
            'macro_area' => TenantService::MACRO_PRODUCTION_DIGITAL_INFRASTRUCTURES,
            'name' => $name,
            'is_active' => true,
        ]);
        $this->assertTrue($service->save(), $service->getErrors()->toJson());

        return $service;
    }

    public function test_company_sees_its_own_and_tenant_wide_services_only(): void
    {
        $tenant = Tenant::create(['uuid' => (string) Str::uuid()]);
        $companyA = Company::factory()->create(['tenant_id' => $tenant->id, 'name' => 'Suez Italy']);
        $companyB = Company::factory()->create(['tenant_id' => $tenant->id, 'name' => 'Ecosistem']);

        $wide = $this->service($tenant->id, null, 'Servizio comune');
        $onlyA = $this->service($tenant->id, $companyA->id, 'Solo Italy');
        $onlyB = $this->service($tenant->id, $companyB->id, 'Solo Ecosistem');

        $forA = TenantService::activeForCompanyId($companyA->id)->pluck('id')->all();
        $this->assertContains($wide->id, $forA);
        $this->assertContains($onlyA->id, $forA);
        $this->assertNotContains($onlyB->id, $forA);

        $forB = TenantService::activeForCompanyId($companyB->id)->pluck('id')->all();
        $this->assertContains($wide->id, $forB);
        $this->assertContains($onlyB->id, $forB);
        $this->assertNotContains($onlyA->id, $forB);
    }

    public function test_valid_ids_for_company_rejects_other_company_services(): void
    {
        $tenant = Tenant::create(['uuid' => (string) Str::uuid()]);
        $companyA = Company::factory()->create(['tenant_id' => $tenant->id]);
        $companyB = Company::factory()->create(['tenant_id' => $tenant->id]);

        $onlyA = $this->service($tenant->id, $companyA->id, 'Solo A');
        $onlyB = $this->service($tenant->id, $companyB->id, 'Solo B');
        $wide = $this->service($tenant->id, null, 'Comune');

        $valid = TenantService::validIdsForCompany([$onlyA->id, $onlyB->id, $wide->id], $companyA->id);
        $this->assertContains($onlyA->id, $valid);
        $this->assertContains($wide->id, $valid);
        $this->assertNotContains($onlyB->id, $valid);
    }
}
