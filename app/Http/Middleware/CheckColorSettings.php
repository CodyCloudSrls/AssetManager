<?php

namespace App\Http\Middleware;

use App\Models\Company;
use App\Models\Setting;
use Closure;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Http\Request;

class CheckColorSettings
{
    /**
     * The Guard implementation.
     *
     * @var Guard
     */
    protected $auth;

    /**
     * Create a new filter instance.
     *
     * @return void
     */
    public function __construct(Guard $auth)
    {
        $this->auth = $auth;
    }

    /**
     * Handle an incoming request.
     *
     * @param  Request  $request
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $user = Company::currentAuthUserData();

        // Set defaults in case this is accessed via the /setup screen
        $nav_color = '#ffffff';
        $link_dark_color = '#89c9ed';
        $link_light_color = '#3c8dbc';

        if ($settings = Setting::getSettings()) {
            $nav_color = $settings->nav_link_color;
            $link_dark_color = $settings->link_dark_color;
            $link_light_color = $settings->link_light_color;
        }

        // Override system settings
        if ($user) {

            if ($user->nav_link_color) {
                $nav_color = $user->nav_link_color;
            }
            if ($user->link_dark_color) {
                $link_dark_color = $user->link_dark_color;
            }
            if ($user->nav_link_color) {
                $link_light_color = $user->link_light_color;
            }
        }

        view()->share('nav_link_color', $nav_color);
        view()->share('link_dark_color', $link_dark_color);
        view()->share('link_light_color', $link_light_color);

        return $next($request);

    }
}
