<?php

namespace App\Console\Commands;

use App\Models\Company;
use App\Models\DocumentFramework;
use App\Models\DocumentFrameworkRequirement;
use Illuminate\Console\Command;
use Illuminate\Support\Arr;

class InstallComplianceFrameworks extends Command
{
    protected $signature = 'snipeit:install-compliance-frameworks
        {pack=all : all, nis2_it, gdpr_eu, or a comma-separated list}
        {--company_id= : Optional company id for tenant-owned frameworks}
        {--visibility=global : private, descendants, or global}
        {--update-existing : Update existing frameworks and requirements}
        {--dry-run : Show changes without writing}';

    protected $description = 'Install starter compliance document frameworks for NIS2/GDPR without overwriting existing data by default.';

    public function handle(): int
    {
        $packs = config('compliance_frameworks.packs', []);
        $selected = $this->selectedPacks(array_keys($packs));

        foreach ($selected as $packKey) {
            if (! isset($packs[$packKey])) {
                $this->error("Unknown compliance pack: {$packKey}");

                return self::FAILURE;
            }
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

        $frameworkData = Arr::get($pack, 'framework', []);
        $frameworkData['company_id'] = $companyId;
        $frameworkData['visibility_type'] = $visibilityType;
        $frameworkData['created_by'] = auth()->id();

        $framework = DocumentFramework::where('slug', $frameworkData['slug'])
            ->where('company_id', $companyId)
            ->first();

        if (! $framework) {
            $this->line(($dryRun ? '[dry-run] would create ' : 'creating ').$frameworkData['name']);

            if (! $dryRun) {
                $framework = new DocumentFramework($frameworkData);
                $this->saveOrFail($framework);
            }
        } elseif ($updateExisting) {
            $this->line(($dryRun ? '[dry-run] would update ' : 'updating ').$frameworkData['name']);

            if (! $dryRun) {
                $framework->fill($frameworkData);
                $this->saveOrFail($framework);
            }
        } else {
            $this->line('skipping existing '.$frameworkData['name']);
        }

        foreach (Arr::get($pack, 'requirements', []) as $index => $requirementData) {
            $requirementData = array_merge([
                'is_mandatory' => true,
                'is_active' => true,
                'sort_order' => ($index + 1) * 10,
                'created_by' => auth()->id(),
            ], $requirementData);

            if ($dryRun && ! $framework) {
                $this->line('[dry-run] would create requirement '.$requirementData['code'].' for '.$packKey);
                continue;
            }

            $requirement = DocumentFrameworkRequirement::where('document_framework_id', $framework->id)
                ->where('code', $requirementData['code'])
                ->first();

            $requirementData['document_framework_id'] = $framework->id;

            if (! $requirement) {
                $this->line(($dryRun ? '[dry-run] would create requirement ' : 'creating requirement ').$requirementData['code']);

                if (! $dryRun) {
                    $requirement = new DocumentFrameworkRequirement($requirementData);
                    $this->saveOrFail($requirement);
                }
            } elseif ($updateExisting) {
                $this->line(($dryRun ? '[dry-run] would update requirement ' : 'updating requirement ').$requirementData['code']);

                if (! $dryRun) {
                    $requirement->fill($requirementData);
                    $this->saveOrFail($requirement);
                }
            } else {
                $this->line('skipping existing requirement '.$requirementData['code']);
            }
        }
    }

    private function saveOrFail($model): void
    {
        if (! $model->save()) {
            throw new \RuntimeException(implode('; ', $model->getErrors()->all()));
        }
    }
}
