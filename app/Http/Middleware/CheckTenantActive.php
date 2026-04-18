<?php

namespace App\Http\Middleware;

use App\Models\Tenant;
use App\Support\TenantContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckTenantActive
{
    public function handle(Request $request, Closure $next): Response
    {
        $tenant = app(TenantContext::class)->tenant();
        $userTenantId = $request->user()?->tenant_id;

        if (! $tenant && $userTenantId) {
            $tenant = Tenant::query()->find((int) $userTenantId);
        }

        if ($tenant === null && $userTenantId) {
            abort(403, 'Your tenant is no longer available. Please contact support.');
        }

        if ($tenant && $tenant->status === 'suspended') {
            abort(403, 'Your account has been suspended. Please contact support.');
        }

        if ($tenant && $tenant->status === 'pending_checkout' && ! $request->routeIs('app.billing*')) {
            return redirect()->route('app.billing')
                ->with('warning', 'Please complete checkout to activate your package.');
        }

        return $next($request);
    }
}
