<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('custom_fields', function (Blueprint $table) {
            if (! Schema::hasColumn('custom_fields', 'company_id')) {
                $table->unsignedInteger('company_id')->nullable()->after('help_text');
            }

            if (! Schema::hasColumn('custom_fields', 'visibility_type')) {
                $table->string('visibility_type', 32)->default('global')->after('company_id');
            }

            if (! $this->indexExists('custom_fields', 'custom_fields_company_visibility_idx')) {
                $table->index(['company_id', 'visibility_type'], 'custom_fields_company_visibility_idx');
            }
        });

        $codyCloudCompanyId = DB::table('companies')
            ->whereNull('deleted_at')
            ->where('name', 'CodyCloud')
            ->value('id');

        if (! is_null($codyCloudCompanyId)) {
            DB::table('custom_fields')
                ->whereNull('company_id')
                ->update([
                    'company_id' => $codyCloudCompanyId,
                    'visibility_type' => 'private',
                ]);
        } else {
            DB::table('custom_fields')
                ->whereNull('company_id')
                ->update(['visibility_type' => 'global']);
        }

        DB::table('custom_fields')
            ->whereNotNull('company_id')
            ->whereNull('visibility_type')
            ->update(['visibility_type' => 'private']);
    }

    public function down(): void
    {
        Schema::table('custom_fields', function (Blueprint $table) {
            if ($this->indexExists('custom_fields', 'custom_fields_company_visibility_idx')) {
                $table->dropIndex('custom_fields_company_visibility_idx');
            }

            if (Schema::hasColumn('custom_fields', 'visibility_type')) {
                $table->dropColumn('visibility_type');
            }

            if (Schema::hasColumn('custom_fields', 'company_id')) {
                $table->dropColumn('company_id');
            }
        });
    }

    private function indexExists(string $tableName, string $indexName): bool
    {
        return collect(DB::select("SHOW INDEX FROM `{$tableName}`"))
            ->contains(fn ($row) => $row->Key_name === $indexName);
    }
};
