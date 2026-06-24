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

    public function test_selectlist_falls_back_to_users_tenant_without_a_company(): void
    {
        // On the asset CREATE form (no company picked yet) the list must still show the
        // user's own tenant services instead of being empty.
        [$tenant, $company] = $this->tenantWithCompany();
        $user = User::factory()->superuser()->for($company)->create();
        $service = $this->service($tenant, 'Hosting Web Start', true);

        $response = $this->actingAsForApi($user)
            ->getJson(route('api.tenantservices.selectlist'))
            ->assertOk();

        $this->assertContains($service->id, collect($response->json('results'))->pluck('id')->all());
    }

    public function test_selectlist_scopes_to_the_company_plus_tenant_wide(): void
    {
        $tenant = Tenant::create(['uuid' => (string) Str::uuid()]);
        $companyA = Company::factory()->create(['tenant_id' => $tenant->id, 'name' => 'Co A '.Str::random(4)]);
        $companyB = Company::factory()->create(['tenant_id' => $tenant->id, 'name' => 'Co B '.Str::random(4)]);
        $user = User::factory()->superuser()->for($companyA)->create();

        $svcA = (new TenantService(['tenant_id' => $tenant->id, 'company_id' => $companyA->id, 'macro_area' => TenantService::MACRO_PRODUCTION_DIGITAL_INFRASTRUCTURES, 'name' => 'Solo A', 'is_active' => true]));
        $svcA->save();
        $svcB = (new TenantService(['tenant_id' => $tenant->id, 'company_id' => $companyB->id, 'macro_area' => TenantService::MACRO_PRODUCTION_DIGITAL_INFRASTRUCTURES, 'name' => 'Solo B', 'is_active' => true]));
        $svcB->save();
        $wide = $this->service($tenant, 'Tenant Wide', true); // company_id null

        $response = $this->actingAsForApi($user)
            ->getJson(route('api.tenantservices.selectlist', ['company_id' => $companyA->id]))
            ->assertOk();

        $ids = collect($response->json('results'))->pluck('id')->all();
        $this->assertContains($svcA->id, $ids, 'company A service shown');
        $this->assertContains($wide->id, $ids, 'tenant-wide service shown');
        $this->assertNotContains($svcB->id, $ids, 'company B service NOT shown for company A');
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
