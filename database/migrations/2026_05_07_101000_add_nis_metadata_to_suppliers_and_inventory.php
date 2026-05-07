<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('suppliers', function (Blueprint $table) {
            if (! Schema::hasColumn('suppliers', 'nis_relevant')) {
                $table->boolean('nis_relevant')->default(false)->after('visibility_type');
            }

            if (! Schema::hasColumn('suppliers', 'nis_criticality')) {
                $table->string('nis_criticality', 30)->default('not_assessed')->after('nis_relevant');
            }

            if (! Schema::hasColumn('suppliers', 'nis_assessment_status')) {
                $table->string('nis_assessment_status', 30)->default('not_started')->after('nis_criticality');
            }

            if (! Schema::hasColumn('suppliers', 'nis_relevance_criteria')) {
                $table->text('nis_relevance_criteria')->nullable()->after('nis_assessment_status');
            }

            if (! Schema::hasColumn('suppliers', 'cpv_codes')) {
                $table->text('cpv_codes')->nullable()->after('nis_relevance_criteria');
            }

            if (! Schema::hasColumn('suppliers', 'nis_last_assessment_at')) {
                $table->date('nis_last_assessment_at')->nullable()->after('cpv_codes');
            }

            if (! Schema::hasColumn('suppliers', 'nis_next_review_at')) {
                $table->date('nis_next_review_at')->nullable()->after('nis_last_assessment_at');
            }
        });

        Schema::table('categories', function (Blueprint $table) {
            if (! Schema::hasColumn('categories', 'nis_inventory_required')) {
                $table->boolean('nis_inventory_required')->default(false)->after('visibility_type');
            }

            if (! Schema::hasColumn('categories', 'nis_inventory_scope')) {
                $table->string('nis_inventory_scope', 40)->nullable()->after('nis_inventory_required');
            }
        });

        Schema::table('assets', function (Blueprint $table) {
            if (! Schema::hasColumn('assets', 'nis_relevant')) {
                $table->boolean('nis_relevant')->default(false)->after('requestable');
            }

            if (! Schema::hasColumn('assets', 'nis_inventory_scope')) {
                $table->string('nis_inventory_scope', 40)->nullable()->after('nis_relevant');
            }

            if (! Schema::hasColumn('assets', 'nis_service_impact')) {
                $table->string('nis_service_impact', 30)->default('unknown')->after('nis_inventory_scope');
            }

            if (! Schema::hasColumn('assets', 'nis_notes')) {
                $table->text('nis_notes')->nullable()->after('nis_service_impact');
            }
        });
    }

    public function down(): void
    {
        Schema::table('assets', function (Blueprint $table) {
            foreach (['nis_relevant', 'nis_inventory_scope', 'nis_service_impact', 'nis_notes'] as $column) {
                if (Schema::hasColumn('assets', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        Schema::table('categories', function (Blueprint $table) {
            foreach (['nis_inventory_required', 'nis_inventory_scope'] as $column) {
                if (Schema::hasColumn('categories', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        Schema::table('suppliers', function (Blueprint $table) {
            foreach ([
                'nis_relevant',
                'nis_criticality',
                'nis_assessment_status',
                'nis_relevance_criteria',
                'cpv_codes',
                'nis_last_assessment_at',
                'nis_next_review_at',
            ] as $column) {
                if (Schema::hasColumn('suppliers', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
