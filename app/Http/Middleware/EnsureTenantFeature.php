<?php

namespace App\Http\Middleware;

use App\Models\Tenant;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Gate a route behind a per-tenant feature flag. If the current tenant context
 * does not have the feature enabled, the route behaves as if it does not exist
 * (404) — so a disabled module is neither visible nor reachable, and there are no
 * extra permissions to manage.
 *
 * Usage: ->middleware('tenant.feature:erp')
 */
class EnsureTenantFeature
{
    public function handle(Request $request, Closure $next, string $feature): Response
    {
        abort_unless(Tenant::currentContextHasFeature($feature), 404);

        return $next($request);
    }
}
