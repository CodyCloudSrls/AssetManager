<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('tenants') && ! Schema::hasColumn('tenants', 'default_compliance_jurisdiction')) {
            Schema::table('tenants', function (Blueprint $table) {
                $table->string('default_compliance_jurisdiction', 10)->default('EU')->after('default_locale');
            });
        }

        if (Schema::hasTable('tenants') && Schema::hasColumn('tenants', 'default_compliance_jurisdiction')) {
            DB::table('tenants')
                ->whereNull('default_compliance_jurisdiction')
                ->orWhere('default_compliance_jurisdiction', '')
                ->update(['default_compliance_jurisdiction' => 'EU']);

            if (Schema::hasTable('companies') && Schema::hasTable('document_frameworks')) {
                $italianNisTenantIds = DB::table('companies')
                    ->join('document_frameworks', 'document_frameworks.company_id', '=', 'companies.id')
                    ->where('document_frameworks.source_pack_key', 'nis2_it')
                    ->whereNull('document_frameworks.deleted_at')
                    ->whereNotNull('companies.tenant_id')
                    ->pluck('companies.tenant_id')
                    ->unique()
                    ->values()
                    ->all();

                if (count($italianNisTenantIds) > 0) {
                    DB::table('tenants')
                        ->whereIn('id', $italianNisTenantIds)
                        ->update(['default_compliance_jurisdiction' => 'IT']);
                }
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('tenants') && Schema::hasColumn('tenants', 'default_compliance_jurisdiction')) {
            Schema::table('tenants', function (Blueprint $table) {
                $table->dropColumn('default_compliance_jurisdiction');
            });
        }
    }
};
