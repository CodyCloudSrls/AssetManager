<?php

namespace Tests\Feature\TenantServices\Api;

use App\Models\Company;
use App\Models\Tenant;
use App\Models\TenantService;
use App\Models\User;
use Illuminate\Support\Str;
use Tests\TestCase;

class TenantServiceSelectlistTest extends TestCase
{
    private function tenantWithCompany(): array
    {
        $tenant = Tenant::create(['uuid' => (string) Str::uuid()]);
        $company = Company::factory()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Selectlist Co '.Str::random(6),
        ]);

        return [$tenant, $company];
    }

    private function service(Tenant $tenant, string $name, bool $active = true): TenantService
    {
        $service = new TenantService([
            'tenant_id' => $tenant->id,
            'macro_area' => TenantService::MACRO_PRODUCTION_DIGITAL_INFRASTRUCTURES,
            'name' => $name,
            'description' => 'desc',
            'is_active' => $active,
        ]);
        $this->assertTrue($service->save(), $service->getErrors()->toJson());

        return $service;
    }

    public function test_selectlist_returns_only_active_services_for_the_company(): void
    {
        [$tenant, $company] = $this->tenantWithCompany();
        $user = User::factory()->superuser()->for($company)->create();

        $active1 = $this->service($tenant, 'Hosting Web Start', true);
        $active2 = $this->service($tenant, 'Backup Service', true);
        $inactive = $this->service($tenant, 'Legacy Service', false);

        $response = $this->actingAsForApi($user)
            ->getJson(route('api.tenantservices.selectlist', ['company_id' => $company->id]))
            ->assertOk()
            ->assertJsonStructure(['results' => [['id', 'text']], 'pagination' => ['more']]);

        $ids = collect($response->json('results'))->pluck('id')->all();
        $this->assertContains($active1->id, $ids);
        $this->assertContains($active2->id, $ids);
        $this->assertNotContains($inactive->id, $ids);
    }

    public function test_selectlist_is_empty_without_a_company(): void
    {
        [$tenant, $company] = $this->tenantWithCompany();
        $user = User::factory()->superuser()->for($company)->create();
        $this->service($tenant, 'Hosting Web Start', true);

        $this->actingAsForApi($user)
            ->getJson(route('api.tenantservices.selectlist'))
            ->assertOk()
            ->assertJsonPath('results', []);
    }

    public function test_selectlist_filters_by_search(): void
    {
        [$tenant, $company] = $this->tenantWithCompany();
        $user = User::factory()->superuser()->for($company)->create();
        $this->service($tenant, 'Hosting Web Start', true);
        $this->service($tenant, 'Backup Service', true);

        $response = $this->actingAsForApi($user)
            ->getJson(route('api.tenantservices.selectlist', ['company_id' => $company->id, 'search' => 'Hosting']))
            ->assertOk();

        $texts = collect($response->json('results'))->pluck('text')->implode('|');
        $this->assertStringContainsString('Hosting Web Start', $texts);
        $this->assertStringNotContainsString('Backup Service', $texts);
    }

    public function test_selectlist_does_not_leak_services_from_an_unreachable_tenant(): void
    {
        [, $companyA] = $this->tenantWithCompany();
        [$tenantB, $companyB] = $this->tenantWithCompany();
        $this->service($tenantB, 'Foreign Tenant Service', true);

        // Regular user (not superuser) confined to company A, but holding a
        // permission that satisfies the view.selectlists gate.
        $user = User::factory()->for($companyA)->create([
            'permissions' => json_encode(['documents.create' => '1']),
        ]);

        $this->actingAsForApi($user)
            ->getJson(route('api.tenantservices.selectlist', ['company_id' => $companyB->id]))
            ->assertOk()
            ->assertJsonPath('results', []);
    }
}
