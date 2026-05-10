<?php

namespace App\Policies;

use App\Models\Company;
use App\Models\DocumentFrameworkRequirement;
use App\Models\Tenant;
use App\Models\User;

class DocumentFrameworkRequirementPolicy extends SnipePermissionsPolicy
{
    protected function columnName()
    {
        return 'documentframeworks';
    }

    public function before(User $user, $ability, $item = null)
    {
        if ($item instanceof DocumentFrameworkRequirement && $item->framework && is_null($item->framework->company_id)) {
            return $item->framework->isSystemTemplate()
                ? $this->allowsSystemTemplateAbility($user, $ability)
                : $user->isSuperUser() && is_null(Tenant::activeTenantId());
        }

        return parent::before($user, $ability, $item);
    }

    public function view(User $user, $item = null)
    {
        if ($item instanceof DocumentFrameworkRequirement && $item->framework && is_null($item->framework->company_id)) {
            return $item->framework->isSystemTemplate()
                ? $this->allowsSystemTemplateAbility($user, 'view')
                : $user->isSuperUser() && is_null(Tenant::activeTenantId());
        }

        return parent::view($user, $item)
            && $item?->framework
            && Company::isCurrentUserHasTemplateAccess($item->framework);
    }

    public function update(User $user, $item = null)
    {
        return parent::update($user, $item)
            && $item?->framework
            && ! $item->framework->isSystemTemplate()
            && Company::canCurrentUserManageTemplate($item->framework);
    }

    public function delete(User $user, $item = null)
    {
        return parent::delete($user, $item)
            && $item?->framework
            && ! $item->framework->isSystemTemplate()
            && Company::canCurrentUserManageTemplate($item->framework);
    }

    private function allowsSystemTemplateAbility(User $user, string $ability): bool
    {
        if (! in_array($ability, ['view', 'history', 'journal'], true)) {
            return false;
        }

        return $user->isSuperUser() && is_null(Tenant::activeTenantId());
    }
}
