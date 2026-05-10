<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            if (! Schema::hasColumn('companies', 'parent_id')) {
                $table->unsignedInteger('parent_id')->nullable()->after('name');
                $table->index('parent_id', 'companies_parent_id_index');
            }
        });

        $this->addTemplateOwnershipColumns('models', 'models_company_visibility_deleted_idx');
        $this->addTemplateOwnershipColumns('custom_fieldsets', 'custom_fieldsets_company_visibility_idx');
        $this->addTemplateOwnershipColumns('document_types', 'document_types_company_visibility_deleted_idx');
        $this->addTemplateOwnershipColumns('document_frameworks', 'document_frameworks_company_visibility_deleted_idx');
    }

    public function down(): void
    {
        $this->dropTemplateOwnershipColumns('models', 'models_company_visibility_deleted_idx');
        $this->dropTemplateOwnershipColumns('custom_fieldsets', 'custom_fieldsets_company_visibility_idx');
        $this->dropTemplateOwnershipColumns('document_types', 'document_types_company_visibility_deleted_idx');
        $this->dropTemplateOwnershipColumns('document_frameworks', 'document_frameworks_company_visibility_deleted_idx');

        Schema::table('companies', function (Blueprint $table) {
            if (Schema::hasColumn('companies', 'parent_id')) {
                $table->dropIndex('companies_parent_id_index');
                $table->dropColumn('parent_id');
            }
        });
    }

    private function addTemplateOwnershipColumns(string $tableName, string $indexName): void
    {
        Schema::table($tableName, function (Blueprint $table) use ($tableName, $indexName) {
            if (! Schema::hasColumn($tableName, 'company_id')) {
                $table->unsignedInteger('company_id')->nullable()->after('created_by');
            }

            if (! Schema::hasColumn($tableName, 'visibility_type')) {
                $table->string('visibility_type', 32)->default('global')->after('company_id');
            }

            if (! $this->indexExists($tableName, $indexName)) {
                $columns = ['company_id', 'visibility_type'];

                if (Schema::hasColumn($tableName, 'deleted_at')) {
                    $columns[] = 'deleted_at';
                }

                $table->index($columns, $indexName);
            }
        });

        DB::table($tableName)
            ->whereNull('company_id')
            ->update(['visibility_type' => 'global']);

        DB::table($tableName)
            ->whereNotNull('company_id')
            ->whereNull('visibility_type')
            ->update(['visibility_type' => 'private']);
    }

    private function dropTemplateOwnershipColumns(string $tableName, string $indexName): void
    {
        Schema::table($tableName, function (Blueprint $table) use ($tableName, $indexName) {
            if ($this->indexExists($tableName, $indexName)) {
                $table->dropIndex($indexName);
            }

            if (Schema::hasColumn($tableName, 'visibility_type')) {
                $table->dropColumn('visibility_type');
            }

            if (Schema::hasColumn($tableName, 'company_id')) {
                $table->dropColumn('company_id');
            }
        });
    }

    private function indexExists(string $tableName, string $indexName): bool
    {
        return collect(Schema::getIndexes($tableName))
            ->contains(fn (array $index) => ($index['name'] ?? null) === $indexName);
    }
};
