<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    private const PACK_KEY = 'nis2_it_allegato_1';

    public function up(): void
    {
        if (! Schema::hasTable('document_types')) {
            return;
        }

        $this->seedGlobalDocumentTypes();
        $this->repairRequirementDefaultDocumentTypes();
        $this->repairExistingDocumentTypesFromPrimaryRequirements();
    }

    public function down(): void
    {
        // These defaults may be referenced by existing tenant documents and requirements.
    }

    private function seedGlobalDocumentTypes(): void
    {
        $now = Carbon::now();
        $adminUserId = $this->adminUserId();

        foreach ($this->defaultDocumentTypes() as $default) {
            $existing = DB::table('document_types')
                ->whereNull('company_id')
                ->where('name', $default['name'])
                ->first();

            if ($existing) {
                $updates = [
                    'slug' => $default['slug'],
                    'description' => $default['description'],
                    'sort_order' => $default['sort_order'],
                    'is_active' => true,
                    'visibility_type' => 'global',
                    'updated_at' => $now,
                ];

                if (Schema::hasColumn('document_types', 'deleted_at')) {
                    $updates['deleted_at'] = null;
                }

                DB::table('document_types')
                    ->where('id', $existing->id)
                    ->update($updates);

                continue;
            }

            DB::table('document_types')->insert([
                'name' => $default['name'],
                'slug' => $default['slug'],
                'description' => $default['description'],
                'sort_order' => $default['sort_order'],
                'is_active' => true,
                'created_by' => $adminUserId,
                'company_id' => null,
                'visibility_type' => 'global',
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    private function repairRequirementDefaultDocumentTypes(): void
    {
        if (! Schema::hasTable('document_frameworks') || ! Schema::hasTable('document_framework_requirements')) {
            return;
        }

        $requirementsByCode = $this->packDocumentTypeNamesByRequirementCode();

        if ($requirementsByCode->isEmpty()) {
            return;
        }

        $frameworks = DB::table('document_frameworks')
            ->whereNull('deleted_at')
            ->where('source_pack_key', self::PACK_KEY)
            ->select(['id', 'company_id'])
            ->get();

        foreach ($frameworks as $framework) {
            foreach ($requirementsByCode as $code => $documentTypeName) {
                $documentTypeId = $this->documentTypeIdForName($documentTypeName, $framework->company_id ? (int) $framework->company_id : null);

                if (! $documentTypeId) {
                    continue;
                }

                DB::table('document_framework_requirements')
                    ->whereNull('deleted_at')
                    ->where('document_framework_id', $framework->id)
                    ->where('code', $code)
                    ->whereNull('default_document_type_id')
                    ->update([
                        'default_document_type_id' => $documentTypeId,
                        'updated_at' => Carbon::now(),
                    ]);
            }
        }
    }

    private function repairExistingDocumentTypesFromPrimaryRequirements(): void
    {
        if (
            ! Schema::hasTable('documents')
            || ! Schema::hasTable('document_frameworks')
            || ! Schema::hasTable('document_framework_requirements')
            || ! Schema::hasTable('document_framework_requirement_document')
        ) {
            return;
        }

        $documentIds = DB::table('documents')
            ->join('document_framework_requirement_document as pivot', 'pivot.document_id', '=', 'documents.id')
            ->join('document_framework_requirements as requirements', 'requirements.id', '=', 'pivot.document_framework_requirement_id')
            ->join('document_frameworks as frameworks', 'frameworks.id', '=', 'requirements.document_framework_id')
            ->whereNull('documents.deleted_at')
            ->whereNull('documents.document_type_id')
            ->whereNull('requirements.deleted_at')
            ->whereNull('frameworks.deleted_at')
            ->where('frameworks.source_pack_key', self::PACK_KEY)
            ->where('pivot.coverage_role', 'primary')
            ->whereNotNull('requirements.default_document_type_id')
            ->distinct()
            ->pluck('documents.id');

        foreach ($documentIds as $documentId) {
            $document = DB::table('documents')
                ->where('id', $documentId)
                ->select(['id', 'name', 'company_id'])
                ->first();

            if (! $document) {
                continue;
            }

            $documentTypeId = $this->documentTypeIdForDocumentName(
                (string) $document->name,
                $document->company_id ? (int) $document->company_id : null,
            ) ?: DB::table('document_framework_requirement_document as pivot')
                ->join('document_framework_requirements as requirements', 'requirements.id', '=', 'pivot.document_framework_requirement_id')
                ->join('document_frameworks as frameworks', 'frameworks.id', '=', 'requirements.document_framework_id')
                ->where('pivot.document_id', $documentId)
                ->where('pivot.coverage_role', 'primary')
                ->whereNull('requirements.deleted_at')
                ->whereNull('frameworks.deleted_at')
                ->where('frameworks.source_pack_key', self::PACK_KEY)
                ->whereNotNull('requirements.default_document_type_id')
                ->groupBy('requirements.default_document_type_id')
                ->orderByDesc(DB::raw('COUNT(*)'))
                ->orderBy('requirements.default_document_type_id')
                ->value('requirements.default_document_type_id');

            if (! $documentTypeId) {
                continue;
            }

            DB::table('documents')
                ->where('id', $documentId)
                ->whereNull('document_type_id')
                ->update([
                    'document_type_id' => $documentTypeId,
                    'updated_at' => Carbon::now(),
                ]);
        }
    }

    private function packDocumentTypeNamesByRequirementCode()
    {
        $pack = config('compliance_frameworks.packs.'.self::PACK_KEY);

        if (! is_array($pack)) {
            return collect();
        }

        return collect($pack['requirements'] ?? [])
            ->mapWithKeys(function (array $requirement) {
                $code = $requirement['code'] ?? null;
                $documentTypeName = trim((string) ($requirement['default_document_type_name'] ?? ''));

                return ($code && $documentTypeName !== '') ? [$code => $documentTypeName] : [];
            });
    }

    private function documentTypeIdForName(string $name, ?int $companyId): ?int
    {
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

    private function documentTypeIdForDocumentName(string $documentName, ?int $companyId): ?int
    {
        $normalizedName = Str::lower(Str::ascii($documentName));

        $typeName = match (true) {
            str_contains($normalizedName, 'politic') || str_contains($normalizedName, 'policy') => 'Policy',
            str_contains($normalizedName, 'piano') || str_contains($normalizedName, 'plan') => 'Piano',
            str_contains($normalizedName, 'valutaz') || str_contains($normalizedName, 'assessment') => 'Valutazione',
            str_contains($normalizedName, 'procedur') => 'Procedura',
            str_contains($normalizedName, 'registro') || str_contains($normalizedName, 'register') => 'Registro',
            str_contains($normalizedName, 'inventar') || str_contains($normalizedName, 'inventory') => 'Inventario',
            str_contains($normalizedName, 'verbale') || str_contains($normalizedName, 'minutes') => 'Verbale',
            str_contains($normalizedName, 'nomina') || str_contains($normalizedName, 'organizzaz') || str_contains($normalizedName, 'organization') => 'Nomina',
            str_contains($normalizedName, 'informativ') || str_contains($normalizedName, 'notice') => 'Informativa',
            default => null,
        };

        return $typeName ? $this->documentTypeIdForName($typeName, $companyId) : null;
    }

    private function defaultDocumentTypes(): array
    {
        return [
            ['name' => 'Policy', 'slug' => 'policy', 'description' => 'Politiche aziendali o di controllo.', 'sort_order' => 10],
            ['name' => 'Procedura', 'slug' => 'procedura', 'description' => 'Procedure operative o di conformita.', 'sort_order' => 20],
            ['name' => 'Registro', 'slug' => 'registro', 'description' => 'Registri obbligatori o di controllo.', 'sort_order' => 30],
            ['name' => 'Valutazione', 'slug' => 'valutazione', 'description' => 'Assessment, analisi o valutazioni di rischio.', 'sort_order' => 40],
            ['name' => 'Piano', 'slug' => 'piano', 'description' => 'Piani di adeguamento, risposta o continuita.', 'sort_order' => 50],
            ['name' => 'Informativa', 'slug' => 'informativa', 'description' => 'Informative e comunicazioni formali.', 'sort_order' => 60],
            ['name' => 'Nomina', 'slug' => 'nomina', 'description' => 'Nomine, lettere di incarico e designazioni.', 'sort_order' => 70],
            ['name' => 'Verbale', 'slug' => 'verbale', 'description' => 'Verbali, registrazioni o consuntivi.', 'sort_order' => 80],
            ['name' => 'Evidenza', 'slug' => 'evidenza', 'description' => 'Evidenze documentali e prove di controllo.', 'sort_order' => 90],
            ['name' => 'Inventario', 'slug' => 'inventario', 'description' => 'Inventari, elenchi o repertori documentali.', 'sort_order' => 100],
        ];
    }

    private function adminUserId(): ?int
    {
        if (! Schema::hasTable('users')) {
            return null;
        }

        return DB::table('users')
            ->orderBy('id')
            ->value('id');
    }
};
