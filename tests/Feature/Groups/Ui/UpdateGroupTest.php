<?php

namespace Tests\Feature\Groups\Ui;

use App\Models\Group;
use App\Models\User;
use Tests\TestCase;

class UpdateGroupTest extends TestCase
{
    public function test_page_renders()
    {
        $this->actingAs(User::factory()->superuser()->create())
            ->get(route('groups.edit', Group::factory()->create()->id))
            ->assertOk();
    }

    public function test_user_can_edit_groups()
    {
        $group = Group::factory()->create(['name' => 'Test Group']);
        $this->assertTrue(Group::where('name', 'Test Group')->exists());

        $response = $this->actingAs(User::factory()->superuser()->create())
            ->put(route('groups.update', ['group' => $group]), [
                'name' => 'Test Group Edited',
                'notes' => 'Test Note Edited',
            ])
            ->assertStatus(302)
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('groups.index'));

        $this->followRedirects($response)->assertSee('Success');
        $this->assertTrue(Group::where('name', 'Test Group Edited')->where('notes', 'Test Note Edited')->exists());
    }

    public function test_user_can_edit_group_permissions_and_invalid_permissions_are_dropped()
    {
        $group = Group::factory()->create(['name' => 'Permission Test Group']);

        $this->actingAs(User::factory()->superuser()->create())
            ->put(route('groups.update', ['group' => $group]), [
                'name' => 'Permission Test Group Edited',
                'notes' => 'Permission test note',
                'permission' => [
                    'admin' => '1',
                    'reports.view' => '0',
                    'invalid.permission' => '1',
                ],
            ])
            ->assertStatus(302)
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('groups.index'));

        $decoded = (array) $group->refresh()->decodePermissions();

        $this->assertArrayHasKey('admin', $decoded);
        $this->assertArrayHasKey('reports.view', $decoded);
        $this->assertArrayNotHasKey('invalid.permission', $decoded);
    }

    public function test_permissions_are_preserved_if_permission_payload_not_sent()
    {
        $group = Group::factory()->create([
            'permissions' => json_encode(['admin' => '1']),
        ]);

        $this->actingAs(User::factory()->superuser()->create())
            ->put(route('groups.update', ['group' => $group]), [
                'name' => 'Group Name Only',
                'notes' => 'Updated notes',
            ])
            ->assertStatus(302)
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('groups.index'));

        $decoded = (array) $group->refresh()->decodePermissions();

        $this->assertSame('Group Name Only', $group->name);
        $this->assertSame('1', (string) ($decoded['admin'] ?? null));
    }

    public function test_user_can_edit_system_group_definition()
    {
        $group = Group::factory()->create([
            'name' => 'System Group',
            'notes' => 'Original notes',
            'permissions' => json_encode(['reports.view' => '1']),
            'system_key' => 'default_system_group',
        ]);

        $this->actingAs(User::factory()->superuser()->create())
            ->put(route('groups.update', ['group' => $group]), [
                'name' => 'Edited System Group',
                'notes' => 'Edited notes',
                'permission' => [
                    'admin' => '1',
                ],
            ])
            ->assertStatus(302)
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('groups.index'));

        $group->refresh();
        $decoded = (array) $group->decodePermissions();

        $this->assertSame('Edited System Group', $group->name);
        $this->assertSame('Edited notes', $group->notes);
        $this->assertSame('1', (string) ($decoded['admin'] ?? null));
    }

    public function test_user_can_sync_users_to_system_group_without_editing_definition()
    {
        $originalUser = User::factory()->create();
        $syncedUser = User::factory()->create();
        $group = Group::factory()->create([
            'name' => 'System Group',
            'notes' => 'Original notes',
            'permissions' => json_encode(['reports.view' => '1']),
            'system_key' => 'default_system_group',
        ]);
        $group->users()->attach($originalUser);

        $this->actingAs(User::factory()->superuser()->create())
            ->put(route('groups.update', ['group' => $group]), [
                'users_to_sync' => (string) $syncedUser->id,
            ])
            ->assertStatus(302)
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('groups.index'));

        $group->refresh();

        $this->assertSame('System Group', $group->name);
        $this->assertSame('Original notes', $group->notes);
        $this->assertEqualsCanonicalizing([$syncedUser->id], $group->users()->pluck('users.id')->all());
    }

    public function test_non_superadmin_cannot_edit_groups()
    {
        $group = Group::factory()->create(['name' => 'Tenant Group']);

        $this->actingAs(User::factory()->tenantSuperuser()->create())
            ->put(route('groups.update', ['group' => $group]), [
                'name' => 'Changed By Tenant Superuser',
            ])
            ->assertForbidden();

        $this->assertSame('Tenant Group', $group->refresh()->name);
    }
}
