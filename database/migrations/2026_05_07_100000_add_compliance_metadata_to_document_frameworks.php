<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('document_frameworks', function (Blueprint $table) {
            if (! Schema::hasColumn('document_frameworks', 'compliance_domain')) {
                $table->string('compliance_domain', 40)->nullable()->after('framework_type');
            }

            if (! Schema::hasColumn('document_frameworks', 'compliance_objective')) {
                $table->text('compliance_objective')->nullable()->after('description');
            }
        });

        Schema::table('document_framework_requirements', function (Blueprint $table) {
            if (! Schema::hasColumn('document_framework_requirements', 'obligation_type')) {
                $table->string('obligation_type', 60)->nullable()->after('domain');
            }

            if (! Schema::hasColumn('document_framework_requirements', 'evidence_type')) {
                $table->string('evidence_type', 60)->nullable()->after('default_document_type_id');
            }

            if (! Schema::hasColumn('document_framework_requirements', 'delegation_level')) {
                $table->string('delegation_level', 40)->default('owner_review')->after('evidence_type');
            }

            if (! Schema::hasColumn('document_framework_requirements', 'risk_level')) {
                $table->string('risk_level', 20)->default('medium')->after('delegation_level');
            }

            if (! Schema::hasColumn('document_framework_requirements', 'official_reference')) {
                $table->string('official_reference')->nullable()->after('risk_level');
            }

            if (! Schema::hasColumn('document_framework_requirements', 'source_url')) {
                $table->string('source_url', 2048)->nullable()->after('official_reference');
            }
        });
    }

    public function down(): void
    {
        Schema::table('document_framework_requirements', function (Blueprint $table) {
            foreach ([
                'obligation_type',
                'evidence_type',
                'delegation_level',
                'risk_level',
                'official_reference',
                'source_url',
            ] as $column) {
                if (Schema::hasColumn('document_framework_requirements', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        Schema::table('document_frameworks', function (Blueprint $table) {
            foreach (['compliance_domain', 'compliance_objective'] as $column) {
                if (Schema::hasColumn('document_frameworks', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
