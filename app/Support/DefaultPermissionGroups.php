<?php

namespace App\Support;

use App\Helpers\Helper;

final class DefaultPermissionGroups
{
    public static function definitions(): array
    {
        return [
            [
                'system_key' => 'default_helpdesk_operator',
                'name' => 'Default - Helpdesk Operator',
                'notes' => 'Tenant-scoped helpdesk operator. Can open and work tickets, track time, manage ticket attachments, and inspect inventory context without full administrative edit rights.',
                'permissions' => self::permissionMap([
                    'reports.view',
                    'tickets.view', 'tickets.create', 'tickets.operate', 'tickets.files',
                    'assets.view',
                    'documents.view', 'documents.files.view',
                    'users.view',
                    'locations.view',
                    'companies.view',
                    'licenses.view',
                    'accessories.view',
                    'consumables.view',
                    'components.view',
                    'models.view',
                    'categories.view',
                    'statuslabels.view',
                ]),
            ],
            [
                'system_key' => 'default_service_desk_manager',
                'name' => 'Default - Service Desk Manager',
                'notes' => 'Service desk lead profile. Can manage the full ticket lifecycle, ticket attachments, and support context across users, assets, documents, and tenant inventory metadata.',
                'permissions' => self::permissionMap([
                    'reports.view',
                    'tickets.view', 'tickets.create', 'tickets.operate', 'tickets.edit', 'tickets.delete', 'tickets.files',
                    'assets.view', 'assets.files',
                    'documents.view', 'documents.files.view', 'documents.files',
                    'users.view',
                    'locations.view',
                    'companies.view',
                    'licenses.view',
                    'accessories.view',
                    'consumables.view',
                    'components.view',
                    'models.view',
                    'categories.view',
                    'statuslabels.view',
                ]),
            ],
            [
                'system_key' => 'default_inventory_operator',
                'name' => 'Default - Inventory Operator',
                'notes' => 'Day-to-day inventory operations role. Can move, assign, audit, and track tenant inventory without catalog-administration or tenant-wide governance rights.',
                'permissions' => self::permissionMap([
                    'reports.view',
                    'assets.view', 'assets.create', 'assets.edit', 'assets.checkin', 'assets.checkout', 'assets.audit', 'assets.view.requestable', 'assets.files',
                    'accessories.view', 'accessories.checkout', 'accessories.checkin',
                    'consumables.view', 'consumables.checkout',
                    'licenses.view', 'licenses.checkout', 'licenses.checkin',
                    'components.view', 'components.checkout', 'components.checkin',
                    'tickets.view', 'tickets.create',
                    'documents.view', 'documents.files.view',
                    'users.view',
                    'locations.view',
                    'companies.view',
                ]),
            ],
            [
                'system_key' => 'default_asset_manager',
                'name' => 'Default - Asset Manager',
                'notes' => 'Operational inventory manager. Can administer assets and related inventory objects, including lifecycle settings and catalog metadata, without platform-level system access.',
                'permissions' => self::permissionMap([
                    'reports.view',
                    'assets.view', 'assets.create', 'assets.edit', 'assets.delete', 'assets.checkin', 'assets.checkout', 'assets.audit', 'assets.view.requestable', 'assets.view.encrypted_custom_fields', 'assets.files',
                    'accessories.view', 'accessories.create', 'accessories.edit', 'accessories.delete', 'accessories.checkout', 'accessories.checkin', 'accessories.files',
                    'consumables.view', 'consumables.create', 'consumables.edit', 'consumables.delete', 'consumables.checkout', 'consumables.files',
                    'licenses.view', 'licenses.create', 'licenses.edit', 'licenses.delete', 'licenses.checkout', 'licenses.checkin', 'licenses.keys', 'licenses.files',
                    'components.view', 'components.create', 'components.edit', 'components.delete', 'components.checkout', 'components.checkin', 'components.files',
                    'models.view', 'models.create', 'models.edit', 'models.delete',
                    'categories.view', 'categories.create', 'categories.edit', 'categories.delete',
                    'statuslabels.view', 'statuslabels.create', 'statuslabels.edit', 'statuslabels.delete',
                    'manufacturers.view', 'manufacturers.create', 'manufacturers.edit', 'manufacturers.delete',
                    'suppliers.view', 'suppliers.create', 'suppliers.edit', 'suppliers.delete',
                    'customers.view', 'contracts.view',
                    'depreciations.view', 'depreciations.create', 'depreciations.edit', 'depreciations.delete',
                    'customfields.view', 'customfields.create', 'customfields.edit', 'customfields.delete',
                    'locations.view', 'locations.create', 'locations.edit', 'locations.delete',
                    'departments.view', 'departments.create', 'departments.edit', 'departments.delete',
                    'companies.view',
                    'users.view',
                    'documents.view',
                ]),
            ],
            [
                'system_key' => 'default_procurement_catalog_manager',
                'name' => 'Default - Procurement And Catalog Manager',
                'notes' => 'Catalog and procurement governance role. Can maintain suppliers, manufacturers, models, categories, depreciation rules, kits, and related purchasing metadata without broad tenant-admin powers.',
                'permissions' => self::permissionMap([
                    'reports.view',
                    'models.view', 'models.create', 'models.edit', 'models.delete', 'models.files',
                    'categories.view', 'categories.create', 'categories.edit', 'categories.delete',
                    'manufacturers.view', 'manufacturers.create', 'manufacturers.edit', 'manufacturers.delete',
                    'suppliers.view', 'suppliers.create', 'suppliers.edit', 'suppliers.delete', 'suppliers.files',
                    'depreciations.view', 'depreciations.create', 'depreciations.edit', 'depreciations.delete',
                    'statuslabels.view', 'statuslabels.create', 'statuslabels.edit', 'statuslabels.delete',
                    'kits.view', 'kits.create', 'kits.edit', 'kits.delete',
                    'licenses.view', 'licenses.create', 'licenses.edit', 'licenses.delete', 'licenses.keys', 'licenses.files',
                    'assets.view',
                    'companies.view',
                    'locations.view',
                    'documents.view',
                ]),
            ],
            [
                'system_key' => 'default_administration_document_updater',
                'name' => 'Default - Administration Document Updater',
                'notes' => 'Tenant-scoped document updater for administrative records. Can create, update, and manage attachments only for administration-area documents without framework or document-type administration rights.',
                'permissions' => self::permissionMap([
                    'reports.view',
                    'documents.view', 'documents.create', 'documents.edit', 'documents.files.view', 'documents.files',
                    ...self::documentAreaPermissions(['administration'], true, true),
                    'documenttypes.view',
                    'documentframeworks.view',
                    'tickets.view', 'tickets.create',
                    'users.view',
                    'locations.view',
                    'companies.view',
                ]),
            ],
            [
                'system_key' => 'default_it_document_updater',
                'name' => 'Default - IT Document Updater',
                'notes' => 'Tenant-scoped document updater for IT evidence and operating records. Can maintain IT-area documents and attachments without changing frameworks, requirements, or document type settings.',
                'permissions' => self::permissionMap([
                    'reports.view',
                    'documents.view', 'documents.create', 'documents.edit', 'documents.files.view', 'documents.files',
                    ...self::documentAreaPermissions(['it'], true, true),
                    'documenttypes.view',
                    'documentframeworks.view',
                    'tickets.view', 'tickets.create',
                    'assets.view',
                    'users.view',
                    'locations.view',
                    'companies.view',
                ]),
            ],
            [
                'system_key' => 'default_cybersecurity_document_updater',
                'name' => 'Default - Cybersecurity Document Updater',
                'notes' => 'Tenant-scoped document updater for cybersecurity evidence. Can maintain cybersecurity-area documents, view IT-area documents needed for compliance work, and cannot change framework or document type governance.',
                'permissions' => self::permissionMap([
                    'reports.view', 'reports.nis_risk_matrix.view', 'reports.nis_real_coverage.view',
                    'documents.view', 'documents.create', 'documents.edit', 'documents.files.view', 'documents.files',
                    ...self::documentAreaPermissions(['cybersecurity'], true, true),
                    ...self::documentAreaPermissions(['it'], false, false),
                    'documents.requirements.map',
                    'documenttypes.view',
                    'documentframeworks.view',
                    'tickets.view', 'tickets.create', 'tickets.operate', 'tickets.files',
                    'assets.view',
                    'users.view',
                    'locations.view',
                    'companies.view',
                ]),
            ],
            [
                'system_key' => 'default_document_controller',
                'name' => 'Default - Document Controller',
                'notes' => 'Controlled-document administration role. Can manage document registers, framework libraries, requirement mappings, and related ticket operations without tenant-wide asset administration.',
                'permissions' => self::permissionMap([
                    'reports.view',
                    'documents.view', 'documents.create', 'documents.edit', 'documents.delete', 'documents.restore', 'documents.force_delete', 'documents.files.view', 'documents.files', 'documents.requirements.map',
                    ...self::documentAreaPermissions(self::documentAreas(), true, true),
                    'documenttypes.view', 'documenttypes.create', 'documenttypes.edit', 'documenttypes.delete',
                    'documentframeworks.view', 'documentframeworks.create', 'documentframeworks.edit', 'documentframeworks.delete',
                    'compliancedomains.view',
                    'customers.view', 'contracts.view',
                    'tickets.view', 'tickets.create', 'tickets.operate', 'tickets.files',
                    'assets.view',
                    'users.view',
                    'locations.view',
                    'companies.view',
                ]),
            ],
            [
                'system_key' => 'default_compliance_evidence_coordinator',
                'name' => 'Default - Compliance Evidence Coordinator',
                'notes' => 'Compliance evidence coordinator. Can update evidence documents, attachments, and requirement mappings across document areas while framework, requirement, and document-type governance remains read-only.',
                'permissions' => self::permissionMap([
                    'reports.view', 'reports.nis_risk_matrix.view', 'reports.nis_real_coverage.view',
                    'documents.view', 'documents.create', 'documents.edit', 'documents.files.view', 'documents.files', 'documents.requirements.map',
                    ...self::documentAreaPermissions(self::documentAreas(), true, true),
                    'documenttypes.view',
                    'documentframeworks.view',
                    'compliancedomains.view',
                    'customers.view', 'contracts.view',
                    'tickets.view', 'tickets.create', 'tickets.operate', 'tickets.files',
                    'assets.view',
                    'users.view',
                    'locations.view',
                    'companies.view',
                ]),
            ],
            [
                'system_key' => 'default_compliance_manager',
                'name' => 'Default - Compliance Manager',
                'notes' => 'Document and compliance operations role. Can manage document registers and related ticket workflows while keeping asset and user data in read-only mode.',
                'permissions' => self::permissionMap([
                    'reports.view', 'reports.nis_risk_matrix.view', 'reports.nis_real_coverage.view',
                    'documents.view', 'documents.create', 'documents.edit', 'documents.delete', 'documents.restore', 'documents.files.view', 'documents.files', 'documents.requirements.map',
                    ...self::documentAreaPermissions(self::documentAreas(), true, true),
                    'documenttypes.view', 'documenttypes.create', 'documenttypes.edit', 'documenttypes.delete',
                    'documentframeworks.view', 'documentframeworks.create', 'documentframeworks.edit', 'documentframeworks.delete',
                    'compliancedomains.view',
                    'customers.view', 'customers.create', 'customers.edit', 'customers.delete', 'customers.files',
                    'contracts.view', 'contracts.create', 'contracts.edit', 'contracts.delete',
                    'tickets.view', 'tickets.create', 'tickets.operate', 'tickets.files',
                    'assets.view',
                    'users.view',
                    'locations.view',
                    'companies.view',
                ]),
            ],
            [
                'system_key' => 'default_executive_read_only',
                'name' => 'Default - Executive Read Only',
                'notes' => 'Leadership read-only profile. Can inspect tenant dashboards, tickets, document coverage, and inventory summaries without operational write permissions or attachment management.',
                'permissions' => self::permissionMap([
                    'reports.view',
                    'assets.view',
                    'documents.view', 'documents.files.view',
                    ...self::documentAreaPermissions(self::documentAreas(), false, false),
                    'tickets.view',
                    'documenttypes.view',
                    'documentframeworks.view',
                    'accessories.view',
                    'consumables.view',
                    'licenses.view',
                    'components.view',
                    'kits.view',
                    'users.view',
                    'locations.view',
                    'companies.view',
                    'departments.view',
                    'models.view',
                    'categories.view',
                    'statuslabels.view',
                    'manufacturers.view',
                    'suppliers.view',
                    'customers.view',
                    'contracts.view',
                    'depreciations.view',
                ]),
            ],
            [
                'system_key' => 'default_read_only_auditor',
                'name' => 'Default - Read Only Auditor',
                'notes' => 'Read-only audit profile. Can inspect the tenant perimeter, reports, tickets, documents and inventory metadata without operational write permissions or file management.',
                'permissions' => self::permissionMap([
                    'reports.view',
                    'assets.view',
                    'documents.view', 'documents.files.view',
                    ...self::documentAreaPermissions(self::documentAreas(), false, false),
                    'tickets.view',
                    'documenttypes.view',
                    'documentframeworks.view',
                    'accessories.view',
                    'consumables.view',
                    'licenses.view',
                    'components.view',
                    'users.view',
                    'locations.view',
                    'companies.view',
                    'departments.view',
                    'models.view',
                    'categories.view',
                    'statuslabels.view',
                    'manufacturers.view',
                    'suppliers.view',
                    'customers.view',
                    'contracts.view',
                    'depreciations.view',
                    'customfields.view',
                ]),
            ],
            [
                'system_key' => 'default_tenant_operations_admin',
                'name' => 'Default - Tenant Operations Admin',
                'notes' => 'High-scope tenant operations administrator. Can manage tenant-scoped operational objects, users, inventory, documents, ticket workflows, and imports without platform superuser powers.',
                'permissions' => self::permissionMap([
                    'import',
                    'reports.view',
                    'assets.view', 'assets.create', 'assets.edit', 'assets.delete', 'assets.checkin', 'assets.checkout', 'assets.audit', 'assets.view.requestable', 'assets.view.encrypted_custom_fields', 'assets.files',
                    'accessories.view', 'accessories.create', 'accessories.edit', 'accessories.delete', 'accessories.checkout', 'accessories.checkin', 'accessories.files',
                    'consumables.view', 'consumables.create', 'consumables.edit', 'consumables.delete', 'consumables.checkout', 'consumables.files',
                    'licenses.view', 'licenses.create', 'licenses.edit', 'licenses.delete', 'licenses.checkout', 'licenses.checkin', 'licenses.keys', 'licenses.files',
                    'components.view', 'components.create', 'components.edit', 'components.delete', 'components.checkout', 'components.checkin', 'components.files',
                    'kits.view', 'kits.create', 'kits.edit', 'kits.delete',
                    'models.view', 'models.create', 'models.edit', 'models.delete', 'models.files',
                    'categories.view', 'categories.create', 'categories.edit', 'categories.delete',
                    'statuslabels.view', 'statuslabels.create', 'statuslabels.edit', 'statuslabels.delete',
                    'manufacturers.view', 'manufacturers.create', 'manufacturers.edit', 'manufacturers.delete',
                    'suppliers.view', 'suppliers.create', 'suppliers.edit', 'suppliers.delete', 'suppliers.files',
                    'customers.view', 'customers.create', 'customers.edit', 'customers.delete', 'customers.files',
                    'contracts.view', 'contracts.create', 'contracts.edit', 'contracts.delete',
                    'depreciations.view', 'depreciations.create', 'depreciations.edit', 'depreciations.delete',
                    'customfields.view', 'customfields.create', 'customfields.edit', 'customfields.delete',
                    'users.view', 'users.create', 'users.edit', 'users.delete', 'users.files',
                    'locations.view', 'locations.create', 'locations.edit', 'locations.delete', 'locations.files',
                    'departments.view', 'departments.create', 'departments.edit', 'departments.delete', 'departments.files',
                    'companies.view', 'companies.create', 'companies.edit', 'companies.delete', 'companies.files',
                    'documents.view', 'documents.create', 'documents.edit', 'documents.delete', 'documents.restore', 'documents.force_delete', 'documents.files.view', 'documents.files', 'documents.requirements.map',
                    ...self::documentAreaPermissions(self::documentAreas(), true, true),
                    'documenttypes.view', 'documenttypes.create', 'documenttypes.edit', 'documenttypes.delete',
                    'documentframeworks.view', 'documentframeworks.create', 'documentframeworks.edit', 'documentframeworks.delete',
                    'compliancedomains.view',
                    'tickets.view', 'tickets.create', 'tickets.operate', 'tickets.edit', 'tickets.delete', 'tickets.files',
                ]),
            ],
        ];
    }

    /**
     * @param  array<int, string>  $grantedPermissions
     * @return array<string, int>
     */
    private static function permissionMap(array $grantedPermissions): array
    {
        return Helper::selectedPermissionsArray(
            config('permissions'),
            array_fill_keys($grantedPermissions, 1)
        );
    }

    /**
     * @return array<int, string>
     */
    private static function documentAreas(): array
    {
        return ['administration', 'it', 'cybersecurity'];
    }

    /**
     * @param  array<int, string>  $areas
     * @return array<int, string>
     */
    private static function documentAreaPermissions(array $areas, bool $canEdit, bool $canManageFiles): array
    {
        $permissions = [];

        foreach ($areas as $area) {
            $permissions[] = "documents.area.{$area}.view";
            $permissions[] = "documents.area.{$area}.files.view";

            if ($canEdit) {
                $permissions[] = "documents.area.{$area}.edit";
            }

            if ($canManageFiles) {
                $permissions[] = "documents.area.{$area}.files";
            }
        }

        return $permissions;
    }
}
