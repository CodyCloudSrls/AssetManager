<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('suppliers', function (Blueprint $table) {
            if (! Schema::hasColumn('suppliers', 'nis_relevance_type')) {
                $table->string('nis_relevance_type', 30)->default('not_assessed')->after('nis_relevant');
            }
        });

        if (Schema::hasColumn('suppliers', 'nis_relevance_type')) {
            DB::table('suppliers')
                ->whereNull('nis_relevance_type')
                ->orWhere('nis_relevance_type', '')
                ->update(['nis_relevance_type' => 'not_assessed']);
        }
    }

    public function down(): void
    {
        Schema::table('suppliers', function (Blueprint $table) {
            if (Schema::hasColumn('suppliers', 'nis_relevance_type')) {
                $table->dropColumn('nis_relevance_type');
            }
        });
    }
};
