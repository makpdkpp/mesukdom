<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Support\ApiMonitorMetrics;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

final class CaptureApiMetrics
{
    public function __construct(
        private readonly ApiMonitorMetrics $metrics,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        if (! (bool) config('services.api_monitor.enabled', true)) {
            /** @var Response $response */
            $response = $next($request);

            return $response;
        }

        $startedAt = hrtime(true);

        try {
            /** @var Response $response */
            $response = $next($request);
            $durationMs = (hrtime(true) - $startedAt) / 1_000_000;
            $this->metrics->record($request, $response->getStatusCode(), $durationMs);

            return $response;
        } catch (Throwable $e) {
            $durationMs = (hrtime(true) - $startedAt) / 1_000_000;
            $this->metrics->record($request, 500, $durationMs);

            throw $e;
        }
    }
}
