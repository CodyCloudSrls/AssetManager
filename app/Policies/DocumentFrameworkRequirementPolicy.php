<?php

namespace App\Policies;

use App\Models\Company;
use App\Models\User;

class DocumentFrameworkRequirementPolicy extends SnipePermissionsPolicy
{
    protected function columnName()
    {
        return 'documentframeworks';
    }

    public function view(User $user, $item = null)
    {
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
}
