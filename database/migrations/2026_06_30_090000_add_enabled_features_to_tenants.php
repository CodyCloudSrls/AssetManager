<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Per-tenant feature flags. A tenant only "has" the modules it needs (ERP, NIS2,
 * Documents, Tickets, ...). NULL = all features enabled (backward-compatible for
 * existing tenants); an array stores the explicitly enabled feature keys.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('tenants') && ! Schema::hasColumn('tenants', 'enabled_features')) {
            Schema::table('tenants', function (Blueprint $table) {
                $table->text('enabled_features')->nullable()->after('default_compliance_jurisdiction');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('tenants') && Schema::hasColumn('tenants', 'enabled_features')) {
            Schema::table('tenants', function (Blueprint $table) {
                $table->dropColumn('enabled_features');
            });
        }
    }
};
