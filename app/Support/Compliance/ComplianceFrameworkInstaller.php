<?php

namespace App\Support\Compliance;

use App\Helpers\Helper;
use App\Models\DocumentFramework;
use App\Models\DocumentFrameworkRequirement;
use App\Models\Tenant;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class ComplianceFrameworkInstaller
{
    public function availablePackKeys(?string $locale = null): array
    {
        $packs = config('compliance_frameworks.packs', []);

        if (is_null($locale)) {
            return array_keys($packs);
        }

        $locale = $this->bootstrapLocale($locale);

        return collect($packs)
            ->filter(fn (array $pack) => ($pack['locale'] ?? null) === $locale)
            ->keys()
            ->values()
            ->all();
    }

    public function bootstrapLocale(?string $locale): string
    {
        $locale = Helper::normalizeSupportedLocale($locale);
        $packs = config('compliance_frameworks.packs', []);

        if (collect($packs)->contains(fn (array $pack) => ($pack['locale'] ?? null) === $locale)) {
            return $locale;
        }

        if (collect($packs)->contains(fn (array $pack) => ($pack['locale'] ?? null) === 'en-US')) {
            return 'en-US';
        }

        return collect($packs)->first()['locale'] ?? 'en-US';
    }

    public function bootstrapTenant(Tenant $tenant, ?string $locale = null, ?array $packKeys = null, bool $updateExisting = false, ?int $createdBy = null): array
    {
        $rootCompany = $tenant->rootCompany();

        if (! $rootCompany) {
            return [
                'created' => 0,
                'updated' => 0,
                'skipped' => 0,
                'requirements_created' => 0,
                'requirements_updated' => 0,
                'requirements_skipped' => 0,
            ];
        }

        $locale = $this->bootstrapLocale($locale ?: $tenant->defaultLocale());
        $packKeys = $packKeys ?: $this->availablePackKeys($locale);

        return DB::transaction(function () use ($packKeys, $rootCompany, $locale, $updateExisting, $createdBy) {
            $summary = [
                'created' => 0,
                'updated' => 0,
                'skipped' => 0,
                'requirements_created' => 0,
                'requirements_updated' => 0,
                'requirements_skipped' => 0,
            ];

            foreach ($packKeys as $packKey) {
                $pack = config("compliance_frameworks.packs.{$packKey}");

                if (! is_array($pack) || ($pack['locale'] ?? null) !== $locale) {
                    continue;
                }

                $result = $this->installPack($packKey, $pack, [
                    'company_id' => (int) $rootCompany->id,
                    'visibility_type' => DocumentFramework::VISIBILITY_PRIVATE,
                    'is_system_template' => false,
                    'locale' => $locale,
                    'created_by' => $createdBy,
                    'update_existing' => $updateExisting,
                    'link_system_source' => true,
                ]);

                foreach ($summary as $key => $value) {
                    $summary[$key] += $result[$key] ?? 0;
                }
            }

            return $summary;
        });
    }

    public function installSystemPack(string $packKey, array $pack, bool $updateExisting = false, ?int $createdBy = null): array
    {
        return $this->installPack($packKey, $pack, [
            'company_id' => null,
            'visibility_type' => DocumentFramework::VISIBILITY_GLOBAL,
            'is_system_template' => true,
            'locale' => $pack['locale'] ?? null,
            'created_by' => $createdBy,
            'update_existing' => $updateExisting,
            'link_system_source' => false,
        ]);
    }

    public function installCompanyPack(string $packKey, array $pack, ?int $companyId, string $visibilityType, bool $updateExisting = false, ?int $createdBy = null): array
    {
        return $this->installPack($packKey, $pack, [
            'company_id' => $companyId,
            'visibility_type' => $visibilityType,
            'is_system_template' => false,
            'locale' => $pack['locale'] ?? null,
            'created_by' => $createdBy,
            'update_existing' => $updateExisting,
            'link_system_source' => true,
        ]);
    }

    private function installPack(string $packKey, array $pack, array $options): array
    {
        $summary = [
            'created' => 0,
            'updated' => 0,
            'skipped' => 0,
            'requirements_created' => 0,
            'requirements_updated' => 0,
            'requirements_skipped' => 0,
        ];

        $frameworkData = Arr::get($pack, 'framework', []);
        $frameworkData['company_id'] = $options['company_id'];
        $frameworkData['visibility_type'] = $options['visibility_type'];
        $frameworkData['is_system_template'] = (bool) $options['is_system_template'];
        $frameworkData['source_pack_key'] = $packKey;
        $frameworkData['source_pack_version'] = $this->packVersion($pack);
        $frameworkData['locale'] = $options['locale'];
        $frameworkData['created_by'] = $options['created_by'];
        $frameworkData['source_framework_id'] = $options['link_system_source']
            ? $this->systemFrameworkIdForPack($packKey)
            : null;

        $framework = DocumentFramework::withoutGlobalScopes()
            ->whereNull('deleted_at')
            ->where(function ($query) use ($frameworkData, $packKey) {
                $query->where('source_pack_key', $packKey)
                    ->orWhere('slug', $frameworkData['slug']);
            })
            ->where(function ($companyQuery) use ($frameworkData) {
                if (is_null($frameworkData['company_id'])) {
                    $companyQuery->whereNull('company_id');
                } else {
                    $companyQuery->where('company_id', $frameworkData['company_id']);
                }
            })
            ->first();

        if (! $framework) {
            $framework = new DocumentFramework($frameworkData);
            $this->saveOrFail($framework);
            $summary['created']++;
        } elseif ($options['update_existing']) {
            $framework->fill($frameworkData);
            $this->saveOrFail($framework);
            $summary['updated']++;
        } else {
            $metadata = [];

            foreach (['source_pack_key', 'source_pack_version', 'locale', 'source_framework_id'] as $column) {
                if (blank($framework->{$column}) && filled($frameworkData[$column] ?? null)) {
                    $metadata[$column] = $frameworkData[$column];
                }
            }

            if (count($metadata) > 0) {
                $framework->forceFill($metadata)->saveQuietly();
            }

            $summary['skipped']++;
        }

        $requirementsByCode = [];
        $requirementsNeedingParentSync = [];
        $packRequirements = Arr::get($pack, 'requirements', []);

        foreach ($packRequirements as $index => $requirementData) {
            $requirementData = array_merge([
                'document_framework_id' => $framework->id,
                'is_mandatory' => true,
                'is_active' => true,
                'sort_order' => ($index + 1) * 10,
                'created_by' => $options['created_by'],
            ], $requirementData);
            unset($requirementData['parent_requirement_code']);

            $requirement = DocumentFrameworkRequirement::withoutGlobalScopes()
                ->where('document_framework_id', $framework->id)
                ->where('code', $requirementData['code'])
                ->first();

            if (! $requirement) {
                $requirement = new DocumentFrameworkRequirement($requirementData);
                $this->saveOrFail($requirement);
                $summary['requirements_created']++;
                $requirementsNeedingParentSync[$requirement->code] = true;
            } elseif ($options['update_existing']) {
                $requirement->fill($requirementData);
                $this->saveOrFail($requirement);
                $summary['requirements_updated']++;
                $requirementsNeedingParentSync[$requirement->code] = true;
            } else {
                $summary['requirements_skipped']++;
            }

            $requirementsByCode[$requirement->code] = $requirement;
        }

        foreach ($packRequirements as $requirementData) {
            $code = $requirementData['code'] ?? null;
            $parentCode = $requirementData['parent_requirement_code'] ?? null;

            if (! $code || ! isset($requirementsByCode[$code], $requirementsNeedingParentSync[$code])) {
                continue;
            }

            $requirement = $requirementsByCode[$code];
            $parentId = $parentCode && isset($requirementsByCode[$parentCode])
                ? $requirementsByCode[$parentCode]->id
                : null;

            if ((int) ($requirement->parent_id ?? 0) !== (int) ($parentId ?? 0)) {
                $requirement->parent_id = $parentId;
                $this->saveOrFail($requirement);
            }
        }

        return $summary;
    }

    private function systemFrameworkIdForPack(string $packKey): ?int
    {
        return DocumentFramework::withoutGlobalScopes()
            ->where('source_pack_key', $packKey)
            ->where('is_system_template', true)
            ->whereNull('company_id')
            ->value('id');
    }

    private function packVersion(array $pack): ?string
    {
        return $pack['pack_version'] ?? Arr::get($pack, 'framework.version');
    }

    private function saveOrFail($model): void
    {
        if (! $model->save()) {
            throw new \RuntimeException(implode('; ', $model->getErrors()->all()));
        }
    }
}
