<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Extends the FiC document mirror for management control (controllo di gestione):
 * - `category`: the FiC expense/revenue category, used to reclassify costs into
 *   COGS / OPEX / LABOR buckets (Conto Economico riclassificato).
 * - `paid_on`: the realization date of the (last) payment, for the flussi di cassa
 *   (cash actually in/out by month/year).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('fic_documents')) {
            return;
        }

        Schema::table('fic_documents', function (Blueprint $table) {
            if (! Schema::hasColumn('fic_documents', 'category')) {
                $table->string('category', 191)->nullable()->after('doc_type');
            }
            if (! Schema::hasColumn('fic_documents', 'paid_on')) {
                $table->date('paid_on')->nullable()->after('paid_amount');
                $table->index('paid_on');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('fic_documents')) {
            return;
        }

        Schema::table('fic_documents', function (Blueprint $table) {
            foreach (['category', 'paid_on'] as $column) {
                if (Schema::hasColumn('fic_documents', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
