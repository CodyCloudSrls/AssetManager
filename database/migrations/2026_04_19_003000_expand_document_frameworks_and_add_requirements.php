<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('document_frameworks', function (Blueprint $table) {
            if (! Schema::hasColumn('document_frameworks', 'authority_name')) {
                $table->string('authority_name')->nullable()->after('description');
            }
            if (! Schema::hasColumn('document_frameworks', 'framework_code')) {
                $table->string('framework_code', 80)->nullable()->after('authority_name');
            }
            if (! Schema::hasColumn('document_frameworks', 'framework_type')) {
                $table->string('framework_type', 40)->nullable()->after('framework_code');
            }
            if (! Schema::hasColumn('document_frameworks', 'jurisdiction')) {
                $table->string('jurisdiction', 80)->nullable()->after('framework_type');
            }
            if (! Schema::hasColumn('document_frameworks', 'version')) {
                $table->string('version', 80)->nullable()->after('jurisdiction');
            }
            if (! Schema::hasColumn('document_frameworks', 'effective_from')) {
                $table->date('effective_from')->nullable()->after('version');
            }
            if (! Schema::hasColumn('document_frameworks', 'effective_to')) {
                $table->date('effective_to')->nullable()->after('effective_from');
            }
            if (! Schema::hasColumn('document_frameworks', 'owner_id')) {
                $table->unsignedInteger('owner_id')->nullable()->after('effective_to');
            }
            if (! Schema::hasColumn('document_frameworks', 'review_cadence_months')) {
                $table->unsignedSmallInteger('review_cadence_months')->nullable()->after('owner_id');
            }
            if (! Schema::hasColumn('document_frameworks', 'status')) {
                $table->string('status', 32)->default('active')->after('review_cadence_months');
            }
            if (! Schema::hasColumn('document_frameworks', 'external_reference_url')) {
                $table->string('external_reference_url', 2048)->nullable()->after('status');
            }
        });

        Schema::create('document_framework_requirements', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('document_framework_id')->index();
            $table->unsignedInteger('parent_id')->nullable()->index();
            $table->string('code', 100);
            $table->string('title');
            $table->string('domain', 120)->nullable();
            $table->boolean('is_mandatory')->default(true);
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('owner_id')->nullable()->index();
            $table->unsignedInteger('default_document_type_id')->nullable()->index();
            $table->unsignedSmallInteger('review_frequency_months')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->text('description')->nullable();
            $table->text('evidence_guidance')->nullable();
            $table->text('applicability_notes')->nullable();
            $table->unsignedInteger('created_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('document_framework_id', 'doc_framework_requirements_framework_fk')
                ->references('id')->on('document_frameworks')->cascadeOnDelete();
            $table->foreign('parent_id', 'doc_framework_requirements_parent_fk')
                ->references('id')->on('document_framework_requirements')->nullOnDelete();
            $table->foreign('owner_id', 'doc_framework_requirements_owner_fk')
                ->references('id')->on('users')->nullOnDelete();
            $table->foreign('default_document_type_id', 'doc_framework_requirements_doc_type_fk')
                ->references('id')->on('document_types')->nullOnDelete();

            $table->index(['document_framework_id', 'is_active', 'deleted_at'], 'doc_framework_requirements_framework_active_deleted_idx');
        });

        Schema::create('document_framework_requirement_document', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('document_framework_requirement_id')->index('doc_req_doc_requirement_idx');
            $table->unsignedInteger('document_id')->index('doc_req_doc_document_idx');
            $table->string('coverage_role', 20)->default('primary');
            $table->text('notes')->nullable();
            $table->timestamp('covered_at')->nullable();
            $table->unsignedInteger('created_by')->nullable();
            $table->timestamps();

            $table->foreign('document_framework_requirement_id', 'doc_req_doc_requirement_fk')
                ->references('id')->on('document_framework_requirements')->cascadeOnDelete();
            $table->foreign('document_id', 'doc_req_doc_document_fk')
                ->references('id')->on('documents')->cascadeOnDelete();
            $table->unique(['document_framework_requirement_id', 'document_id'], 'doc_req_doc_unique');
        });

        DB::table('document_frameworks')
            ->whereNull('deleted_at')
            ->update([
                'status' => DB::raw("COALESCE(status, 'active')"),
            ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('document_framework_requirement_document');
        Schema::dropIfExists('document_framework_requirements');

        Schema::table('document_frameworks', function (Blueprint $table) {
            foreach ([
                'authority_name',
                'framework_code',
                'framework_type',
                'jurisdiction',
                'version',
                'effective_from',
                'effective_to',
                'owner_id',
                'review_cadence_months',
                'status',
                'external_reference_url',
            ] as $column) {
                if (Schema::hasColumn('document_frameworks', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
