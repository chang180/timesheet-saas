<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use App\Models\Company;
use App\Tenancy\TenantContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

trait BelongsToTenant
{
    /**
     * Boot the trait and register tenancy hooks.
     */
    public static function bootBelongsToTenant(): void
    {
        static::creating(function (Model $model): void {
            if (! app()->bound(TenantContext::class)) {
                return;
            }

            if (! $model->getAttribute('company_id')) {
                $model->setAttribute('company_id', app(TenantContext::class)->companyId());
            }
        });

        static::addGlobalScope('tenant', function (Builder $builder): void {
            if (! app()->bound(TenantContext::class)) {
                return;
            }

            $builder->where($builder->qualifyColumn('company_id'), app(TenantContext::class)->companyId());
        });
    }

    public function scopeForTenant(Builder $builder, TenantContext|Company|int $tenant): Builder
    {
        $companyId = match (true) {
            $tenant instanceof TenantContext => $tenant->companyId(),
            $tenant instanceof Company => $tenant->getKey(),
            default => $tenant,
        };

        return $builder->where($builder->qualifyColumn('company_id'), $companyId);
    }
}
