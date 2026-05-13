<?php

namespace App\Support\Compliance;

use App\Models\ComplianceFrameworkPackEvent;
use App\Models\DocumentFramework;
use App\Models\Tenant;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Str;
use InvalidArgumentException;

class ComplianceFrameworkPackDashboard
{
    public function __construct(
        private ComplianceFrameworkInstaller $installer,
        private ComplianceFrameworkPackSync $sync,
    ) {
    }

    public function packRows(): Collection
    {
        $tenants = $this->tenants();

        return collect($this->packs())->map(function (array $pack, string $packKey) use ($tenants) {
            $systemDiff = $this->systemDiff($packKey, $pack);
            $tenantRows = $this->tenantRowsForPack($packKey, $pack, $tenants);

            return [
                'key' => $packKey,
                'name' => data_get($pack, 'framework.name', $packKey),
                'locale' => $pack['locale'] ?? null,
                'domain' => data_get($pack, 'framework.compliance_domain'),
                'jurisdiction' => data_get($pack, 'source_register.jurisdiction', data_get($pack, 'framework.jurisdiction')),
                'source_register_key' => $pack['source_register_key'] ?? null,
                'source_status' => data_get($pack, 'source_register.status'),
                'source_scope' => data_get($pack, 'source_register.scope'),
                'source_checked_at' => data_get($pack, 'source_register.last_checked_at'),
                'version' => $this->packVersion($pack),
                'checksum' => $this->checksum($pack),
                'system' => $systemDiff,
                'tenant_counts' => $this->tenantCounts($tenantRows),
            ];
        })->values();
    }

    public function filtersFromInput(array $input): array
    {
        return collect([
            'domain',
            'locale',
            'jurisdiction',
            'source_status',
            'system_status',
            'tenant_status',
        ])->mapWithKeys(fn (string $key) => [$key => trim((string) ($input[$key] ?? ''))])
            ->filter(fn (string $value) => $value !== '')
            ->all();
    }

    public function filterPackRows(Collection $rows, array $filters): Collection
    {
        return $rows->filter(function (array $row) use ($filters) {
            if (isset($filters['domain']) && $row['domain'] !== $filters['domain']) {
                return false;
            }

            if (isset($filters['locale']) && $row['locale'] !== $filters['locale']) {
                return false;
            }

            if (isset($filters['jurisdiction']) && $row['jurisdiction'] !== $filters['jurisdiction']) {
                return false;
            }

            if (isset($filters['source_status']) && $row['source_status'] !== $filters['source_status']) {
                return false;
            }

            if (isset($filters['system_status']) && ($row['system']['status'] ?? null) !== $filters['system_status']) {
                return false;
            }

            if (isset($filters['tenant_status'])) {
                $tenantStatus = $filters['tenant_status'];
                $count = (int) ($row['tenant_counts'][$tenantStatus] ?? 0);

                if ($count < 1) {
                    return false;
                }
            }

            return true;
        })->values();
    }

    public function filterOptions(Collection $rows): array
    {
        return [
            'domains' => $this->optionsFromRows($rows, 'domain', fn (?string $value) => $this->domainLabel($value)),
            'locales' => $this->optionsFromRows($rows, 'locale', fn (?string $value) => $this->localeLabel($value)),
            'jurisdictions' => $this->optionsFromRows($rows, 'jurisdiction'),
            'source_statuses' => $this->optionsFromRows($rows, 'source_status', fn (?string $value) => $this->sourceStatusLabel($value)),
            'system_statuses' => $this->optionsFromRows($rows, 'system.status', fn (?string $value) => $this->statusLabel((string) $value)),
            'tenant_statuses' => collect(['current', 'outdated', 'modified', 'missing_framework', 'actionable'])
                ->filter(fn (string $status) => $rows->contains(fn (array $row) => (int) ($row['tenant_counts'][$status] ?? 0) > 0))
                ->mapWithKeys(fn (string $status) => [$status => $this->statusLabel($status)])
                ->all(),
        ];
    }

    public function packOrFail(string $packKey): array
    {
        $packs = $this->packs();

        if (! isset($packs[$packKey])) {
            throw new InvalidArgumentException("Unknown compliance framework pack: {$packKey}");
        }

        return $packs[$packKey];
    }

    public function systemDiff(string $packKey, array $pack): array
    {
        $framework = $this->sync->systemFramework($packKey, $pack);

        return $this->sync->diff($framework, $packKey, $pack);
    }

    public function tenantRows(string $packKey, array $pack): Collection
    {
        return $this->tenantRowsForPack($packKey, $pack, $this->tenants());
    }

    public function latestEvents(?string $packKey = null, int $limit = 25): Collection
    {
        return ComplianceFrameworkPackEvent::query()
            ->with(['tenant', 'company', 'framework', 'actor'])
            ->when($packKey, fn ($query) => $query->where('pack_key', $packKey))
            ->orderByDesc('id')
            ->limit($limit)
            ->get();
    }

