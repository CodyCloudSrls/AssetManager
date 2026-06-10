<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Validation\ValidationException;

class RejectEmailHeaderInjection
{
    public function handle(Request $request, Closure $next)
    {
        $errors = [];

        foreach (Arr::dot($request->all()) as $key => $value) {
            if (! is_string($value) || ! str_contains(strtolower((string) $key), 'email')) {
                continue;
            }

            if (str_contains($value, "\r") || str_contains($value, "\n")) {
                $errors[$key] = trans('validation.email', ['attribute' => str_replace(['.', '_'], ' ', (string) $key)]);
            }
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }

        return $next($request);
    }
}
