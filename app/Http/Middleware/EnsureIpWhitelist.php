<?php

namespace App\Http\Middleware;

use App\Support\IpMatcher;
use App\Tenancy\TenantContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureIpWhitelist
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $tenant = app()->bound(TenantContext::class)
            ? app(TenantContext::class)
            : null;

        if (! $tenant) {
            return $next($request);
        }

        $whitelist = $tenant->settings()?->login_ip_whitelist ?? [];

        if (empty($whitelist)) {
            return $next($request);
        }

        $clientIp = $request->ip();

        if ($clientIp === null) {
            abort(403, __('無法取得 IP 位址。'));
        }

        if (! IpMatcher::matches($clientIp, $whitelist)) {
            abort(403, __('您的 IP 位址不在允許名單內。'));
        }

        return $next($request);
    }
}
