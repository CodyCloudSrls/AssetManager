<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adds the Italian annual depreciation coefficient (coefficiente di ammortamento, e.g.
 * the DM 31/12/1988 ministerial rate) to depreciation schemes. Optional: when null the
 * libro cespiti derives the rate from the scheme's useful life (12 / months).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('depreciations') && ! Schema::hasColumn('depreciations', 'coefficiente_annuo')) {
            Schema::table('depreciations', function (Blueprint $table) {
                $table->decimal('coefficiente_annuo', 6, 3)->nullable()->after('months');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('depreciations') && Schema::hasColumn('depreciations', 'coefficiente_annuo')) {
            Schema::table('depreciations', function (Blueprint $table) {
                $table->dropColumn('coefficiente_annuo');
            });
        }
    }
};
