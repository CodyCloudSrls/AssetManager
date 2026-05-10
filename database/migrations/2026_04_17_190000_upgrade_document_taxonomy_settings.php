<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('document_types', function (Blueprint $table) {
            if (! Schema::hasColumn('document_types', 'created_by')) {
                $table->unsignedInteger('created_by')->nullable()->after('is_active');
            }

            if (! Schema::hasColumn('document_types', 'deleted_at')) {
                $table->softDeletes();
            }
        });

        Schema::table('document_frameworks', function (Blueprint $table) {
            if (! Schema::hasColumn('document_frameworks', 'created_by')) {
                $table->unsignedInteger('created_by')->nullable()->after('is_active');
            }

            if (! Schema::hasColumn('document_frameworks', 'deleted_at')) {
                $table->softDeletes();
            }
        });

        $this->replaceSlugUniqueIndex('document_types');
        $this->replaceSlugUniqueIndex('document_frameworks');

        Schema::table('document_types', function (Blueprint $table) {
            $table->index(['is_active', 'deleted_at'], 'document_types_active_deleted_idx');
        });

        Schema::table('document_frameworks', function (Blueprint $table) {
            $table->index(['is_active', 'deleted_at'], 'document_frameworks_active_deleted_idx');
        });
    }

    public function down(): void
    {
        Schema::table('document_types', function (Blueprint $table) {
            if (Schema::hasColumn('document_types', 'created_by')) {
                $table->dropColumn('created_by');
            }

            if (Schema::hasColumn('document_types', 'deleted_at')) {
                $table->dropSoftDeletes();
            }

            $table->dropIndex('document_types_active_deleted_idx');
        });

        Schema::table('document_frameworks', function (Blueprint $table) {
            if (Schema::hasColumn('document_frameworks', 'created_by')) {
                $table->dropColumn('created_by');
            }

            if (Schema::hasColumn('document_frameworks', 'deleted_at')) {
                $table->dropSoftDeletes();
            }

            $table->dropIndex('document_frameworks_active_deleted_idx');
        });

        $this->restoreSlugUniqueIndex('document_types');
        $this->restoreSlugUniqueIndex('document_frameworks');
    }

    private function replaceSlugUniqueIndex(string $table): void
    {
        $uniqueName = $this->findIndexName($table, 'slug', false);

        if ($uniqueName) {
            Schema::table($table, function (Blueprint $tableBlueprint) use ($uniqueName) {
                $tableBlueprint->dropUnique($uniqueName);
            });
        }

        $indexName = $table.'_slug_index';

        if (! $this->indexExists($table, $indexName)) {
            Schema::table($table, function (Blueprint $tableBlueprint) use ($indexName) {
                $tableBlueprint->index('slug', $indexName);
            });
        }
    }

    private function restoreSlugUniqueIndex(string $table): void
    {
        $indexName = $table.'_slug_index';

        if ($this->indexExists($table, $indexName)) {
            Schema::table($table, function (Blueprint $tableBlueprint) use ($indexName) {
                $tableBlueprint->dropIndex($indexName);
            });
        }

        $uniqueName = $table.'_slug_unique';

        if (! $this->indexExists($table, $uniqueName)) {
            Schema::table($table, function (Blueprint $tableBlueprint) use ($uniqueName) {
                $tableBlueprint->unique('slug', $uniqueName);
            });
        }
    }

    private function findIndexName(string $table, string $column, bool $nonUnique = false): ?string
    {
        $index = collect(Schema::getIndexes($table))
            ->first(function (array $index) use ($column, $nonUnique) {
                return in_array($column, $index['columns'] ?? [], true)
                    && (bool) ($index['unique'] ?? false) === (! $nonUnique);
            });

        return $index['name'] ?? null;
    }

    private function indexExists(string $table, string $indexName): bool
    {
        return collect(Schema::getIndexes($table))
            ->contains(fn (array $index) => ($index['name'] ?? null) === $indexName);
    }
};
