<?php

namespace App\Support\Tenants;

use App\Models\Company;
use App\Models\Tenant;
use App\Models\User;

class TenantRecordGuard
{
    public static function companyTenantId(?int $companyId): ?int
    {
        if (! $companyId) {
            return null;
        }

        $tenantId = Company::withoutGlobalScopes()
            ->whereNull('deleted_at')
            ->where('id', $companyId)
            ->value('tenant_id');

        return $tenantId ? (int) $tenantId : null;
    }

    public static function userCanBeReferencedByTenant(?int $userId, ?int $tenantId, bool $allowSuperuser = true): bool
    {
        if (! $userId || ! $tenantId) {
            return false;
        }

        $user = User::withoutGlobalScopes()
            ->whereNull('deleted_at')
            ->find($userId);

        if (! $user) {
            return false;
        }

        if ($allowSuperuser && $user->isSuperAdmin()) {
            return true;
        }

        $userTenantId = $user->company_id
            ? static::companyTenantId((int) $user->company_id)
            : null;

        if ((int) ($userTenantId ?? 0) === (int) $tenantId) {
            return true;
        }

        return $user->tenants()
            ->where('tenants.id', $tenantId)
            ->exists();
    }

    public static function templateCanBeAppliedToCompany($template, ?int $companyId): bool
    {
        return $template && Company::templateCanBeAppliedToCompany($template, $companyId);
    }

    public static function recordBelongsToTenant($record, ?int $tenantId): bool
    {
        if (! $record || ! $tenantId || ! isset($record->company_id)) {
            return false;
        }

        return static::companyTenantId((int) $record->company_id) === (int) $tenantId;
    }
}
