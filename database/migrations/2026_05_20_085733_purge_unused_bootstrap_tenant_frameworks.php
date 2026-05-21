<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (
            ! Schema::hasTable('document_frameworks')
            || ! Schema::hasTable('documents')
            || ! Schema::hasTable('document_framework_requirements')
            || ! Schema::hasTable('document_framework_requirement_document')
            || ! Schema::hasColumn('document_frameworks', 'source_pack_key')
            || ! Schema::hasColumn('document_frameworks', 'company_id')
        ) {
            return;
        }

        $candidateQuery = DB::table('document_frameworks')
            ->whereNotNull('document_frameworks.company_id')
            ->whereNotNull('document_frameworks.source_pack_key')
            ->whereNull('document_frameworks.deleted_at')
            ->whereNotExists(function ($query) {
                $query->selectRaw('1')
                    ->from('documents')
                    ->whereColumn('documents.document_framework_id', 'document_frameworks.id');
            })
            ->whereNotExists(function ($query) {
                $query->selectRaw('1')
                    ->from('document_framework_requirements')
                    ->join(
                        'document_framework_requirement_document',
                        'document_framework_requirement_document.document_framework_requirement_id',
                        '=',
                        'document_framework_requirements.id'
                    )
                    ->whereColumn('document_framework_requirements.document_framework_id', 'document_frameworks.id');
            });

        if (Schema::hasColumn('document_frameworks', 'is_system_template')) {
            $candidateQuery->where('document_frameworks.is_system_template', false);
        }

        $candidateQuery
            ->orderBy('document_frameworks.id')
            ->select('document_frameworks.id')
            ->chunkById(200, function ($frameworks) {
                $frameworkIds = $frameworks->pluck('id')->map(fn ($id) => (int) $id)->all();

                if ($frameworkIds === []) {
                    return;
                }

                DB::table('document_frameworks')
                    ->whereIn('id', $frameworkIds)
                    ->delete();
            }, 'document_frameworks.id', 'id');
    }

    public function down(): void
    {
        // Purged bootstrap-generated tenant copies cannot be reconstructed safely.
    }
};
