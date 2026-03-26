<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as Middleware;

class VerifyCsrfToken extends Middleware
{
    /**
     * The URIs that should be excluded from CSRF verification.
     *
     * @var array<int, string>
     */
    protected $except = [
        //
    ];

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return mixed
     *
     * @throws \Illuminate\Session\TokenMismatchException
     */
    public function handle($request, Closure $next)
    {
        // Always skip CSRF verification in testing environment
        if ($this->isTestingEnvironment()) {
            return $next($request);
        }

        return parent::handle($request, $next);
    }

    /**
     * Determine if the request has a valid CSRF token.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return bool
     */
    protected function tokensMatch($request)
    {
        // Always return true in testing environment
        if ($this->isTestingEnvironment()) {
            return true;
        }

        return parent::tokensMatch($request);
    }

    /**
     * Determine if we are in a testing environment.
     *
     * @return bool
     */
    protected function isTestingEnvironment()
    {
        return $this->app->environment('testing')
            || $this->app->runningUnitTests()
            || defined('PHPUNIT_COMPOSER_INSTALL')
            || (isset($_ENV['APP_ENV']) && $_ENV['APP_ENV'] === 'testing')
            || (isset($_SERVER['APP_ENV']) && $_SERVER['APP_ENV'] === 'testing');
    }
}
