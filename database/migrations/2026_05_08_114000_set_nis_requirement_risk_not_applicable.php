<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('document_frameworks') || ! Schema::hasTable('document_framework_requirements')) {
            return;
        }

        $frameworkIds = DB::table('document_frameworks')
            ->where('compliance_domain', 'nis2')
            ->pluck('id')
            ->all();

        foreach (array_chunk($frameworkIds, 500) as $chunk) {
            $query = DB::table('document_framework_requirements')
                ->whereIn('document_framework_id', $chunk);

            if (Schema::hasColumn('document_framework_requirements', 'deleted_at')) {
                $query->whereNull('deleted_at');
            }

            $query->update([
                'risk_level' => 'not_applicable',
            ]);
        }
    }

    public function down(): void
    {
        // Previous per-requirement risk values cannot be reconstructed safely.
    }
};
