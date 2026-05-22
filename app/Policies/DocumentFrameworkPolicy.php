<?php

namespace App\Policies;

use App\Models\Company;
use App\Models\DocumentFramework;
use App\Models\Tenant;
use App\Models\User;
use App\Support\Compliance\ComplianceDomainAccess;

class DocumentFrameworkPolicy extends SnipePermissionsPolicy
{
    protected function columnName()
    {
        return 'documentframeworks';
    }

    public function before(User $user, $ability, $item = null)
    {
        if ($item instanceof DocumentFramework && ! ComplianceDomainAccess::canAccessFramework($item, $user)) {
            return false;
        }

        if ($item instanceof DocumentFramework && is_null($item->company_id)) {
            return $item->isSystemTemplate()
                ? $this->allowsSystemTemplateAbility($user, $ability)
                : Tenant::canCurrentUserUseGlobalTenantContext();
        }

        return parent::before($user, $ability, $item);
    }

    public function view(User $user, $item = null)
    {
        if ($item instanceof DocumentFramework && ! ComplianceDomainAccess::canAccessFramework($item, $user)) {
            return false;
        }

        if ($item instanceof DocumentFramework && is_null($item->company_id)) {
            return $item->isSystemTemplate()
                ? $this->allowsSystemTemplateAbility($user, 'view')
                : Tenant::canCurrentUserUseGlobalTenantContext();
        }

        return parent::view($user, $item);
    }

    public function update(User $user, $item = null)
    {
        if ($item?->isSystemTemplate()) {
            return false;
        }

        return parent::update($user, $item) && Company::canCurrentUserManageTemplate($item);
    }

    public function delete(User $user, $item = null)
    {
        if ($item?->isSystemTemplate()) {
            return false;
        }

        return parent::delete($user, $item) && Company::canCurrentUserManageTemplate($item);
    }

    private function allowsSystemTemplateAbility(User $user, string $ability): bool
    {
        if (! in_array($ability, ['view', 'history', 'journal'], true)) {
            return false;
        }

        return Tenant::canCurrentUserUseGlobalTenantContext();
    }
}
