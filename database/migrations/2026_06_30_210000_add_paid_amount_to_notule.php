<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Notule track partial payments (netto / pagato / residuo) like the legacy gestionale.
 * Adds paid_amount so the outstanding (da pagare) is amount - paid_amount.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('notule') && ! Schema::hasColumn('notule', 'paid_amount')) {
            Schema::table('notule', function (Blueprint $table) {
                $table->decimal('paid_amount', 15, 2)->default(0)->after('amount');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('notule') && Schema::hasColumn('notule', 'paid_amount')) {
            Schema::table('notule', function (Blueprint $table) {
                $table->dropColumn('paid_amount');
            });
        }
    }
};
