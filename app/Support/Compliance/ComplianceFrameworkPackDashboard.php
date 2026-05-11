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
                'version' => $this->packVersion($pack),
                'checksum' => $this->checksum($pack),
                'system' => $systemDiff,
                'tenant_counts' => $this->tenantCounts($tenantRows),
            ];
        })->values();
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

    public function shortChecksum(string $checksum): string
    {
        return Str::limit($checksum, 16, '');
    }

    private function tenantRowsForPack(string $packKey, array $pack, Collection $tenants): Collection
    {
        $packLocale = $pack['locale'] ?? null;

        return $tenants
            ->filter(fn (Tenant $tenant) => $this->installer->bootstrapLocale($tenant->defaultLocale()) === $packLocale)
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
