<?php

namespace App\Support\Compliance;

use App\Helpers\Helper;
use App\Models\DocumentFramework;
use App\Models\DocumentFrameworkRequirement;
use App\Models\DocumentType;
use App\Models\Tenant;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ComplianceFrameworkInstaller
{
    public function availablePackKeys(?string $locale = null, ?string $jurisdiction = null): array
    {
        $packs = config('compliance_frameworks.packs', []);

        if (is_null($locale)) {
            return array_keys($packs);
        }

        $locale = $this->bootstrapLocale($locale);

        $matchingPacks = collect($packs)
            ->filter(fn (array $pack) => ($pack['locale'] ?? null) === $locale)
            ->values();

        if (blank($jurisdiction)) {
            return $matchingPacks
                ->map(fn (array $pack) => $this->packKeyFor($pack, $packs))
                ->filter()
                ->values()
                ->all();
        }

        $jurisdiction = $this->normalizeJurisdiction($jurisdiction);

        return $matchingPacks
            ->groupBy(fn (array $pack) => data_get($pack, 'framework.bootstrap_group', data_get($pack, 'framework.compliance_domain', 'custom')))
            ->map(function (Collection $domainPacks) use ($jurisdiction, $packs) {
                $compatiblePacks = $domainPacks
                    ->filter(fn (array $pack) => $this->jurisdictionPriority($pack, $jurisdiction) < 2);

                return $compatiblePacks
                    ->sortBy(fn (array $pack) => $this->jurisdictionPriority($pack, $jurisdiction))
                    ->first();
            })
            ->filter()
            ->map(fn (array $pack) => $this->packKeyFor($pack, $packs))
            ->filter()
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
        $packKeys = $packKeys ?: $this->availablePackKeys($locale, $tenant->defaultComplianceJurisdiction());

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
        if (is_null($companyId)) {
            throw new \InvalidArgumentException('Tenant-owned compliance packs require a company id.');
        }

        if ($visibilityType === DocumentFramework::VISIBILITY_GLOBAL) {
            throw new \InvalidArgumentException('Tenant-owned compliance packs cannot use global visibility.');
        }

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
        $this->assertOwnershipOptions($options);

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

        $this->ensureGlobalDocumentTypesForRequirements($packRequirements, $options['created_by']);

        foreach ($packRequirements as $index => $requirementData) {
            $requirementData = array_merge([
                'document_framework_id' => $framework->id,
                'is_mandatory' => true,
                'is_active' => true,
                'minimum_required_documents' => 1,
                'sort_order' => ($index + 1) * 10,
                'created_by' => $options['created_by'],
            ], $requirementData);
            $requirementData['default_document_type_id'] = $this->documentTypeIdForRequirement($requirementData, $frameworkData['company_id']);
            unset($requirementData['default_document_type_name']);
            unset($requirementData['parent_requirement_code'], $requirementData['parent_requirement_codes']);

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
            $parentCodes = $this->parentRequirementCodesFromData($requirementData);

            if (! $code || ! isset($requirementsByCode[$code], $requirementsNeedingParentSync[$code])) {
                continue;
            }

            $requirement = $requirementsByCode[$code];
            $parentIds = collect($parentCodes)
                ->filter(fn (string $parentCode) => isset($requirementsByCode[$parentCode]))
                ->map(fn (string $parentCode) => (int) $requirementsByCode[$parentCode]->id)
                ->unique()
                ->values()
                ->all();

            $parentId = $parentIds[0] ?? null;

            if ((int) ($requirement->parent_id ?? 0) !== (int) ($parentId ?? 0)) {
                $requirement->parent_id = $parentId;
                $this->saveOrFail($requirement);
            }

            if (DocumentFrameworkRequirement::parentPivotTableExists()) {
                $requirement->parents()->sync($parentIds);
            }
        }

        return $summary;
    }

    private function assertOwnershipOptions(array $options): void
    {
        $isSystemTemplate = (bool) ($options['is_system_template'] ?? false);
        $companyId = $options['company_id'] ?? null;
        $visibilityType = $options['visibility_type'] ?? null;

        if ($isSystemTemplate) {
            if (! is_null($companyId) || $visibilityType !== DocumentFramework::VISIBILITY_GLOBAL) {
                throw new \InvalidArgumentException('System compliance bootstrap packs must be global and company-less.');
            }

            return;
        }

        if (is_null($companyId)) {
            throw new \InvalidArgumentException('Tenant compliance packs require a company id.');
        }

        if ($visibilityType === DocumentFramework::VISIBILITY_GLOBAL) {
            throw new \InvalidArgumentException('Tenant compliance packs cannot use global visibility.');
        }
    }

    private function systemFrameworkIdForPack(string $packKey): ?int
    {
        return DocumentFramework::withoutGlobalScopes()
            ->where('source_pack_key', $packKey)
            ->where('is_system_template', true)
            ->whereNull('company_id')
            ->value('id');
    }

    private function documentTypeIdForRequirement(array $requirementData, ?int $companyId): ?int
    {
        if (isset($requirementData['default_document_type_id']) && is_numeric($requirementData['default_document_type_id'])) {
            return (int) $requirementData['default_document_type_id'];
        }

        $documentTypeName = trim((string) ($requirementData['default_document_type_name'] ?? ''));

        if ($documentTypeName === '') {
            return null;
        }

        return DocumentType::withoutGlobalScopes()
            ->whereNull('deleted_at')
            ->where('name', $documentTypeName)
            ->where(function ($query) use ($companyId) {
                if (! is_null($companyId)) {
                    $query->where('company_id', $companyId)
                        ->orWhereNull('company_id');
                } else {
                    $query->whereNull('company_id');
                }
            })
            ->orderByRaw('CASE WHEN company_id IS NULL THEN 1 ELSE 0 END')
            ->value('id');
    }

    private function ensureGlobalDocumentTypesForRequirements(array $requirements, ?int $createdBy): void
    {
        $documentTypeNames = collect($requirements)
            ->pluck('default_document_type_name')
            ->filter(fn ($name) => is_string($name) && trim($name) !== '')
            ->map(fn (string $name) => trim($name))
            ->unique()
            ->values();

        foreach ($documentTypeNames as $index => $documentTypeName) {
            $exists = DocumentType::withoutGlobalScopes()
                ->whereNull('deleted_at')
                ->whereNull('company_id')
                ->where('name', $documentTypeName)
                ->exists();

            if ($exists) {
                continue;
            }

            $documentType = new DocumentType([
                'name' => $documentTypeName,
                'slug' => Str::slug($documentTypeName),
                'sort_order' => ($index + 1) * 10,
                'is_active' => true,
                'created_by' => $createdBy,
                'company_id' => null,
                'visibility_type' => DocumentType::VISIBILITY_GLOBAL,
            ]);

            $this->saveOrFail($documentType);
        }
    }

    private function packVersion(array $pack): ?string
    {
        return $pack['pack_version'] ?? Arr::get($pack, 'framework.version');
    }

    private function packKeyFor(array $pack, array $packs): ?string
    {
        foreach ($packs as $packKey => $candidate) {
            if ($candidate === $pack) {
                return $packKey;
            }
        }

        return null;
    }

    private function normalizeJurisdiction(?string $jurisdiction): string
    {
        $jurisdiction = strtoupper(trim((string) $jurisdiction));

        return $jurisdiction !== '' ? $jurisdiction : Tenant::COMPLIANCE_JURISDICTION_EU;
    }

    private function jurisdictionPriority(array $pack, string $jurisdiction): int
    {
        $scope = data_get($pack, 'source_register.scope');
        $packJurisdiction = strtoupper((string) data_get($pack, 'source_register.jurisdiction', data_get($pack, 'framework.jurisdiction', '')));

        if ($scope === 'national_overlay' && $jurisdiction !== Tenant::COMPLIANCE_JURISDICTION_EU && str_contains($packJurisdiction, $jurisdiction)) {
            return 0;
        }

        if ($scope === 'eu_baseline' && str_contains($packJurisdiction, Tenant::COMPLIANCE_JURISDICTION_EU)) {
            return 1;
        }

        return 2;
    }

    private function parentRequirementCodesFromData(array $data): array
    {
        $value = $data['parent_requirement_codes'] ?? $data['parent_requirement_code'] ?? null;

        if (is_array($value)) {
            return collect($value)
                ->filter(fn ($code) => filled($code))
                ->map(fn ($code) => trim((string) $code))
                ->unique()
                ->values()
                ->all();
        }

        if (! is_string($value) || trim($value) === '') {
            return [];
        }

        return collect(preg_split('/[;,|]+/', $value) ?: [])
            ->map(fn ($code) => trim((string) $code))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    private function saveOrFail($model): void
    {
        if (! $model->save()) {
            throw new \RuntimeException(implode('; ', $model->getErrors()->all()));
        }
    }
}
