<?php

namespace Tests\Feature\Users\Ui;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Str;
use Tests\TestCase;

class UserVisibleTenantsTest extends TestCase
{
    public function test_superadmin_assigns_visible_tenants_and_preserves_existing_admin_role(): void
    {
        $tenantA = Tenant::create(['uuid' => (string) Str::uuid()]);
        $tenantB = Tenant::create(['uuid' => (string) Str::uuid()]);
        $tenantC = Tenant::create(['uuid' => (string) Str::uuid()]);
        $user = User::factory()->create();

        // Pre-existing ADMIN membership on tenant A — must be preserved on re-sync.
        $user->tenants()->attach($tenantA->id, ['role' => Tenant::ROLE_ADMIN, 'created_by' => $user->id]);

        $this->actingAs(User::factory()->superuser()->create());

        $this->put(route('users.update', $user), [
            'first_name' => $user->first_name,
            'username' => $user->username,
            'tenants' => [$tenantA->id, $tenantB->id],
        ]);

        $roles = $user->tenants()->get()->pluck('pivot.role', 'id')->all();

        $this->assertEqualsCanonicalizing([$tenantA->id, $tenantB->id], array_keys($roles));
        $this->assertEquals(Tenant::ROLE_ADMIN, $roles[$tenantA->id], 'existing admin role preserved');
        $this->assertEquals(Tenant::ROLE_VIEWER, $roles[$tenantB->id], 'new membership defaults to viewer');
        $this->assertArrayNotHasKey($tenantC->id, $roles, 'unselected tenant not granted');
    }

    public function test_deselecting_removes_visibility(): void
    {
        $tenantA = Tenant::create(['uuid' => (string) Str::uuid()]);
        $tenantB = Tenant::create(['uuid' => (string) Str::uuid()]);
        $user = User::factory()->create();
        $user->tenants()->attach($tenantA->id, ['role' => Tenant::ROLE_VIEWER, 'created_by' => $user->id]);
        $user->tenants()->attach($tenantB->id, ['role' => Tenant::ROLE_VIEWER, 'created_by' => $user->id]);

        $this->actingAs(User::factory()->superuser()->create());

        $this->put(route('users.update', $user), [
            'first_name' => $user->first_name,
            'username' => $user->username,
            'tenants' => [$tenantA->id], // B deselected
        ]);

        $ids = $user->tenants()->pluck('tenants.id')->all();
        $this->assertEqualsCanonicalizing([$tenantA->id], $ids);
    }

    public function test_superadmin_target_visibility_is_not_changed(): void
    {
        $tenant = Tenant::create(['uuid' => (string) Str::uuid()]);
        $superTarget = User::factory()->superuser()->create();

        $this->actingAs(User::factory()->superuser()->create());

        $this->put(route('users.update', $superTarget), [
            'first_name' => $superTarget->first_name,
            'username' => $superTarget->username,
            'tenants' => [$tenant->id],
        ]);

        $this->assertCount(0, $superTarget->tenants()->get(), 'superadmin already sees all tenants — no membership written');
    }
}
