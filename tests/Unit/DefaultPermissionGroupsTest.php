<?php

namespace Tests\Unit;

use App\Support\DefaultPermissionGroups;
use Illuminate\Foundation\Application;
use PHPUnit\Framework\TestCase;
use Tests\CreatesApplication;

class DefaultPermissionGroupsTest extends TestCase
{
    use CreatesApplication;

    private Application $app;

    protected function setUp(): void
    {
        parent::setUp();

        $this->app = $this->createApplication();
    }

    protected function tearDown(): void
    {
        $this->app->flush();

        parent::tearDown();
    }

    public function test_components_permissions_are_registered_in_catalog()
    {
        $catalogPermissions = collect(config('permissions'))
            ->flatten(1)
            ->pluck('permission')
            ->all();

        $this->assertEqualsCanonicalizing(
            [
                'components.view',
                'components.create',
                'components.edit',
                'components.delete',
                'components.checkout',
                'components.checkin',
                'components.files',
            ],
            array_values(array_intersect($catalogPermissions, [
                'components.view',
                'components.create',
                'components.edit',
                'components.delete',
                'components.checkout',
                'components.checkin',
                'components.files',
            ]))
        );
    }

    public function test_default_groups_keep_component_grants()
    {
        $groups = collect(DefaultPermissionGroups::definitions())->keyBy('system_key');

        $inventoryOperatorPermissions = $groups['default_inventory_operator']['permissions'];
        $assetManagerPermissions = $groups['default_asset_manager']['permissions'];

        $this->assertSame(1, $inventoryOperatorPermissions['components.view']);
        $this->assertSame(1, $inventoryOperatorPermissions['components.checkout']);
        $this->assertSame(1, $inventoryOperatorPermissions['components.checkin']);

        foreach ([
            'components.view',
            'components.create',
            'components.edit',
            'components.delete',
            'components.checkout',
            'components.checkin',
            'components.files',
        ] as $permission) {
            $this->assertSame(1, $assetManagerPermissions[$permission]);
        }
    }

    public function test_document_updater_groups_do_not_receive_governance_or_force_delete_permissions()
    {
        $groups = collect(DefaultPermissionGroups::definitions())->keyBy('system_key');

        foreach ([
            'default_administration_document_updater',
            'default_it_document_updater',
            'default_cybersecurity_document_updater',
            'default_compliance_evidence_coordinator',
        ] as $systemKey) {
            $permissions = $groups[$systemKey]['permissions'];

            foreach ([
                'documenttypes.edit',
                'documenttypes.delete',
                'documentframeworks.edit',
                'documentframeworks.delete',
                'documents.force_delete',
            ] as $permission) {
                $this->assertNotSame(1, $permissions[$permission] ?? 0, "{$systemKey} should not grant {$permission}");
            }
        }
    }

    public function test_document_controller_keeps_governance_permissions_explicit()
    {
        $groups = collect(DefaultPermissionGroups::definitions())->keyBy('system_key');
        $permissions = $groups['default_document_controller']['permissions'];

        foreach ([
            'documents.requirements.map',
            'documents.force_delete',
            'documenttypes.edit',
            'documentframeworks.edit',
            'compliancedomains.view',
        ] as $permission) {
            $this->assertSame(1, $permissions[$permission], "Document controller should grant {$permission}");
        }

        $this->assertNotSame(1, $permissions['compliancedomains.edit'] ?? 0);
        $this->assertNotSame(1, $permissions['compliancedomains.delete'] ?? 0);
    }
}
