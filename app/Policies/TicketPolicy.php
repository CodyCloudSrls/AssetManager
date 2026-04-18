<?php

namespace App\Policies;

use App\Models\User;

class TicketPolicy extends SnipePermissionsPolicy
{
    protected function columnName()
    {
        return 'tickets';
    }

    public function operate(User $user, $item = null): bool
    {
        return $user->hasAccess($this->columnName().'.operate')
            || $user->hasAccess($this->columnName().'.edit');
    }
}
