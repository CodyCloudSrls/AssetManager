<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use App\Support\Compliance\ComplianceFrameworkInstaller;
use App\Support\Compliance\ComplianceFrameworkPackSync;
use Illuminate\Console\Command;

class SyncComplianceFrameworkPacks extends Command
{
    protected $signature = 'snipeit:sync-compliance-framework-packs
        {pack=all : all, a configured pack key, or a comma-separated list}
        {--tenant_id= : Optional tenant id. Without it, system templates are checked}
        {--apply : Apply non-destructive merge changes}';

    protected $description = 'Diff compliance framework packs and safely merge missing tenant requirements without overwriting tenant edits.';

    public function handle(): int
    {
        $packs = config('compliance_frameworks.packs', []);
        $installer = app(ComplianceFrameworkInstaller::class);
        $sync = app(ComplianceFrameworkPackSync::class);
        $tenant = $this->option('tenant_id')
            ? Tenant::query()->findOrFail((int) $this->option('tenant_id'))
            : null;
        $apply = (bool) $this->option('apply');
        $locale = $tenant ? $installer->bootstrapLocale($tenant->defaultLocale()) : null;
        $availablePackKeys = $tenant ? $installer->availablePackKeys($locale) : array_keys($packs);
        $selected = $this->selectedPacks($availablePackKeys);

        foreach ($selected as $packKey) {
            if (! isset($packs[$packKey])) {
                $this->error("Unknown compliance pack: {$packKey}");

                return self::FAILURE;
            }

            if ($tenant && ($packs[$packKey]['locale'] ?? null) !== $locale) {
                $this->error("Pack {$packKey} is not available for tenant locale {$locale}.");

                return self::FAILURE;
            }
        }

        if ($tenant && ! $tenant->rootCompany()) {
            $this->error("Tenant {$tenant->id} has no root company.");

            return self::FAILURE;
        }

        foreach ($selected as $packKey) {
            $pack = $packs[$packKey];

            if ($tenant) {
                $this->handleTenantPack($sync, $installer, $tenant, $packKey, $pack, $apply);
            } else {
                $this->handleSystemPack($sync, $installer, $packKey, $pack, $apply);
            }
        }

        return self::SUCCESS;
    }

    private function handleTenantPack(ComplianceFrameworkPackSync $sync, ComplianceFrameworkInstaller $installer, Tenant $tenant, string $packKey, array $pack, bool $apply): void
    {
        $framework = $sync->tenantFramework($tenant, $packKey, $pack);
        $diff = $sync->diff($framework, $packKey, $pack);

        if ($apply && $diff['framework_missing']) {
            $summary = $installer->bootstrapTenant(
                $tenant,
                $tenant->defaultLocale(),
                [$packKey],
                false,
                auth()->id(),
            );
            $framework = $sync->tenantFramework($tenant, $packKey, $pack);
            $diff = $sync->diff($framework, $packKey, $pack);

            $this->line($this->formatLine('tenant '.$tenant->id, $diff).' created_frameworks='.($summary['created'] ?? 0));

            return;
        }

        if ($apply && ! $diff['framework_missing']) {
            $merge = $sync->mergeMissingRequirements($framework, $packKey, $pack, auth()->id());
            $this->line($this->formatLine('tenant '.$tenant->id, $merge['after']).' requirements_created='.$merge['requirements_created'].' metadata_updated='.(int) $merge['metadata_updated']);

            return;
        }

        $this->line($this->formatLine('tenant '.$tenant->id, $diff));
        $this->printDiffDetails($diff);
    }

    private function handleSystemPack(ComplianceFrameworkPackSync $sync, ComplianceFrameworkInstaller $installer, string $packKey, array $pack, bool $apply): void
    {
        $framework = $sync->systemFramework($packKey, $pack);
        $diff = $sync->diff($framework, $packKey, $pack);

        if ($apply) {
            $summary = $installer->installSystemPack($packKey, $pack, true, auth()->id());
            $framework = $sync->systemFramework($packKey, $pack);
            $diff = $sync->diff($framework, $packKey, $pack);

            $this->line($this->formatLine('system', $diff).' '.$this->formatSummary($summary));

            return;
        }

        $this->line($this->formatLine('system', $diff));
        $this->printDiffDetails($diff);
    }

    private function selectedPacks(array $available): array
    {
        $requested = (string) $this->argument('pack');

        if ($requested === 'all') {
            return $available;
        }

        return array_values(array_filter(array_map('trim', explode(',', $requested))));
    }

    private function formatLine(string $scope, array $diff): string
    {
        return sprintf(
            '[%s] %s status=%s source_version=%s pack_version=%s framework_changes=%d missing_requirements=%d changed_requirements=%d custom_requirements=%d conflicts=%d',
            $scope,
            $diff['pack_key'],
            $diff['status'],
            $diff['source_pack_version'] ?: '-',
            $diff['pack_version'] ?: '-',
            count($diff['framework_changes']),
            count($diff['missing_requirements']),
            count($diff['changed_requirements']),
            count($diff['custom_requirements']),
            $diff['conflicts_count'],
        );
    }

    private function printDiffDetails(array $diff): void
    {
        if (count($diff['framework_changes']) > 0) {
            $this->line('  framework fields: '.implode(', ', array_keys($diff['framework_changes'])));
        }

        if (count($diff['missing_requirements']) > 0) {
            $this->line('  missing requirements: '.implode(', ', $diff['missing_requirements']));
        }

        if (count($diff['changed_requirements']) > 0) {
            $this->line('  changed requirements: '.implode(', ', array_keys($diff['changed_requirements'])));
        }

        if (count($diff['custom_requirements']) > 0) {
            $this->line('  custom requirements: '.implode(', ', $diff['custom_requirements']));
        }
    }

    private function formatSummary(array $summary): string
    {
        return sprintf(
            'frameworks created:%d updated:%d skipped:%d; requirements created:%d updated:%d skipped:%d',
            $summary['created'] ?? 0,
            $summary['updated'] ?? 0,
            $summary['skipped'] ?? 0,
            $summary['requirements_created'] ?? 0,
            $summary['requirements_updated'] ?? 0,
            $summary['requirements_skipped'] ?? 0,
        );
    }
}
