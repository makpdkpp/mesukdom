<?php

namespace App\Providers;

use App\Models\User;
use App\Support\TenantContext;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
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

        RateLimiter::for('line-webhook', function (Request $request): Limit {
            return Limit::perMinute($this->rateLimitValue(config('services.line.webhook_rate_limit'), 120))
                ->by((string) $request->ip());
        });

        RateLimiter::for('stripe-webhook', function (Request $request): Limit {
            return Limit::perMinute($this->rateLimitValue(config('services.stripe.webhook_rate_limit'), 60))
                ->by((string) $request->ip());
        });
    }

    private function rateLimitValue(mixed $value, int $default): int
    {
        if (is_int($value)) {
            return $value;
        }

        if (is_string($value) && $value !== '' && is_numeric($value)) {
            return (int) $value;
        }

        return $default;
    }
}
