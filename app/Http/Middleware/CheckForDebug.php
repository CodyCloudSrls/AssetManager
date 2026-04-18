<?php

namespace App\Http\Middleware;

use App\Models\Company;
use Closure;
use Illuminate\Http\Request;

class CheckForDebug
{
    /**
     * Handle an incoming request.
     *
     * @param  Request  $request
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        view()->share('debug_in_production', false);

        if ((Company::currentAuthContext()['is_superuser']) && (app()->environment() == 'production') && (config('app.warn_debug') === true) && (config('app.debug') === true)) {
            view()->share('debug_in_production', true);
        }

        return $next($request);
    }
}
