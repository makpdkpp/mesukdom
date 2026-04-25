<?php

use App\Http\Middleware\CheckTenantActive;
use App\Http\Middleware\CaptureApiMetrics;
use App\Http\Middleware\EnsureTenantCanWrite;
use App\Http\Middleware\EnsureUserHasRole;
use App\Http\Middleware\SetCurrentTenant;
use App\Http\Middleware\SetSecurityHeaders;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->trustProxies(
            at: '*',
            headers: Request::HEADER_X_FORWARDED_FOR
                | Request::HEADER_X_FORWARDED_HOST
                | Request::HEADER_X_FORWARDED_PORT
                | Request::HEADER_X_FORWARDED_PROTO
                | Request::HEADER_X_FORWARDED_PREFIX
                | Request::HEADER_X_FORWARDED_AWS_ELB,
        );

        $middleware->alias([
            'auth' => \Illuminate\Auth\Middleware\Authenticate::class,
            'verified' => \Illuminate\Auth\Middleware\EnsureEmailIsVerified::class,
            'api.monitor' => CaptureApiMetrics::class,
            'role' => EnsureUserHasRole::class,
            'tenant.active' => CheckTenantActive::class,
            'tenant.write' => EnsureTenantCanWrite::class,
        ]);

        $middleware->web(append: [
            SetSecurityHeaders::class,
            SetCurrentTenant::class,
        ]);

        $middleware->validateCsrfTokens(except: [
            'line/webhook',
            'line/platform-webhook',
            'stripe/webhook',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
