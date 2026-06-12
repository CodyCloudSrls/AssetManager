<?php

namespace Tests\Feature\TenantServices\Ui;

use App\Models\Company;
use App\Models\Tenant;
use App\Models\TenantService;
use App\Models\User;
use Illuminate\Support\Str;
use Tests\TestCase;

class BulkEditTenantServicesTest extends TestCase
{
    private function tenantWithCompany(): array
    {
        $tenant = Tenant::create(['uuid' => (string) Str::uuid()]);
        $company = Company::factory()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Bulk Services Co '.Str::random(6),
        ]);

        return [$tenant, $company];
    }

    private function service(Tenant $tenant, string $name, bool $active = true): TenantService
    {
        $service = new TenantService([
            'tenant_id' => $tenant->id,
            'macro_area' => TenantService::MACRO_PRODUCTION_DIGITAL_INFRASTRUCTURES,
            'name' => $name,
            'is_active' => $active,
        ]);
        $this->assertTrue($service->save(), $service->getErrors()->toJson());

        return $service;
    }

    public function test_bulk_update_changes_active_and_relevance_on_selected_services(): void
    {
        [$tenant, $company] = $this->tenantWithCompany();
        $user = User::factory()->superuser()->for($company)->create();
        $a = $this->service($tenant, 'Hosting', true);
        $b = $this->service($tenant, 'Backup', true);

        $this->actingAs($user)
            ->post(route('tenants.services.bulkeditsave', $tenant), [
                'ids' => [$a->id, $b->id],
                'apply_is_active' => '1',
                'is_active_state' => '0',
                'apply_relevance_override' => '1',
                'relevance_override' => TenantService::IMPACT_HIGH,
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('tenants.services.index', $tenant));

        foreach ([$a, $b] as $service) {
            $fresh = $service->fresh();
            $this->assertFalse((bool) $fresh->is_active);
            $this->assertSame(TenantService::IMPACT_HIGH, $fresh->relevance_override);
        }
    }

    public function test_nothing_selected_returns_error(): void
    {
        [$tenant, $company] = $this->tenantWithCompany();
        $user = User::factory()->superuser()->for($company)->create();
        $a = $this->service($tenant, 'Hosting', true);

        $this->actingAs($user)
            ->post(route('tenants.services.bulkeditsave', $tenant), [
                'ids' => [$a->id],
                'is_active_state' => '0',
            ])
            ->assertSessionHasErrors('bulk_actions');

        $this->assertTrue((bool) $a->fresh()->is_active);
    }

    public function test_bulk_update_does_not_touch_services_from_another_tenant(): void
    {
        [$tenant, $company] = $this->tenantWithCompany();
        [$otherTenant] = $this->tenantWithCompany();
        $user = User::factory()->superuser()->for($company)->create();
        $foreign = $this->service($otherTenant, 'Foreign', true);

        $this->actingAs($user)
            ->post(route('tenants.services.bulkeditsave', $tenant), [
                'ids' => [$foreign->id],
                'apply_is_active' => '1',
                'is_active_state' => '0',
            ])
            ->assertRedirect(route('tenants.services.index', $tenant))
            ->assertSessionHas('error');

        $this->assertTrue((bool) $foreign->fresh()->is_active);
    }

    public function test_bulk_update_requires_manage_permission(): void
    {
        [$tenant, $company] = $this->tenantWithCompany();
        $owner = User::factory()->superuser()->for($company)->create();
        $service = $this->service($tenant, 'Hosting', true);

        $restricted = User::factory()->for($company)->create([
            'permissions' => json_encode(['tenants.services.view' => '1']),
        ]);

        $this->actingAs($restricted)
            ->post(route('tenants.services.bulkeditsave', $tenant), [
                'ids' => [$service->id],
                'apply_is_active' => '1',
                'is_active_state' => '0',
            ])
            ->assertForbidden();

        $this->assertTrue((bool) $service->fresh()->is_active);
    }
}
