<?php

namespace App\Models\Concerns;

use App\Models\Tenant;
use App\Support\TenantContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait BelongsToTenant
{
    public static function bootBelongsToTenant(): void
    {
        static::creating(function ($model): void {
            if (blank($model->tenant_id) && app(TenantContext::class)->id()) {
                $model->tenant_id = app(TenantContext::class)->id();
            }
        });

        static::addGlobalScope('tenant', function (Builder $builder): void {
            $tenantId = app(TenantContext::class)->id();

            if ($tenantId) {
                $builder->where($builder->qualifyColumn('tenant_id'), $tenantId);
            }
        });
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }
}
