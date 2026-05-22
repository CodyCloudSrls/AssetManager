<?php

namespace App\Policies;

use App\Models\ComplianceDomain;
use App\Models\User;

class ComplianceDomainPolicy extends SnipePermissionsPolicy
{
    protected function columnName()
    {
        return 'compliancedomains';
    }

    public function before(User $user, $ability, $item = null)
    {
        if (in_array($ability, ['create', 'update', 'delete', 'restore'], true) && ! $user->isSuperAdmin()) {
            return false;
        }

        return parent::before($user, $ability, $item);
    }

    public function update(User $user, $item = null)
    {
        if ($item instanceof ComplianceDomain && $item->is_system) {
            return $user->hasAccess('compliancedomains.edit');
        }

        return parent::update($user, $item);
    }

    public function delete(User $user, $item = null)
    {
        if ($item instanceof ComplianceDomain && $item->is_system) {
            return false;
        }

        return parent::delete($user, $item);
    }

    public function restore(User $user, $item = null)
    {
        return $user->hasAccess('compliancedomains.create');
    }
}
