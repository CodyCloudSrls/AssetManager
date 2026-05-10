<?php

namespace Tests\Support;

use App\Models\Company;
use Illuminate\Contracts\Auth\Authenticatable;
use Laravel\Passport\Passport;

trait InteractsWithAuthentication
{
    public function actingAs(Authenticatable $user, $guard = null)
    {
        Company::flushHierarchyCache();

        parent::actingAs($user, $guard);

        Company::flushHierarchyCache();

        return $this;
    }

    protected function actingAsForApi(Authenticatable $user)
    {
        Passport::actingAs($user);
        Company::flushHierarchyCache();

        return $this;
    }
}
