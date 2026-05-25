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
            'documenttypes.edit',
            'documentframeworks.edit',
            'compliancedomains.view',
        ] as $permission) {
            $this->assertSame(1, $permissions[$permission], "Document controller should grant {$permission}");
        }

        $this->assertNotSame(1, $permissions['documents.force_delete'] ?? 0);
        $this->assertNotSame(1, $permissions['compliancedomains.edit'] ?? 0);
        $this->assertNotSame(1, $permissions['compliancedomains.delete'] ?? 0);
    }

    public function test_default_groups_do_not_grant_irreversible_document_force_delete()
    {
        $groups = collect(DefaultPermissionGroups::definitions())->keyBy('system_key');

        foreach ($groups as $systemKey => $definition) {
            $this->assertNotSame(1, $definition['permissions']['documents.force_delete'] ?? 0, "{$systemKey} should not grant documents.force_delete by default");
        }
    }

    public function test_default_document_groups_keep_expected_area_scope()
    {
        $groups = collect(DefaultPermissionGroups::definitions())->keyBy('system_key');

        foreach ([
            'default_document_controller',
            'default_compliance_evidence_coordinator',
            'default_compliance_manager',
            'default_executive_read_only',
            'default_read_only_auditor',
            'default_tenant_operations_admin',
        ] as $systemKey) {
            foreach (['administration', 'it', 'cybersecurity'] as $area) {
                $this->assertSame(1, $groups[$systemKey]['permissions']["documents.area.{$area}.view"], "{$systemKey} should view {$area} documents");
                $this->assertSame(1, $groups[$systemKey]['permissions']["documents.area.{$area}.files.view"], "{$systemKey} should view {$area} files");
            }
        }

        $administrationUpdater = $groups['default_administration_document_updater']['permissions'];
        $this->assertSame(1, $administrationUpdater['documents.area.administration.view']);
        $this->assertNotSame(1, $administrationUpdater['documents.area.it.view'] ?? 0);
        $this->assertNotSame(1, $administrationUpdater['documents.area.cybersecurity.view'] ?? 0);

        $itUpdater = $groups['default_it_document_updater']['permissions'];
        $this->assertSame(1, $itUpdater['documents.area.it.view']);
        $this->assertNotSame(1, $itUpdater['documents.area.administration.view'] ?? 0);
        $this->assertNotSame(1, $itUpdater['documents.area.cybersecurity.view'] ?? 0);

        $cybersecurityUpdater = $groups['default_cybersecurity_document_updater']['permissions'];
        $this->assertSame(1, $cybersecurityUpdater['documents.area.cybersecurity.view']);
        $this->assertSame(1, $cybersecurityUpdater['documents.area.it.view']);
        $this->assertNotSame(1, $cybersecurityUpdater['documents.area.administration.view'] ?? 0);
    }

    public function test_read_only_auditor_is_limited_to_nis_document_audit_scope()
    {
        $groups = collect(DefaultPermissionGroups::definitions())->keyBy('system_key');
        $permissions = $groups['default_read_only_auditor']['permissions'];

        foreach ([
            'reports.nis_risk_matrix.view',
            'reports.nis_real_coverage.view',
            'documents.view',
            'documents.files.view',
            'documenttypes.view',
            'documentframeworks.view',
            'compliancedomains.view',
            'assets.view',
            'suppliers.view',
            'users.view',
            'locations.view',
            'companies.view',
        ] as $permission) {
            $this->assertSame(1, $permissions[$permission], "Read-only auditor should grant {$permission}");
        }

        foreach ([
            'reports.view',
            'tickets.view',
            'accessories.view',
            'consumables.view',
            'licenses.view',
            'components.view',
            'kits.view',
            'models.view',
            'categories.view',
            'statuslabels.view',
            'manufacturers.view',
            'customers.view',
            'contracts.view',
            'depreciations.view',
            'customfields.view',
            'documents.create',
            'documents.edit',
            'documents.delete',
        ] as $permission) {
            $this->assertNotSame(1, $permissions[$permission] ?? 0, "Read-only auditor should not grant {$permission}");
        }
    }

    public function test_context_groups_do_not_grant_unrelated_inventory_or_commercial_scope()
    {
        $groups = collect(DefaultPermissionGroups::definitions())->keyBy('system_key');

        foreach (['default_helpdesk_operator', 'default_service_desk_manager'] as $systemKey) {
            $permissions = $groups[$systemKey]['permissions'];

            foreach ([
                'accessories.view',
                'consumables.view',
                'licenses.view',
                'components.view',
                'models.view',
                'categories.view',
                'statuslabels.view',
            ] as $permission) {
                $this->assertNotSame(1, $permissions[$permission] ?? 0, "{$systemKey} should not grant {$permission}");
            }
        }

        foreach ([
            'default_inventory_operator',
            'default_asset_manager',
            'default_procurement_catalog_manager',
        ] as $systemKey) {
            $permissions = $groups[$systemKey]['permissions'];
            $this->assertNotSame(1, $permissions['documents.view'] ?? 0, "{$systemKey} should not grant document register access");
        }

        $complianceManager = $groups['default_compliance_manager']['permissions'];
        foreach ([
            'customers.create',
            'customers.edit',
            'customers.delete',
            'customers.files',
            'contracts.create',
            'contracts.edit',
            'contracts.delete',
        ] as $permission) {
            $this->assertNotSame(1, $complianceManager[$permission] ?? 0, "Compliance manager should not write {$permission}");
        }
    }
}
