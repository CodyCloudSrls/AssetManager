<?php

namespace App\Policies;

use App\Models\User;

class CustomerPolicy extends SnipePermissionsPolicy
{
    protected function columnName()
    {
        return 'customers';
    }

    public function files(User $user, $item = null)
    {
        return $user->hasAccess($this->columnName().'.files');
    }
}
