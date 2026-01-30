<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureHqAdmin
{
    /**
     * Handle an incoming request.
     *
     * Ensures only users with 'hq_admin' role can access HQ endpoints.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || ! $user->isHqAdmin()) {
            abort(403, 'Forbidden. HQ admin access required.');
        }

        return $next($request);
    }
}
