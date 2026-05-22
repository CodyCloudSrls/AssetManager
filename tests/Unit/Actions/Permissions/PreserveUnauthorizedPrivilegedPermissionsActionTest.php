<?php

namespace Tests\Unit\Actions\Permissions;

use App\Actions\Permissions\PreserveUnauthorizedPrivilegedPermissionsAction;
use App\Models\User;
use Illuminate\Support\Collection;
use PHPUnit\Framework\TestCase;

class PreserveUnauthorizedPrivilegedPermissionsActionTest extends TestCase
{
    public function test_superadmin_can_modify_privileged_keys(): void
    {
        $actor = $this->actor(['superadmin' => '1', 'superuser' => '1', 'tenants.view_all' => '1']);

        $normalized = PreserveUnauthorizedPrivilegedPermissionsAction::run(
            requestedPermissions: ['admin' => '0', 'superadmin' => '0', 'superuser' => '0', 'tenants.view_all' => '0', 'users.view' => '1'],
            authenticatedUser: $actor,
            originalPermissions: ['admin' => '1', 'superadmin' => '1', 'superuser' => '1', 'tenants.view_all' => '1']
        );

        $this->assertSame('0', (string) $normalized['admin']);
        $this->assertSame('0', (string) $normalized['superadmin']);
        $this->assertSame('0', (string) $normalized['superuser']);
        $this->assertSame('0', (string) $normalized['tenants.view_all']);
        $this->assertSame('1', (string) $normalized['users.view']);
    }

    public function test_tenant_superuser_cannot_modify_platform_keys(): void
    {
        $actor = $this->actor(['superuser' => '1']);

        $normalized = PreserveUnauthorizedPrivilegedPermissionsAction::run(
            requestedPermissions: ['superadmin' => '1', 'superuser' => '0', 'tenants.view_all' => '1', 'users.view' => '1'],
            authenticatedUser: $actor,
            originalPermissions: ['superadmin' => '0', 'superuser' => '1', 'tenants.view_all' => '0']
        );

        $this->assertSame('0', (string) $normalized['superadmin']);
        $this->assertSame('0', (string) $normalized['superuser']);
        $this->assertSame('0', (string) $normalized['tenants.view_all']);
        $this->assertSame('1', (string) $normalized['users.view']);
    }

    public function test_admin_cannot_change_existing_superuser_key(): void
    {
        $actor = $this->actor(['admin' => '1']);

        $normalized = PreserveUnauthorizedPrivilegedPermissionsAction::run(
            requestedPermissions: ['admin' => '0', 'superuser' => '0', 'users.view' => '1'],
            authenticatedUser: $actor,
            originalPermissions: ['admin' => '1', 'superuser' => '1']
        );

        $this->assertSame('0', (string) $normalized['admin']);
        $this->assertSame('1', (string) $normalized['superuser']);
        $this->assertSame('1', (string) $normalized['users.view']);
    }

    public function test_admin_cannot_add_superuser_key_when_original_is_missing(): void
    {
        $actor = $this->actor(['admin' => '1']);

        $normalized = PreserveUnauthorizedPrivilegedPermissionsAction::run(
            requestedPermissions: ['admin' => '1', 'superuser' => '1', 'users.view' => '1'],
            authenticatedUser: $actor,
            originalPermissions: ['admin' => '0']
        );

        $this->assertArrayNotHasKey('superuser', $normalized);
        $this->assertSame('1', (string) $normalized['admin']);
        $this->assertSame('1', (string) $normalized['users.view']);
    }

    public function test_non_admin_cannot_change_existing_admin_or_superuser_keys(): void
    {
        $actor = $this->actor(['users.edit' => '1']);

        $normalized = PreserveUnauthorizedPrivilegedPermissionsAction::run(
            requestedPermissions: ['admin' => '1', 'superuser' => '1', 'users.view' => '1'],
            authenticatedUser: $actor,
            originalPermissions: ['admin' => '0', 'superuser' => '0']
        );

        $this->assertSame('0', (string) $normalized['admin']);
        $this->assertSame('0', (string) $normalized['superuser']);
        $this->assertSame('1', (string) $normalized['users.view']);
    }

    public function test_non_admin_cannot_add_missing_admin_or_superuser_keys(): void
    {
        $actor = $this->actor(['users.edit' => '1']);

        $normalized = PreserveUnauthorizedPrivilegedPermissionsAction::run(
            requestedPermissions: ['admin' => '1', 'superuser' => '1', 'users.view' => '1'],
            authenticatedUser: $actor,
            originalPermissions: []
        );

        $this->assertArrayNotHasKey('admin', $normalized);
        $this->assertArrayNotHasKey('superuser', $normalized);
        $this->assertSame('1', (string) $normalized['users.view']);
    }

    private function actor(array $permissions): User
    {
        $user = new User;
        $user->permissions = json_encode($permissions);
        $user->setRelation('groups', new Collection);

        return $user;
    }
}
