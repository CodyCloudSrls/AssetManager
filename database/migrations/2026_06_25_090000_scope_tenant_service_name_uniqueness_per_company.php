<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The same service name + macro-area must be allowed for DIFFERENT companies of the
 * same tenant (two companies can legitimately offer the identical service). The old
 * unique index was (tenant_id, macro_area, name, deleted_at) — per tenant — which
 * blocked that. Re-scope it to include company_id.
 */
return new class extends Migration
{
    private const OLD_INDEX = 'tenant_services_tenant_macro_name_deleted_unique';
    private const NEW_INDEX = 'tenant_services_tenant_company_macro_name_deleted_unique';

    private function indexNames(string $table): array
    {
        return array_map(
            fn ($index) => $index['name'],
            Schema::getIndexes($table)
        );
    }

    public function up(): void
    {
        if (! Schema::hasTable('tenant_services') || ! Schema::hasColumn('tenant_services', 'company_id')) {
            return;
        }

        $indexes = $this->indexNames('tenant_services');

        Schema::table('tenant_services', function (Blueprint $table) use ($indexes) {
            if (in_array(self::OLD_INDEX, $indexes, true)) {
                $table->dropUnique(self::OLD_INDEX);
            }
            if (! in_array(self::NEW_INDEX, $indexes, true)) {
                $table->unique(['tenant_id', 'company_id', 'macro_area', 'name', 'deleted_at'], self::NEW_INDEX);
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('tenant_services')) {
            return;
        }

        $indexes = $this->indexNames('tenant_services');

        Schema::table('tenant_services', function (Blueprint $table) use ($indexes) {
            if (in_array(self::NEW_INDEX, $indexes, true)) {
                $table->dropUnique(self::NEW_INDEX);
            }
            if (! in_array(self::OLD_INDEX, $indexes, true)) {
                $table->unique(['tenant_id', 'macro_area', 'name', 'deleted_at'], self::OLD_INDEX);
            }
        });
    }
};
