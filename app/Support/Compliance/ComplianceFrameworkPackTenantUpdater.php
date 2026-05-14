<?php

namespace App\Support\Compliance;

use App\Models\ComplianceFrameworkPackEvent;
use App\Models\Tenant;

class ComplianceFrameworkPackTenantUpdater
{
    public function __construct(
        private ComplianceFrameworkInstaller $installer,
        private ComplianceFrameworkPackSync $sync,
    ) {
    }

    public function applyAvailablePacks(Tenant $tenant, ?int $actorId = null): array
    {
        $locale = $this->installer->bootstrapLocale($tenant->defaultLocale());
        $packKeys = $this->installer->availablePackKeys($locale, $tenant->defaultComplianceJurisdiction());
        $summary = $this->emptySummary();

        foreach ($packKeys as $packKey) {
            $pack = config("compliance_frameworks.packs.{$packKey}");

            if (! is_array($pack)) {
                $this->countResult($summary, [
                    'status' => 'skipped',
                    'reason' => 'missing_pack',
                    'pack_key' => $packKey,
                ]);

                continue;
            }

            $this->countResult($summary, $this->applyPack($tenant, $packKey, $pack, $actorId));
        }

        return $summary;
    }

    public function applyPack(Tenant $tenant, string $packKey, array $pack, ?int $actorId = null): array
    {
        $rootCompany = $tenant->rootCompany();

        if (! $rootCompany) {
            return [
                'status' => 'skipped',
                'reason' => 'missing_root_company',
                'pack_key' => $packKey,
            ];
        }

        $tenantLocale = $this->installer->bootstrapLocale($tenant->defaultLocale());
        $compatiblePackKeys = $this->installer->availablePackKeys($tenantLocale, $tenant->defaultComplianceJurisdiction());

        if (($pack['locale'] ?? null) !== $tenantLocale) {
            return [
                'status' => 'skipped',
                'reason' => 'locale_mismatch',
                'pack_key' => $packKey,
            ];
        }

        if (! in_array($packKey, $compatiblePackKeys, true)) {
            return [
                'status' => 'skipped',
                'reason' => 'jurisdiction_mismatch',
                'pack_key' => $packKey,
            ];
        }

        $framework = $this->sync->tenantFramework($tenant, $packKey, $pack);
        $before = $this->sync->diff($framework, $packKey, $pack);

        if (! $this->canApplyTenantDiff($before)) {
            return [
                'status' => $before['status'] === 'current' ? 'current' : 'manual_review',
                'reason' => $before['status'] === 'current' ? 'current' : 'conflicts_or_custom_changes',
                'pack_key' => $packKey,
                'diff_before' => $before,
            ];
        }

        if ($before['framework_missing']) {
            $summary = $this->installer->bootstrapTenant($tenant, $tenantLocale, [$packKey], false, $actorId);
            $framework = $this->sync->tenantFramework($tenant, $packKey, $pack);
            $after = $this->sync->diff($framework, $packKey, $pack);
            $eventType = ComplianceFrameworkPackEvent::EVENT_TENANT_BOOTSTRAP;
            $status = 'bootstrapped';
        } else {
            $merge = $this->sync->mergeMissingRequirements($framework, $packKey, $pack, $actorId);
            $summary = [
                'created' => 0,
                'updated' => 0,
                'skipped' => 0,
                'requirements_created' => $merge['requirements_created'],
                'requirements_updated' => 0,
                'requirements_skipped' => 0,
                'metadata_updated' => $merge['metadata_updated'],
                'conflicts_count' => $merge['conflicts_count'],
            ];
            $framework->refresh();
            $after = $this->sync->diff($framework, $packKey, $pack);
            $eventType = ComplianceFrameworkPackEvent::EVENT_TENANT_SYNC;
            $status = 'synced';
        }

        ComplianceFrameworkPackEvent::record(
            $eventType,
            ComplianceFrameworkPackEvent::SCOPE_TENANT,
            $packKey,
            $pack,
            [
                'tenant_id' => $tenant->id,
                'company_id' => $rootCompany->id,
                'document_framework_id' => $framework?->id,
                'diff_before' => $before,
                'diff_after' => $after,
                'result_summary' => $summary,
            ],
        );

        return [
            'status' => $status,
            'reason' => 'applied',
            'pack_key' => $packKey,
            'summary' => $summary,
            'diff_before' => $before,
            'diff_after' => $after,
        ];
    }

    public function emptySummary(): array
    {
        return [
            'checked' => 0,
            'applied' => 0,
            'bootstrapped' => 0,
            'synced' => 0,
            'current' => 0,
            'manual_review' => 0,
            'skipped' => 0,
            'frameworks_created' => 0,
            'requirements_created' => 0,
            'metadata_updated' => 0,
            'details' => [],
        ];
    }

    public function countResult(array &$summary, array $result): void
    {
        $summary['checked']++;
        $summary['details'][] = $result;

        match ($result['status'] ?? null) {
            'bootstrapped' => $this->countApplied($summary, $result, 'bootstrapped'),
            'synced' => $this->countApplied($summary, $result, 'synced'),
            'current' => $summary['current']++,
            'manual_review' => $summary['manual_review']++,
            default => $summary['skipped']++,
        };
    }

    private function countApplied(array &$summary, array $result, string $status): void
    {
        $summary['applied']++;
        $summary[$status]++;
        $summary['frameworks_created'] += (int) data_get($result, 'summary.created', 0);
        $summary['requirements_created'] += (int) data_get($result, 'summary.requirements_created', 0);
        $summary['metadata_updated'] += (int) data_get($result, 'summary.metadata_updated', 0);
    }

    private function canApplyTenantDiff(array $diff): bool
    {
        if ($diff['framework_missing']) {
            return true;
        }

        if ((int) $diff['conflicts_count'] > 0) {
            return false;
        }

        if (count($diff['missing_requirements']) > 0) {
            return true;
        }

        return $diff['status'] === 'outdated';
    }
}
