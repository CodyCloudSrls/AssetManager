<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const PLATFORM_PERMISSION = 'superadmin';
    private const PERMISSION = 'tenants.view_all';

    public function up(): void
    {
        $this->backfillPermission('users');
        $this->backfillPermission('permission_groups');
    }

    public function down(): void
    {
        $this->removePermission('users');
        $this->removePermission('permission_groups');
    }

    private function backfillPermission(string $table): void
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

                    if (! is_array($permissions) || ! $this->permissionAllows($permissions[self::PLATFORM_PERMISSION] ?? null)) {
                        continue;
                    }

                    if ($this->permissionDenies($permissions[self::PERMISSION] ?? null)) {
                        continue;
                    }

                    if (($permissions[self::PERMISSION] ?? null) === '1' || ($permissions[self::PERMISSION] ?? null) === 1) {
                        continue;
                    }

                    $permissions[self::PERMISSION] = '1';

                    DB::table($table)
                        ->where('id', $row->id)
                        ->update(['permissions' => json_encode($permissions)]);
                }
            });
    }

    private function removePermission(string $table): void
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

                    if (! is_array($permissions) || ! array_key_exists(self::PERMISSION, $permissions)) {
                        continue;
                    }

                    unset($permissions[self::PERMISSION]);

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
