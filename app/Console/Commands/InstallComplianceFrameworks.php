<?php

namespace App\Console\Commands;

use App\Models\Company;
use App\Models\Tenant;
use App\Support\Compliance\ComplianceFrameworkInstaller;
use Illuminate\Console\Command;
use Illuminate\Support\Arr;

class InstallComplianceFrameworks extends Command
{
    protected $signature = 'snipeit:install-compliance-frameworks
        {pack=all : all, nis2_it, nis2_en, gdpr_eu, gdpr_en, or a comma-separated list}
        {--company_id= : Optional company id for tenant-owned frameworks}
        {--tenant_id= : Optional tenant id. Uses the tenant root company and tenant default language}
        {--visibility=global : private, descendants, or global}
        {--update-existing : Update existing frameworks and requirements}
        {--dry-run : Show changes without writing}';

    protected $description = 'Install starter compliance document frameworks for NIS2/GDPR without overwriting existing data by default.';

    public function handle(): int
    {
        $packs = config('compliance_frameworks.packs', []);
        $installer = app(ComplianceFrameworkInstaller::class);
        $tenant = $this->option('tenant_id') ? Tenant::query()->findOrFail((int) $this->option('tenant_id')) : null;
        $availablePackKeys = $tenant
            ? $installer->availablePackKeys($tenant->defaultLocale())
            : array_keys($packs);
        $selected = $this->selectedPacks($availablePackKeys);

        foreach ($selected as $packKey) {
            if (! isset($packs[$packKey])) {
                $this->error("Unknown compliance pack: {$packKey}");

                return self::FAILURE;
            }
        }

        if ($tenant) {
            $locale = $installer->bootstrapLocale($tenant->defaultLocale());

            if ((bool) $this->option('dry-run')) {
                $this->line("[dry-run] would bootstrap tenant {$tenant->display_name} using {$locale}: ".implode(', ', $selected));

                return self::SUCCESS;
            }

            $summary = $installer->bootstrapTenant(
                $tenant,
                $locale,
                $selected,
                (bool) $this->option('update-existing'),
                auth()->id(),
            );

            $this->line($this->formatSummary($summary));

            return self::SUCCESS;
        }

        [$companyId, $visibilityType] = Company::normalizeTemplateOwnership(
            $this->option('company_id'),
            $this->option('visibility'),
        );

        foreach ($selected as $packKey) {
            $this->installPack($packKey, $packs[$packKey], $companyId, $visibilityType);
        }

        return self::SUCCESS;
    }

    private function selectedPacks(array $available): array
    {
        $requested = (string) $this->argument('pack');

        if ($requested === 'all') {
            return $available;
        }

        return array_values(array_filter(array_map('trim', explode(',', $requested))));
    }

    private function installPack(string $packKey, array $pack, ?int $companyId, string $visibilityType): void
    {
        $dryRun = (bool) $this->option('dry-run');
        $updateExisting = (bool) $this->option('update-existing');

        if ($dryRun) {
            $frameworkData = Arr::get($pack, 'framework', []);
            $scope = is_null($companyId) ? 'system template' : "company {$companyId}";
            $this->line("[dry-run] would install {$frameworkData['name']} as {$scope}");

            return;
        }

        $installer = app(ComplianceFrameworkInstaller::class);
        $summary = is_null($companyId)
            ? $installer->installSystemPack($packKey, $pack, $updateExisting, auth()->id())
            : $installer->installCompanyPack($packKey, $pack, $companyId, $visibilityType, $updateExisting, auth()->id());

        $this->line($this->formatSummary($summary).' for '.$packKey);
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
