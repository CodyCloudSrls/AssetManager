<?php

namespace Tests\Feature\Tenants;

use App\Models\Company;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Str;
use Tests\TestCase;

class CrossTenantMiddleGroundTest extends TestCase
{
    private function tenantWithCompany(): array
    {
        $tenant = Tenant::create(['uuid' => (string) Str::uuid()]);
        $company = Company::factory()->create(['tenant_id' => $tenant->id, 'name' => 'Co '.Str::random(5)]);

        return [$tenant, $company];
    }

    public function test_user_assigned_to_some_tenants_sees_own_plus_assigned_not_all(): void
    {
        [$tenantA, $companyA] = $this->tenantWithCompany(); // the user's own tenant
        [$tenantB] = $this->tenantWithCompany();            // explicitly assigned
        [$tenantC] = $this->tenantWithCompany();            // NOT assigned

        // A normal (non-superuser) user belonging to company A
        $user = User::factory()->create(['company_id' => $companyA->id]);

        // Assign the user as a cross-tenant member of tenant B only
        $tenantB->members()->syncWithoutDetaching([
            $user->id => ['role' => Tenant::ROLE_VIEWER, 'created_by' => $user->id],
        ]);

        $this->actingAs($user);
        Tenant::clearCurrentUserTenantRoleCache();

        $accessible = Tenant::accessibleTenantIdsForCurrentUser();

        $this->assertContains((int) $tenantA->id, $accessible, 'sees its own tenant');
        $this->assertContains((int) $tenantB->id, $accessible, 'sees the assigned tenant (middle ground)');
        $this->assertNotContains((int) $tenantC->id, $accessible, 'does NOT see unassigned tenants');
    }
}
