<?php

namespace App\Policies;

use App\Models\Company;
use App\Models\User;

class DocumentFrameworkPolicy extends SnipePermissionsPolicy
{
    protected function columnName()
    {
        return 'documentframeworks';
    }

    public function update(User $user, $item = null)
    {
        if ($item?->isSystemTemplate() && ! is_null(\App\Models\Tenant::activeTenantId())) {
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
}
