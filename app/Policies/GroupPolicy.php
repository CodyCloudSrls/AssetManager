<?php

namespace App\Policies;

use App\Models\Group;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class GroupPolicy
{
    use HandlesAuthorization;

    public function view(User $user, $item = null): bool
    {
        return $user->isSuperAdmin();
    }

    public function create(User $user): bool
    {
        return $user->isSuperAdmin();
    }

    public function update(User $user, $item = null): bool
    {
        return $user->isSuperAdmin();
    }

    public function delete(User $user, $item = null): bool
    {
        if (! $user->isSuperAdmin()) {
            return false;
        }

        return ! $item instanceof Group || ! $item->isSystemGroup();
    }
}
