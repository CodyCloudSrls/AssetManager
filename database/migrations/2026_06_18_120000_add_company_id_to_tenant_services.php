<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('tenant_services') && ! Schema::hasColumn('tenant_services', 'company_id')) {
            Schema::table('tenant_services', function (Blueprint $table) {
                // NULL = service applies to the whole tenant (every company);
                // a value scopes the service to one company within the tenant.
                $table->unsignedInteger('company_id')->nullable()->after('tenant_id');
                $table->index('company_id', 'tenant_services_company_idx');
                $table->foreign('company_id')->references('id')->on('companies')->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('tenant_services') && Schema::hasColumn('tenant_services', 'company_id')) {
            Schema::table('tenant_services', function (Blueprint $table) {
                $table->dropForeign('tenant_services_company_id_foreign');
                $table->dropIndex('tenant_services_company_idx');
                $table->dropColumn('company_id');
            });
        }
    }
};
