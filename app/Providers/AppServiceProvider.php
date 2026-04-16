<?php

namespace App\Providers;

use App\Models\User;
use App\Support\TenantContext;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(TenantContext::class, fn () => new TenantContext());
    }

    public function boot(): void
    {
        Gate::define('accessAdminPortal', fn (User $user): bool => $user->canAccessAdminPortal());
        Gate::define('accessTenantPortal', fn (User $user): bool => $user->canAccessTenantPortal());
    }
}
