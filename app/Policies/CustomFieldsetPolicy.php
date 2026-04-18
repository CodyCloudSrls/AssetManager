<?php

namespace App\Policies;

use App\Models\Company;
use App\Models\User;

class CustomFieldsetPolicy extends SnipePermissionsPolicy
{
    protected function columnName()
    {
        /**
         * Proxy the authorization for custom fieldsets down to custom fields.
         * This allows us to use the existing permissions in use and have more
         * semantically correct authorization checks for custom fieldsets.
         *
         * See: https://github.com/grokability/snipe-it/pull/5795
         */
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
