<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('document_framework_requirements') && ! Schema::hasColumn('document_framework_requirements', 'minimum_required_documents')) {
            Schema::table('document_framework_requirements', function (Blueprint $table) {
                $table->unsignedSmallInteger('minimum_required_documents')->default(1)->after('default_document_type_id');
            });
        }

        if (Schema::hasTable('document_framework_requirements') && Schema::hasColumn('document_framework_requirements', 'minimum_required_documents')) {
            DB::table('document_framework_requirements')
                ->whereNull('minimum_required_documents')
                ->update(['minimum_required_documents' => 1]);
        }

        if (Schema::hasTable('document_frameworks') && Schema::hasColumn('document_frameworks', 'is_system_template')) {
            DB::table('document_frameworks')
                ->whereNull('company_id')
                ->where(function ($query) {
                    $query->where('is_system_template', true);

                    if (Schema::hasColumn('document_frameworks', 'source_pack_key')) {
                        $query->orWhereNotNull('source_pack_key');
                    }
                })
                ->delete();
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('document_framework_requirements') && Schema::hasColumn('document_framework_requirements', 'minimum_required_documents')) {
            Schema::table('document_framework_requirements', function (Blueprint $table) {
                $table->dropColumn('minimum_required_documents');
            });
        }
    }
};
