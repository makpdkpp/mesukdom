<?php

namespace App\Http\Middleware;

use App\Support\TenantContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckTenantActive
{
    public function handle(Request $request, Closure $next): Response
    {
        $tenant = app(TenantContext::class)->tenant();

        if ($tenant && $tenant->status === 'suspended') {
            abort(403, 'Your account has been suspended. Please contact support.');
        }

        return $next($request);
    }
}
