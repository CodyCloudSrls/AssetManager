<?php

use App\Models\Group;
use App\Support\DefaultPermissionGroups;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('permission_groups', 'system_key')) {
            Schema::table('permission_groups', function (Blueprint $table) {
                $table->string('system_key', 100)->nullable()->unique()->after('name');
            });
        }

        foreach (DefaultPermissionGroups::definitions() as $definition) {
            $group = Group::where('system_key', $definition['system_key'])->first();

            if (! $group) {
                $group = new Group;
                $group->system_key = $definition['system_key'];
            }

            $group->name = $definition['name'];
            $group->notes = $definition['notes'];
            $group->permissions = json_encode($definition['permissions']);
            $group->created_by ??= null;
            $group->save();
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('permission_groups', 'system_key')) {
            $keys = array_map(
                fn (array $definition) => $definition['system_key'],
                DefaultPermissionGroups::definitions()
            );

            Group::whereIn('system_key', $keys)->delete();

            Schema::table('permission_groups', function (Blueprint $table) {
                $table->dropUnique(['system_key']);
                $table->dropColumn('system_key');
            });
        }
    }
};
