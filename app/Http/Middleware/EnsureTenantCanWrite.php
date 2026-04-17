<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Support\TenantContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class EnsureTenantCanWrite
{
    public function handle(Request $request, Closure $next): Response
    {
        if (in_array($request->method(), ['GET', 'HEAD', 'OPTIONS'], true)) {
            return $next($request);
        }

        $tenant = app(TenantContext::class)->tenant();

        if ($tenant && ! $tenant->canWrite()) {
            abort(403, 'Trial has ended. Please upgrade to continue making changes.');
        }

        return $next($request);
    }
}
