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
                    'documents.view',
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
                'system_key' => 'default_compliance_manager',
                'name' => 'Default - Compliance Manager',
                'notes' => 'Document and compliance operations role. Can manage document registers and related ticket workflows while keeping asset and user data in read-only mode.',
                'permissions' => self::permissionMap([
                    'reports.view',
                    'documents.view', 'documents.create', 'documents.edit', 'documents.delete', 'documents.files',
                    'documenttypes.view', 'documenttypes.create', 'documenttypes.edit', 'documenttypes.delete',
                    'documentframeworks.view', 'documentframeworks.create', 'documentframeworks.edit', 'documentframeworks.delete',
                    'tickets.view', 'tickets.create', 'tickets.operate', 'tickets.files',
                    'assets.view',
                    'users.view',
                    'locations.view',
                    'companies.view',
                ]),
            ],
            [
                'system_key' => 'default_read_only_auditor',
                'name' => 'Default - Read Only Auditor',
                'notes' => 'Read-only audit profile. Can inspect the tenant perimeter, reports, tickets, documents and inventory metadata without operational write permissions or file management.',
                'permissions' => self::permissionMap([
                    'reports.view',
                    'assets.view',
                    'documents.view',
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
                    'depreciations.view',
                    'customfields.view',
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
}
