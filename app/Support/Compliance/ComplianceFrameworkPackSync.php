<?php

namespace App\Support\Compliance;

use App\Models\DocumentFramework;
use App\Models\DocumentFrameworkRequirement;
use App\Models\Tenant;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class ComplianceFrameworkPackSync
{
    private const FRAMEWORK_FIELDS = [
        'name',
        'slug',
        'description',
        'compliance_objective',
        'authority_name',
        'framework_code',
        'framework_type',
        'compliance_domain',
        'jurisdiction',
        'version',
        'status',
        'review_cadence_months',
        'external_reference_url',
        'sort_order',
        'is_active',
    ];

    private const REQUIREMENT_FIELDS = [
        'title',
        'domain',
        'obligation_type',
        'evidence_type',
        'delegation_level',
        'risk_level',
        'official_reference',
        'source_url',
        'review_frequency_months',
        'description',
        'evidence_guidance',
        'applicability_notes',
        'is_mandatory',
        'is_active',
        'sort_order',
        'parent_requirement_code',
    ];

    public function systemFramework(string $packKey, array $pack): ?DocumentFramework
    {
        return DocumentFramework::withoutGlobalScopes()
            ->whereNull('deleted_at')
            ->whereNull('company_id')
            ->where('is_system_template', true)
            ->where(function ($query) use ($packKey, $pack) {
                $query->where('source_pack_key', $packKey)
                    ->orWhere('slug', Arr::get($pack, 'framework.slug'));
            })
            ->first();
    }

    public function tenantFramework(Tenant $tenant, string $packKey, array $pack): ?DocumentFramework
    {
        $rootCompany = $tenant->rootCompany();

        if (! $rootCompany) {
            return null;
        }

        return DocumentFramework::withoutGlobalScopes()
            ->whereNull('deleted_at')
            ->where('company_id', $rootCompany->id)
            ->where('is_system_template', false)
            ->where(function ($query) use ($packKey, $pack) {
                $query->where('source_pack_key', $packKey)
                    ->orWhere('slug', Arr::get($pack, 'framework.slug'));
            })
            ->first();
    }

    public function diff(?DocumentFramework $framework, string $packKey, array $pack): array
    {
        $packVersion = $this->packVersion($pack);

        if (! $framework) {
            return [
                'pack_key' => $packKey,
                'pack_version' => $packVersion,
                'source_pack_version' => null,
                'status' => 'missing_framework',
                'framework_missing' => true,
                'framework_changes' => [],
                'missing_requirements' => array_values(array_map(
                    fn (array $requirement) => $requirement['code'] ?? '',
                    Arr::get($pack, 'requirements', []),
                )),
                'changed_requirements' => [],
                'custom_requirements' => [],
                'conflicts_count' => 0,
            ];
        }

        $frameworkChanges = $this->frameworkChanges($framework, Arr::get($pack, 'framework', []));
        $requirementDiff = $this->requirementDiff($framework, Arr::get($pack, 'requirements', []));
        $conflicts = count($frameworkChanges) + count($requirementDiff['changed']);
        $sourceVersion = $framework->source_pack_version;

        return [
            'pack_key' => $packKey,
            'pack_version' => $packVersion,
            'source_pack_version' => $sourceVersion,
            'status' => $this->status($sourceVersion, $packVersion, $frameworkChanges, $requirementDiff),
            'framework_missing' => false,
            'framework_changes' => $frameworkChanges,
            'missing_requirements' => $requirementDiff['missing'],
            'changed_requirements' => $requirementDiff['changed'],
            'custom_requirements' => $requirementDiff['custom'],
            'conflicts_count' => $conflicts,
        ];
    }

    public function mergeMissingRequirements(DocumentFramework $framework, string $packKey, array $pack, ?int $createdBy = null): array
    {
        return DB::transaction(function () use ($framework, $packKey, $pack, $createdBy) {
            $before = $this->diff($framework, $packKey, $pack);
            $created = 0;

            $createdRequirements = [];
            $requirementsByCode = DocumentFrameworkRequirement::withoutGlobalScopes()
                ->where('document_framework_id', $framework->id)
                ->whereNull('deleted_at')
                ->get()
                ->keyBy('code');

            foreach (Arr::get($pack, 'requirements', []) as $index => $requirementData) {
                $code = $requirementData['code'] ?? null;

                if (! $code || ! in_array($code, $before['missing_requirements'], true)) {
                    continue;
                }

                $attributes = array_merge([
                    'document_framework_id' => $framework->id,
                    'is_mandatory' => true,
                    'is_active' => true,
                    'sort_order' => ($index + 1) * 10,
                    'created_by' => $createdBy,
                ], $requirementData);
                unset($attributes['parent_requirement_code']);

                $requirement = new DocumentFrameworkRequirement($attributes);

                $this->saveOrFail($requirement);
                $createdRequirements[$code] = $requirement;
                $requirementsByCode[$code] = $requirement;
                $created++;
            }

            foreach (Arr::get($pack, 'requirements', []) as $requirementData) {
                $code = $requirementData['code'] ?? null;
                $parentCode = $requirementData['parent_requirement_code'] ?? null;

                if (! $code || ! isset($createdRequirements[$code])) {
                    continue;
                }

                $parentId = $parentCode && isset($requirementsByCode[$parentCode])
                    ? $requirementsByCode[$parentCode]->id
                    : null;

                if ($parentId) {
                    $createdRequirements[$code]->parent_id = $parentId;
                    $this->saveOrFail($createdRequirements[$code]);
                }
            }

            $framework->refresh();
            $after = $this->diff($framework, $packKey, $pack);
            $metadataUpdated = $this->refreshSourceMetadataIfClean($framework, $packKey, $pack, $after);

            if ($metadataUpdated) {
                $framework->refresh();
                $after = $this->diff($framework, $packKey, $pack);
            }

            return [
                'requirements_created' => $created,
                'metadata_updated' => $metadataUpdated,
                'conflicts_count' => $after['conflicts_count'],
                'before' => $before,
                'after' => $after,
            ];
        });
    }

    private function frameworkChanges(DocumentFramework $framework, array $frameworkData): array
    {
        $changes = [];

        foreach (self::FRAMEWORK_FIELDS as $field) {
            if (! array_key_exists($field, $frameworkData)) {
                continue;
            }

            $current = $this->normalizeValue($framework->{$field});
            $expected = $this->normalizeValue($frameworkData[$field]);

            if ($current !== $expected) {
                $changes[$field] = [
                    'current' => $framework->{$field},
                    'expected' => $frameworkData[$field],
                ];
            }
        }

        return $changes;
    }

    private function requirementDiff(DocumentFramework $framework, array $packRequirements): array
    {
        $requirements = DocumentFrameworkRequirement::withoutGlobalScopes()
            ->where('document_framework_id', $framework->id)
            ->whereNull('deleted_at')
            ->get()
            ->keyBy('code');

        $missing = [];
        $changed = [];
        $packCodes = [];

        foreach ($packRequirements as $index => $requirementData) {
            $code = $requirementData['code'] ?? null;

            if (! $code) {
                continue;
            }

            $packCodes[] = $code;
            $requirement = $requirements->get($code);

            if (! $requirement) {
                $missing[] = $code;
                continue;
            }

            $expected = array_merge([
                'is_mandatory' => true,
                'is_active' => true,
                'sort_order' => ($index + 1) * 10,
            ], $requirementData);

            foreach (self::REQUIREMENT_FIELDS as $field) {
                if (! array_key_exists($field, $expected)) {
                    continue;
                }

                $current = $field === 'parent_requirement_code'
                    ? $this->normalizeValue($requirement->parent?->code)
                    : $this->normalizeValue($requirement->{$field});
                $expectedValue = $this->normalizeValue($expected[$field]);

                if ($current !== $expectedValue) {
                    $changed[$code][$field] = [
                        'current' => $field === 'parent_requirement_code' ? $requirement->parent?->code : $requirement->{$field},
                        'expected' => $expected[$field],
                    ];
                }
            }
        }

        return [
            'missing' => $missing,
            'changed' => $changed,
            'custom' => array_values($requirements->keys()->diff($packCodes)->all()),
        ];
    }

    private function refreshSourceMetadataIfClean(DocumentFramework $framework, string $packKey, array $pack, array $diff): bool
    {
        if ($diff['conflicts_count'] > 0 || count($diff['missing_requirements']) > 0) {
            return false;
        }

        $metadata = [];

        foreach ([
            'source_pack_key' => $packKey,
            'source_pack_version' => $this->packVersion($pack),
            'locale' => $pack['locale'] ?? null,
        ] as $column => $value) {
            if ($this->normalizeValue($framework->{$column}) !== $this->normalizeValue($value)) {
                $metadata[$column] = $value;
            }
        }

        if (! $framework->is_system_template && blank($framework->source_framework_id)) {
            $systemFrameworkId = $this->systemFrameworkIdForPack($packKey);

            if ($systemFrameworkId) {
                $metadata['source_framework_id'] = $systemFrameworkId;
            }
        }

        if (count($metadata) === 0) {
            return false;
        }

        $framework->forceFill($metadata)->saveQuietly();

        return true;
    }

    private function status(?string $sourceVersion, ?string $packVersion, array $frameworkChanges, array $requirementDiff): string
    {
        if (count($frameworkChanges) > 0 || count($requirementDiff['changed']) > 0) {
            return 'modified';
        }

        if (count($requirementDiff['missing']) > 0 || $this->normalizeValue($sourceVersion) !== $this->normalizeValue($packVersion)) {
            return 'outdated';
        }

        return 'current';
    }

    private function packVersion(array $pack): ?string
    {
        return $pack['pack_version'] ?? Arr::get($pack, 'framework.version');
    }

    private function systemFrameworkIdForPack(string $packKey): ?int
    {
        return DocumentFramework::withoutGlobalScopes()
            ->where('source_pack_key', $packKey)
            ->where('is_system_template', true)
            ->whereNull('company_id')
            ->value('id');
    }

    private function normalizeValue($value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_bool($value)) {
            return $value ? '1' : '0';
        }

        if (is_int($value) || is_float($value)) {
            return (string) $value;
        }

        return (string) $value;
    }

    private function saveOrFail($model): void
    {
        if (! $model->save()) {
            throw new \RuntimeException(implode('; ', $model->getErrors()->all()));
        }
    }
}
