<?php

namespace App\Http\Middleware;

use App\Models\Company;
use App\Tenancy\TenantContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureTenantScope
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $company = $this->resolveCompany($request);

        if (! $company instanceof Company) {
            abort(404, __('Tenant not found.'));
        }

        if ($company->status !== 'active') {
            abort(423, __('Tenant has been suspended.'));
        }

        $context = TenantContext::fromCompany($company);

        app()->instance(TenantContext::class, $context);
        $request->attributes->set('tenant', $context);

        return $next($request);
    }

    protected function resolveCompany(Request $request): ?Company
    {
        $routeParameter = $request->route('company');

        if ($routeParameter instanceof Company) {
            return $routeParameter;
        }

        $slug = is_string($routeParameter) ? $routeParameter : null;

        $slug ??= (string) $request->route('company_slug');
        $slug = $slug !== '' ? $slug : $this->resolveFromSubdomain($request);

        if (! $slug) {
            return null;
        }

        return Company::query()
            ->with('settings')
            ->where('slug', $slug)
            ->first();
    }

    protected function resolveFromSubdomain(Request $request): ?string
    {
        if (config('tenant.slug_mode') !== 'subdomain') {
            return null;
        }

        $host = $request->getHost();
        $primaryDomain = (string) config('tenant.primary_domain');

        if ($host === $primaryDomain) {
            return null;
        }

        if (! str_ends_with($host, $primaryDomain)) {
            return null;
        }

        $hostSegments = explode('.', $host);
        $primarySegments = explode('.', $primaryDomain);

        if (count($hostSegments) <= count($primarySegments)) {
            return null;
        }

        return $hostSegments[0] !== '' ? $hostSegments[0] : null;
    }
}
