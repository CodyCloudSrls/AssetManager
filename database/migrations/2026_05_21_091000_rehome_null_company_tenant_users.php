<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('users') || ! Schema::hasColumn('users', 'company_id')) {
            return;
        }

        DB::table('users')
            ->whereNull('company_id')
            ->whereNotNull('created_by')
            ->where(function ($query) {
                $query->whereNull('permissions')
                    ->orWhere(function ($permissionQuery) {
                        $permissionQuery->where('permissions', 'NOT LIKE', '%"superuser":"1"%')
                            ->where('permissions', 'NOT LIKE', '%"superuser":1%');
                    });
            })
            ->orderBy('id')
            ->get(['id', 'created_by'])
            ->each(function ($user) {
                $creatorCompanyId = DB::table('users')
                    ->where('id', $user->created_by)
                    ->value('company_id');

                if ($creatorCompanyId) {
                    DB::table('users')
                        ->where('id', $user->id)
                        ->whereNull('company_id')
                        ->update(['company_id' => $creatorCompanyId]);
                }
            });
    }

    public function down(): void
    {
        // Intentionally not reversible: the previous null company made tenant users unreachable or over-visible.
    }
};
