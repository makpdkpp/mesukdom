<?php

namespace App\Http\Middleware;

use App\Models\Tenant;
use App\Support\TenantContext;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use Symfony\Component\HttpFoundation\Response;

class SetCurrentTenant
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! Schema::hasTable('tenants')) {
            return $next($request);
        }

        $tenantId = $request->user()?->tenant_id
            ?: ($request->integer('tenant') ?: (int) $request->session()->get('tenant_id'));

        $tenant = $tenantId
            ? Tenant::query()->find($tenantId)
            : Tenant::query()->orderBy('id')->first();

        if (! $tenant && $request->hasSession() && $request->session()->has('tenant_id')) {
            $request->session()->forget('tenant_id');
        }

        if ($tenant) {
            app(TenantContext::class)->set($tenant);
            View::share('currentTenant', $tenant);

            if ($request->hasSession()) {
                $request->session()->put('tenant_id', $tenant->id);
            }
        }

        return $next($request);
    }
}
