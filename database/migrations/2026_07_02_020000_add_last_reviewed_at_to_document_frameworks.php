<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tracks when a compliance framework was last reviewed, so the periodic review reminder
 * (review_cadence_months) has an anchor. Additive/nullable — existing data untouched;
 * frameworks with no value fall back to created_at for the first cycle.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('document_frameworks', function (Blueprint $table) {
            if (! Schema::hasColumn('document_frameworks', 'last_reviewed_at')) {
                $table->date('last_reviewed_at')->nullable()->after('review_cadence_months');
            }
        });
    }

    public function down(): void
    {
        Schema::table('document_frameworks', function (Blueprint $table) {
            if (Schema::hasColumn('document_frameworks', 'last_reviewed_at')) {
                $table->dropColumn('last_reviewed_at');
            }
        });
    }
};
