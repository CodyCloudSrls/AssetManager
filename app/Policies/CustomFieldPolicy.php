<?php

namespace App\Policies;

use App\Models\Company;
use App\Models\User;

class CustomFieldPolicy extends SnipePermissionsPolicy
{
    protected function columnName()
    {
        return 'customfields';
    }

    public function update(User $user, $item = null)
    {
        return parent::update($user, $item) && Company::canCurrentUserManageTemplate($item);
    }

    public function delete(User $user, $item = null)
    {
        return parent::delete($user, $item) && Company::canCurrentUserManageTemplate($item);
    }
}
