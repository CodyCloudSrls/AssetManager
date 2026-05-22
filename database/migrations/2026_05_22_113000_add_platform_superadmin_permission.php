<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const SUPERADMIN = 'superadmin';
    private const SUPERUSER = 'superuser';
    private const VIEW_ALL_TENANTS = 'tenants.view_all';

    public function up(): void
    {
        $this->promoteLegacyPlatformAdministrators('users');
        $this->promoteLegacyPlatformAdministrators('permission_groups');
    }

    public function down(): void
    {
        $this->removePlatformSuperadminPermission('users');
        $this->removePlatformSuperadminPermission('permission_groups');
    }

    private function promoteLegacyPlatformAdministrators(string $table): void
    {
        if (! Schema::hasTable($table) || ! Schema::hasColumn($table, 'permissions')) {
            return;
        }

        DB::table($table)
            ->whereNotNull('permissions')
            ->orderBy('id')
            ->chunkById(200, function ($rows) use ($table) {
                foreach ($rows as $row) {
                    $permissions = json_decode((string) $row->permissions, true);

                    if (! is_array($permissions)) {
                        continue;
                    }

                    $isLegacyPlatformAdmin = $this->permissionAllows($permissions[self::SUPERADMIN] ?? null)
                        || $this->permissionAllows($permissions[self::SUPERUSER] ?? null)
                        || $this->permissionAllows($permissions[self::VIEW_ALL_TENANTS] ?? null);

                    if (! $isLegacyPlatformAdmin) {
                        continue;
                    }

                    $permissions[self::SUPERADMIN] = '1';

                    if (! $this->permissionDenies($permissions[self::SUPERUSER] ?? null)) {
                        $permissions[self::SUPERUSER] = '1';
                    }

                    if (! $this->permissionDenies($permissions[self::VIEW_ALL_TENANTS] ?? null)) {
                        $permissions[self::VIEW_ALL_TENANTS] = '1';
                    }

                    DB::table($table)
                        ->where('id', $row->id)
                        ->update(['permissions' => json_encode($permissions)]);
                }
            });
    }

    private function removePlatformSuperadminPermission(string $table): void
    {
        if (! Schema::hasTable($table) || ! Schema::hasColumn($table, 'permissions')) {
            return;
        }

        DB::table($table)
            ->whereNotNull('permissions')
            ->orderBy('id')
            ->chunkById(200, function ($rows) use ($table) {
                foreach ($rows as $row) {
                    $permissions = json_decode((string) $row->permissions, true);

                    if (! is_array($permissions) || ! array_key_exists(self::SUPERADMIN, $permissions)) {
                        continue;
                    }

                    unset($permissions[self::SUPERADMIN]);

                    DB::table($table)
                        ->where('id', $row->id)
                        ->update(['permissions' => json_encode($permissions)]);
                }
            });
    }

    private function permissionAllows(mixed $value): bool
    {
        return (string) $value === '1';
    }

    private function permissionDenies(mixed $value): bool
    {
        return (string) $value === '-1';
    }
};
