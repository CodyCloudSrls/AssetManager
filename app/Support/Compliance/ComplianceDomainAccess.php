<?php

namespace App\Support\Compliance;

use App\Models\Document;
use App\Models\DocumentFramework;
use App\Models\DocumentFrameworkRequirement;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Schema;

final class ComplianceDomainAccess
{
    public static function userIsRestricted(?User $user = null): bool
    {
        $user ??= auth()->user();

        if (! $user instanceof User || $user->isSuperAdmin()) {
            return false;
        }

        if (! self::usersColumnExists()) {
            return false;
        }

        return (bool) $user->compliance_scope_restricted;
    }

    public static function allowedDomainKeys(?User $user = null): array
    {
        $user ??= auth()->user();

        if (! $user instanceof User || ! self::domainsTableExists()) {
            return [];
        }

        $domains = $user->relationLoaded('complianceDomains')
            ? $user->complianceDomains
            : $user->complianceDomains()->active()->get();

        return $domains
            ->filter(fn ($domain) => (bool) $domain->is_active)
            ->pluck('key')
            ->filter()
            ->map(fn ($key) => (string) $key)
            ->unique()
            ->values()
            ->all();
    }

    public static function canAccessDomain(?string $domainKey, ?User $user = null): bool
    {
        $domainKey = trim((string) $domainKey);

        if (! self::userIsRestricted($user)) {
            return true;
        }

        if ($domainKey === '') {
            return false;
        }

        return in_array($domainKey, self::allowedDomainKeys($user), true);
    }

    public static function canAccessFramework(?DocumentFramework $framework, ?User $user = null): bool
    {
        if (! $framework instanceof DocumentFramework) {
            return true;
        }

        return self::canAccessDomain($framework->compliance_domain, $user);
    }

    public static function canAccessRequirement(?DocumentFrameworkRequirement $requirement, ?User $user = null): bool
    {
        if (! $requirement instanceof DocumentFrameworkRequirement) {
            return true;
        }

        return self::canAccessFramework($requirement->framework, $user);
    }

    public static function canAccessDocument(?Document $document, ?User $user = null): bool
    {
        if (! self::userIsRestricted($user)) {
            return true;
        }

        if ($document->relationLoaded('framework')) {
            return self::canAccessFramework($document->framework, $user);
        }

        if (! $document->document_framework_id) {
            return false;
        }

        $framework = DocumentFramework::withoutGlobalScopes()->find($document->document_framework_id);

        return self::canAccessFramework($framework, $user);
    }

    public static function applyFrameworkScope(Builder $query, ?User $user = null, string $column = 'compliance_domain'): Builder
    {
        if (! self::userIsRestricted($user)) {
            return $query;
        }

        $allowedDomains = self::allowedDomainKeys($user);

        if ($allowedDomains === []) {
            return $query->whereRaw('1 = 0');
        }

        return $query->whereIn($column, $allowedDomains);
    }

    public static function applyRequirementScope(Builder $query, ?User $user = null): Builder
    {
        if (! self::userIsRestricted($user)) {
            return $query;
        }

        return $query->whereHas('framework', function (Builder $frameworkQuery) use ($user) {
            self::applyFrameworkScope($frameworkQuery, $user, 'document_frameworks.compliance_domain');
        });
    }

    public static function applyDocumentScope($query, ?User $user = null)
    {
        if (! self::userIsRestricted($user)) {
            return $query;
        }

        return $query->whereHas('framework', function (Builder $frameworkQuery) use ($user) {
            self::applyFrameworkScope($frameworkQuery, $user, 'document_frameworks.compliance_domain');
        });
    }

    private static function usersColumnExists(): bool
    {
        try {
            return Schema::hasColumn('users', 'compliance_scope_restricted');
        } catch (\Throwable) {
            return false;
        }
    }

    private static function domainsTableExists(): bool
    {
        try {
            return Schema::hasTable('compliance_domains')
                && Schema::hasTable('user_compliance_domains');
        } catch (\Throwable) {
            return false;
        }
    }
}
