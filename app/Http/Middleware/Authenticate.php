<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Auth\Middleware\Authenticate as Middleware;
use Illuminate\Http\Request;

class Authenticate extends Middleware
{
    /**
     * Get the path the user should be redirected to when they are not authenticated.
     *
     * @param  Request  $request
     * @return string|null
     */
    protected function redirectTo($request)
    {
        // Don't redirect API requests - they will throw an exception in unauthenticated()
        if ($request->expectsJson()) {
            return null;
        }

        return route('login');
    }

    /**
     * Handle an unauthenticated user.
     *
     * @param  Request  $request
     * @param  array  $guards
     * @return void
     *
     * @throws \Illuminate\Auth\AuthenticationException
     */
    protected function unauthenticated($request, array $guards)
    {
        // Return JSON response for API requests
        if ($request->is('api/*') || $request->expectsJson()) {
            throw new \Illuminate\Auth\AuthenticationException('Unauthenticated');
        }

        parent::unauthenticated($request, $guards);
    }
}
