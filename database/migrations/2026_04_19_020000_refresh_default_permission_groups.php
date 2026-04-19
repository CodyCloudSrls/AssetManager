<?php

use App\Models\Group;
use App\Support\DefaultPermissionGroups;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
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
        // No-op: default groups are project baseline data and should not be removed automatically.
    }
};
