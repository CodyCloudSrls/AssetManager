<?php

namespace App\Policies;

use App\Models\Company;
use App\Models\User;

class DocumentTypePolicy extends SnipePermissionsPolicy
{
    protected function columnName()
    {
        return 'documenttypes';
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