    public function checksum(array $pack): string
    {
        return ComplianceFrameworkPackEvent::checksumForPack($pack);
    }

    public function canApplyTenantDiff(array $diff): bool
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

    public function canApplySystemDiff(array $diff): bool
    {
        return $diff['status'] !== 'current' || $diff['framework_missing'];
    }

    public function statusLabelClass(string $status): string
    {
        return match ($status) {
            'current' => 'success',
            'outdated' => 'warning',
            'modified' => 'danger',
            'actionable' => 'info',
            'missing_framework' => 'default',
            default => 'default',
        };
    }

    public function statusLabel(string $status): string
    {
        return $this->translatedChoice('admin/compliancepacks/general.statuses.'.$status, $status);
    }

    public function scopeLabel(string $scope): string
    {
        return $this->translatedChoice('admin/compliancepacks/general.scopes.'.$scope, $scope);
    }

    public function eventTypeLabel(string $eventType): string
    {
        return $this->translatedChoice('admin/compliancepacks/general.event_types.'.$eventType, $eventType);
    }

    public function localeLabel(?string $locale): string
    {
        if (blank($locale)) {
            return '-';
        }

        $languages = trans('localizations.languages');

        if (is_array($languages) && isset($languages[$locale])) {
            return $languages[$locale];
        }

        return $locale;
    }

    public function domainLabel(?string $domain): string
    {
        if (blank($domain)) {
            return '-';
        }

        return DocumentFramework::complianceDomainOptions()[$domain] ?? $this->humanizeCode($domain);
    }

    public function sourceStatusLabel(?string $status): string
    {
        if (blank($status)) {
            return '-';
        }

        return $this->translatedChoice('admin/compliancepacks/general.source_statuses.'.$status, $status);
    }

    public function sourceScopeLabel(?string $scope): string
    {
        if (blank($scope)) {
            return '-';
        }

        return $this->translatedChoice('admin/compliancepacks/general.source_scopes.'.$scope, $scope);
    }

    public function shortChecksum(string $checksum): string
    {
        return Str::limit($checksum, 16, '');
    }

    private function tenantRowsForPack(string $packKey, array $pack, Collection $tenants): Collection
    {
        $packLocale = $pack['locale'] ?? null;

        return $tenants
            ->filter(function (Tenant $tenant) use ($packKey, $packLocale) {
                $tenantLocale = $this->installer->bootstrapLocale($tenant->defaultLocale());

                return $tenantLocale === $packLocale
                    && in_array($packKey, $this->installer->availablePackKeys($tenantLocale, $tenant->defaultComplianceJurisdiction()), true);
            })
            ->map(function (Tenant $tenant) use ($packKey, $pack) {
                $framework = $this->sync->tenantFramework($tenant, $packKey, $pack);
                $diff = $this->sync->diff($framework, $packKey, $pack);

                return [
                    'tenant' => $tenant,
                    'root_company' => $tenant->rootCompany(),
                    'framework' => $framework,
                    'diff' => $diff,
                    'can_apply' => $this->canApplyTenantDiff($diff),
                ];
            })->values();
    }

    private function tenantCounts(Collection $tenantRows): array
    {
        $counts = [
            'total' => $tenantRows->count(),
            'current' => 0,
            'outdated' => 0,
            'modified' => 0,
            'missing_framework' => 0,
            'actionable' => 0,
        ];

        foreach ($tenantRows as $row) {
            $status = $row['diff']['status'] ?? 'missing_framework';
            $counts[$status] = ($counts[$status] ?? 0) + 1;

            if ($row['can_apply']) {
                $counts['actionable']++;
            }
        }

        return $counts;
    }

    private function tenants(): Collection
    {
        return Tenant::query()
            ->orderBy('id')
            ->get()
            ->filter(fn (Tenant $tenant) => ! is_null($tenant->rootCompany()))
            ->values();
    }

    private function optionsFromRows(Collection $rows, string $key, ?callable $labeler = null): array
    {
        $options = $rows
            ->map(fn (array $row) => data_get($row, $key))
            ->filter(fn ($value) => filled($value))
            ->unique()
            ->mapWithKeys(function ($value) use ($labeler) {
                $value = (string) $value;

                return [$value => $labeler ? $labeler($value) : $value];
            })
            ->all();

        asort($options, SORT_NATURAL | SORT_FLAG_CASE);

        return $options;
    }

    private function packs(): array
    {
        return config('compliance_frameworks.packs', []);
    }

    private function packVersion(array $pack): ?string
    {
        return $pack['pack_version'] ?? data_get($pack, 'framework.version');
    }

    private function translatedChoice(string $key, string $fallbackCode): string
    {
        if (Lang::has($key)) {
            return trans($key);
        }

        return $this->humanizeCode($fallbackCode);
    }

    private function humanizeCode(string $code): string
    {
        return Str::headline(str_replace(['-', '_'], ' ', $code));
    }
}
