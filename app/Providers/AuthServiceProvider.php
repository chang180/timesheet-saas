<?php

namespace App\Providers;

use App\Models\Department;
use App\Models\Division;
use App\Models\Team;
use App\Models\WeeklyReport;
use App\Policies\DepartmentPolicy;
use App\Policies\DivisionPolicy;
use App\Policies\TeamPolicy;
use App\Policies\WeeklyReportPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * @var array<class-string, class-string>
     */
    protected array $policies = [
        Division::class => DivisionPolicy::class,
        Department::class => DepartmentPolicy::class,
        Team::class => TeamPolicy::class,
        WeeklyReport::class => WeeklyReportPolicy::class,
    ];

    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        foreach ($this->policies as $model => $policy) {
            Gate::policy($model, $policy);
        }
    }
}
