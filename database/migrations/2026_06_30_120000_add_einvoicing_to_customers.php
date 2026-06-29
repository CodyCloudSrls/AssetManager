<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adds the Italian electronic-invoicing recipient details to the customer registry:
 * Codice Destinatario SDI (7 chars) and PEC. Required to issue fatture elettroniche and
 * to map customers to Fatture in Cloud clients.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('customers')) {
            return;
        }

        Schema::table('customers', function (Blueprint $table) {
            if (! Schema::hasColumn('customers', 'sdi_code')) {
                $table->string('sdi_code', 7)->nullable()->after('tax_code');
            }
            if (! Schema::hasColumn('customers', 'pec')) {
                $table->string('pec')->nullable()->after('sdi_code');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('customers')) {
            return;
        }

        Schema::table('customers', function (Blueprint $table) {
            foreach (['sdi_code', 'pec'] as $column) {
                if (Schema::hasColumn('customers', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
