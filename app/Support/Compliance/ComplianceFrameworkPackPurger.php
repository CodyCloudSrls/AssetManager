<?php

namespace App\Support\Compliance;

use App\Models\ComplianceFrameworkPackEvent;
use App\Models\DocumentFramework;
use App\Models\DocumentFrameworkRequirement;
use App\Models\Tenant;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ComplianceFrameworkPackPurger
{
    public function tenantFrameworks(Tenant $tenant, string $packKey): Collection
    {
        $rootCompany = $tenant->rootCompany();

        if (! $rootCompany) {
            return collect();
        }

        return DocumentFramework::withoutGlobalScopes()
            ->where('company_id', $rootCompany->id)
            ->where('is_system_template', false)
            ->where('source_pack_key', $packKey)
            ->orderBy('id')
            ->get();
    }

    public function purgeableTenantFrameworks(Tenant $tenant, string $packKey): Collection
    {
        return $this->tenantFrameworks($tenant, $packKey)
            ->filter(fn (DocumentFramework $framework) => $this->canPurgeFramework($framework))
            ->values();
    }

    public function canPurgeTenantPack(Tenant $tenant, string $packKey): bool
    {
        return $this->purgeableTenantFrameworks($tenant, $packKey)->isNotEmpty();
    }

    public function canPurgeFramework(DocumentFramework $framework): bool
    {
        return $this->purgeBlockers($framework) === [];
    }

    public function purgeBlockers(DocumentFramework $framework): array
    {
        $blockers = [];
        $packKey = (string) $framework->source_pack_key;

        if ((bool) $framework->is_system_template || is_null($framework->company_id) || blank($packKey)) {
            $blockers[] = 'not_bootstrap_tenant_copy';
        } elseif (! $this->knownPack($packKey)) {
            $blockers[] = 'unknown_source_pack';
        }

        if ($this->directDocumentCount($framework) > 0) {
            $blockers[] = 'documents_linked_to_framework';
        }

        if ($this->requirementDocumentLinkCount($framework) > 0) {
            $blockers[] = 'documents_linked_to_requirements';
        }

        return $blockers;
    }

    public function purgeTenantPack(Tenant $tenant, string $packKey, array $pack, ?int $actorId = null): array
    {
        $rootCompany = $tenant->rootCompany();

        if (! $rootCompany) {
            return [
                'status' => 'skipped',
                'reason' => 'missing_root_company',
                'pack_key' => $packKey,
            ];
        }

        $frameworks = $this->tenantFrameworks($tenant, $packKey);

        if ($frameworks->isEmpty()) {
            return [
                'status' => 'skipped',
                'reason' => 'missing_framework',
                'pack_key' => $packKey,
            ];
        }

        $purgeableFrameworks = $frameworks
            ->filter(fn (DocumentFramework $framework) => $this->canPurgeFramework($framework))
            ->values();

        if ($purgeableFrameworks->isEmpty()) {
            return [
                'status' => 'blocked',
                'reason' => 'linked_documents',
                'pack_key' => $packKey,
                'blockers' => $frameworks
                    ->mapWithKeys(fn (DocumentFramework $framework) => [
                        $framework->id => $this->purgeBlockers($framework),
                    ])
                    ->all(),
            ];
        }

        $summary = $this->purgeFrameworkCollection($purgeableFrameworks);

        ComplianceFrameworkPackEvent::record(
            ComplianceFrameworkPackEvent::EVENT_TENANT_PURGE,
            ComplianceFrameworkPackEvent::SCOPE_TENANT,
            $packKey,
            $pack,
            [
                'tenant_id' => $tenant->id,
                'company_id' => $rootCompany->id,
                'actor_id' => $actorId,
                'result_summary' => $summary,
            ],
        );

        return [
            'status' => 'purged',
            'reason' => 'unused_bootstrap_copy',
            'pack_key' => $packKey,
            'summary' => $summary,
        ];
    }

    public function purgeFramework(DocumentFramework $framework, ?int $actorId = null): array
    {
        $framework = DocumentFramework::withoutGlobalScopes()->find($framework->id);

        if (! $framework) {
            return [
                'status' => 'skipped',
                'reason' => 'missing_framework',
            ];
        }

        $packKey = (string) $framework->source_pack_key;
        $blockers = $this->purgeBlockers($framework);

        if ($blockers !== []) {
            return [
                'status' => 'blocked',
                'reason' => 'linked_documents',
                'pack_key' => $packKey,
                'blockers' => [
                    $framework->id => $blockers,
                ],
            ];
        }

        $company = $framework->company()->withoutGlobalScopes()->first();
        $tenant = $company?->tenant;
        $pack = config("compliance_frameworks.packs.{$packKey}", []);
        $summary = $this->purgeFrameworkCollection(collect([$framework]));

        if ($tenant && is_array($pack)) {
            ComplianceFrameworkPackEvent::record(
                ComplianceFrameworkPackEvent::EVENT_TENANT_PURGE,
                ComplianceFrameworkPackEvent::SCOPE_TENANT,
                $packKey,
                $pack,
                [
                    'tenant_id' => $tenant->id,
                    'company_id' => $company?->id,
                    'actor_id' => $actorId,
                    'result_summary' => $summary,
                ],
            );
        }

        return [
            'status' => 'purged',
            'reason' => 'unused_bootstrap_copy',
            'pack_key' => $packKey,
            'summary' => $summary,
        ];
    }

    private function purgeFrameworkCollection(Collection $frameworks): array
    {
        return DB::transaction(function () use ($frameworks) {
            $summary = [
                'frameworks_purged' => 0,
                'requirements_purged' => 0,
                'framework_ids' => [],
                'source_pack_keys' => [],
            ];

            foreach ($frameworks as $framework) {
                $requirementIds = DocumentFrameworkRequirement::withoutGlobalScopes()
                    ->where('document_framework_id', $framework->id)
                    ->pluck('id')
                    ->map(fn ($id) => (int) $id)
                    ->all();

                if ($requirementIds !== []) {
                    if (Schema::hasTable('document_framework_requirement_parents')) {
                        DB::table('document_framework_requirement_parents')
                            ->whereIn('child_requirement_id', $requirementIds)
                            ->orWhereIn('parent_requirement_id', $requirementIds)
                            ->delete();
                    }

                    if (Schema::hasTable('document_framework_requirement_document')) {
                        DB::table('document_framework_requirement_document')
                            ->whereIn('document_framework_requirement_id', $requirementIds)
                            ->delete();
                    }

                    DocumentFrameworkRequirement::withoutGlobalScopes()
                        ->whereIn('id', $requirementIds)
                        ->forceDelete();
                }

                $summary['requirements_purged'] += count($requirementIds);
                $summary['frameworks_purged']++;
                $summary['framework_ids'][] = (int) $framework->id;
                $summary['source_pack_keys'][] = (string) $framework->source_pack_key;

                $framework->forceDelete();
            }

            $summary['source_pack_keys'] = array_values(array_unique($summary['source_pack_keys']));

            return $summary;
        });
    }

    private function directDocumentCount(DocumentFramework $framework): int
    {
        if (! Schema::hasTable('documents')) {
            return 0;
        }

        return DB::table('documents')
            ->where('document_framework_id', $framework->id)
            ->count();
    }

    private function requirementDocumentLinkCount(DocumentFramework $framework): int
    {
        if (! Schema::hasTable('document_framework_requirements') || ! Schema::hasTable('document_framework_requirement_document')) {
            return 0;
        }

        return DB::table('document_framework_requirements')
            ->join(
                'document_framework_requirement_document',
                'document_framework_requirement_document.document_framework_requirement_id',
                '=',
                'document_framework_requirements.id'
            )
            ->where('document_framework_requirements.document_framework_id', $framework->id)
            ->count();
    }

    private function knownPack(string $packKey): bool
    {
        return array_key_exists($packKey, config('compliance_frameworks.packs', []));
    }
}
