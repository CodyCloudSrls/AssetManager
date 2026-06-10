<?php

namespace Tests\Feature\TenantServices\Ui;

use App\Models\Company;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Str;
use Tests\TestCase;

class TenantServicePermissionsTest extends TestCase
{
    private function tenantWithCompany(): array
    {
        $tenant = Tenant::create(['uuid' => (string) Str::uuid()]);
        $company = Company::factory()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Tenant Services Co '.Str::random(6),
        ]);

        return [$tenant, $company];
    }

    public function test_user_with_manage_permission_can_open_service_create_form(): void
    {
        [$tenant, $company] = $this->tenantWithCompany();

        $user = User::factory()->for($company)->create([
            'permissions' => json_encode(['tenants.services.manage' => '1']),
        ]);

        $this->actingAs($user)
            ->get(route('tenants.services.create', $tenant))
            ->assertOk();
    }

    public function test_user_without_manage_permission_cannot_open_service_create_form(): void
    {
        [$tenant, $company] = $this->tenantWithCompany();

        $user = User::factory()->for($company)->create([
            'permissions' => json_encode(['tenants.services.view' => '1']),
        ]);

        $this->actingAs($user)
            ->get(route('tenants.services.create', $tenant))
            ->assertForbidden();
    }

    public function test_manage_permission_does_not_grant_access_to_unreachable_tenant(): void
    {
        [, $company] = $this->tenantWithCompany();
        [$foreignTenant] = $this->tenantWithCompany();

        $user = User::factory()->for($company)->create([
            'permissions' => json_encode(['tenants.services.manage' => '1']),
        ]);

        // The user can manage services for their own tenant, but the global
        // permission must not let them reach a tenant they cannot otherwise view.
        $this->actingAs($user)
            ->get(route('tenants.services.create', $foreignTenant))
            ->assertForbidden();
    }
}
