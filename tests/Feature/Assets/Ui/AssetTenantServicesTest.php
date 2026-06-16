<?php

namespace Tests\Feature\Assets\Ui;

use App\Models\Asset;
use App\Models\Company;
use App\Models\Tenant;
use App\Models\TenantService;
use App\Models\User;
use Illuminate\Support\Str;
use Tests\TestCase;

class AssetTenantServicesTest extends TestCase
{
    private function tenantWithCompany(): array
    {
        $tenant = Tenant::create(['uuid' => (string) Str::uuid()]);
        $company = Company::factory()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Svc Co '.Str::random(6),
        ]);

        return [$tenant, $company];
    }

    private function service(Tenant $tenant, string $name): TenantService
    {
        $service = new TenantService([
            'tenant_id' => $tenant->id,
            'macro_area' => TenantService::MACRO_PRODUCTION_DIGITAL_INFRASTRUCTURES,
            'name' => $name,
            'is_active' => true,
        ]);
        $this->assertTrue($service->save(), $service->getErrors()->toJson());

        return $service;
    }

    public function test_asset_update_links_tenant_services_for_its_company(): void
    {
        [$tenant, $company] = $this->tenantWithCompany();
        $service = $this->service($tenant, 'Hosting');
        $asset = Asset::factory()->create(['company_id' => $company->id]);

        $this->actingAs(User::factory()->viewAssets()->editAssets()->create())
            ->put(route('hardware.update', $asset), [
                'redirect_option' => 'item',
                'name' => $asset->name,
                'asset_tags' => $asset->asset_tag,
                'status_id' => $asset->status_id,
                'model_id' => $asset->model_id,
                'company_id' => $company->id,
                'tenant_service_ids_present' => '1',
                'tenant_service_ids' => [$service->id],
            ])
            ->assertStatus(302);

        $this->assertTrue($asset->fresh()->tenantServices->pluck('id')->contains($service->id));
    }

    public function test_asset_update_rejects_tenant_services_from_a_different_tenant(): void
    {
        [$tenant, $company] = $this->tenantWithCompany();
        [$otherTenant] = $this->tenantWithCompany();
        $foreignService = $this->service($otherTenant, 'Foreign service');
        $asset = Asset::factory()->create(['company_id' => $company->id]);

        $this->actingAs(User::factory()->viewAssets()->editAssets()->create())
            ->put(route('hardware.update', $asset), [
                'redirect_option' => 'item',
                'name' => $asset->name,
                'asset_tags' => $asset->asset_tag,
                'status_id' => $asset->status_id,
                'model_id' => $asset->model_id,
                'company_id' => $company->id,
                'tenant_service_ids_present' => '1',
                'tenant_service_ids' => [$foreignService->id],
            ])
            ->assertStatus(302);

        $this->assertCount(0, $asset->fresh()->tenantServices);
    }
}
