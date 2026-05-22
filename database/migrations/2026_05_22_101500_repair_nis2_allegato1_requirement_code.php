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
            || ! Schema::hasTable('document_framework_requirements')
            || ! Schema::hasTable('document_framework_requirement_parents')
            || ! Schema::hasColumn('document_frameworks', 'source_pack_key')
        ) {
            return;
        }

        $expected = collect(require base_path('app/Support/Compliance/Packs/nis2_it_allegato_1.php'))
            ->firstWhere('code', 'ID.RA-08 punto 5');

        if (! is_array($expected)) {
            return;
        }

        $frameworks = DB::table('document_frameworks')
            ->where('source_pack_key', 'nis2_it_allegato_1')
            ->whereNull('deleted_at')
            ->select(['id', 'company_id'])
            ->get();

        foreach ($frameworks as $framework) {
            $hasCorrectRequirement = DB::table('document_framework_requirements')
                ->where('document_framework_id', $framework->id)
                ->where('code', 'ID.RA-08 punto 5')
                ->whereNull('deleted_at')
                ->exists();

            if ($hasCorrectRequirement) {
                continue;
            }

            $legacyRequirement = DB::table('document_framework_requirements')
                ->where('document_framework_id', $framework->id)
                ->where('code', 'ID.RA-08 punto 4')
                ->where('title', 'Approvazione del piano di gestione delle vulnerabilità')
                ->whereNull('deleted_at')
                ->first();

            if (! $legacyRequirement) {
                continue;
            }

            $columns = collect(Schema::getColumnListing('document_framework_requirements'))->flip();
            $updates = collect([
                'code' => $expected['code'] ?? 'ID.RA-08 punto 5',
                'title' => $expected['title'] ?? null,
                'domain' => $expected['domain'] ?? null,
                'obligation_type' => $expected['obligation_type'] ?? null,
                'evidence_type' => $expected['evidence_type'] ?? null,
                'delegation_level' => $expected['delegation_level'] ?? null,
                'risk_level' => $expected['risk_level'] ?? 'not_applicable',
                'official_reference' => $expected['official_reference'] ?? null,
                'source_url' => $expected['source_url'] ?? null,
                'review_frequency_months' => $expected['review_frequency_months'] ?? null,
                'description' => $expected['description'] ?? null,
                'evidence_guidance' => $expected['evidence_guidance'] ?? null,
                'applicability_notes' => $expected['applicability_notes'] ?? null,
                'is_mandatory' => $expected['is_mandatory'] ?? true,
                'is_active' => $expected['is_active'] ?? true,
                'sort_order' => $expected['sort_order'] ?? null,
                'minimum_required_documents' => $expected['minimum_required_documents'] ?? 1,
                'updated_at' => now(),
            ])
                ->filter(fn ($value, string $column) => $columns->has($column))
                ->all();

            if ($columns->has('default_document_type_id')) {
                $documentTypeId = $this->documentTypeIdForName(
                    (string) ($expected['default_document_type_name'] ?? ''),
                    $framework->company_id ? (int) $framework->company_id : null,
                );

                if ($documentTypeId) {
                    $updates['default_document_type_id'] = $documentTypeId;
                }
            }

            DB::table('document_framework_requirements')
                ->where('id', $legacyRequirement->id)
                ->update($updates);
        }
    }

    public function down(): void
    {
        // The previous value came from an incorrect bootstrap source and is not restored.
    }

    private function documentTypeIdForName(string $name, ?int $companyId): ?int
    {
        $name = trim($name);

        if ($name === '' || ! Schema::hasTable('document_types')) {
            return null;
        }

        return DB::table('document_types')
            ->whereNull('deleted_at')
            ->where('name', $name)
            ->where(function ($query) use ($companyId) {
                if ($companyId) {
                    $query->where('company_id', $companyId)
                        ->orWhereNull('company_id');
                } else {
                    $query->whereNull('company_id');
                }
            })
            ->orderByRaw('CASE WHEN company_id IS NULL THEN 1 ELSE 0 END')
            ->value('id');
    }
};
