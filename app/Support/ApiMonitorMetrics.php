<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Str;
use Illuminate\Support\Collection;
use Illuminate\Routing\Route;
use Throwable;

final class ApiMonitorMetrics
{
    /**
     * Buckets mirror the histogram shape we would expose to Prometheus later.
     *
     * @var list<int>
     */
    private const LATENCY_BUCKETS_MS = [25, 50, 100, 250, 500, 1000, 2500, 5000];

    private const SERIES_WINDOW_HOURS = 12;

    public function record(Request $request, int $statusCode, float $durationMs): void
    {
        [$method, $routeName, $routePath] = $this->routeContext($request);
        $routeKey = $this->routeKey($method, $routeName, $routePath);
        $recordedAt = now();

        Log::channel((string) config('services.api_monitor.log_channel', 'stack'))->info('api.monitor', [
            'method' => $method,
            'route' => $routeName,
            'path' => $routePath,
            'status' => $statusCode,
            'status_group' => $this->statusGroup($statusCode),
            'duration_ms' => round($durationMs, 2),
            'recorded_at' => $recordedAt->toIso8601String(),
            'ip' => $request->ip(),
        ]);

        try {
            $redis = Redis::connection((string) config('services.api_monitor.redis_connection', 'default'));
            $summaryKey = $this->summaryKey($routeKey);
            $bucketKey = $this->bucketKey($routeKey);

            $redis->sadd($this->routesIndexKey(), $routeKey);
            $redis->hSet($summaryKey, 'method', $method);
            $redis->hSet($summaryKey, 'route', $routeName);
            $redis->hSet($summaryKey, 'path', $routePath);
            $redis->hSet($summaryKey, 'updated_at', $recordedAt->toIso8601String());
            $redis->hSet($summaryKey, 'last_status', (string) $statusCode);
            $redis->hSet($summaryKey, 'last_duration_ms', $this->formatFloat($durationMs));
            $redis->hIncrBy($summaryKey, 'count', 1);
            $redis->hIncrByFloat($summaryKey, 'duration_sum_ms', $durationMs);
            $redis->hIncrBy($summaryKey, 'status_'.$this->statusGroup($statusCode), 1);

            $this->recordSeries($redis, 'overall', $recordedAt, $durationMs);
            $this->recordSeries($redis, $routeKey, $recordedAt, $durationMs);

            $currentMin = $redis->hGet($summaryKey, 'duration_min_ms');
            $currentMax = $redis->hGet($summaryKey, 'duration_max_ms');

            if ($currentMin === null || (float) $currentMin > $durationMs) {
                $redis->hSet($summaryKey, 'duration_min_ms', $this->formatFloat($durationMs));
            }

            if ($currentMax === null || (float) $currentMax < $durationMs) {
                $redis->hSet($summaryKey, 'duration_max_ms', $this->formatFloat($durationMs));
            }

            foreach (self::LATENCY_BUCKETS_MS as $bucket) {
                if ($durationMs <= $bucket) {
                    $redis->hIncrBy($bucketKey, 'le_'.$bucket, 1);
                }
            }
        } catch (Throwable $e) {
            Log::warning('api.monitor.redis_unavailable', [
                'message' => $e->getMessage(),
            ]);
        }
    }

    public function dashboardPayload(): array
    {
        try {
            $redis = Redis::connection((string) config('services.api_monitor.redis_connection', 'default'));
            $routes = $this->monitoredRoutes()
                ->map(function (array $definition) use ($redis): array {
                    $routeKey = $definition['route_key'];
                    $summary = $redis->hGetAll($this->summaryKey($routeKey));
                    $buckets = $redis->hGetAll($this->bucketKey($routeKey));
                    $count = (int) ($summary['count'] ?? 0);
                    $durationSum = (float) ($summary['duration_sum_ms'] ?? 0);

                    return [
                        'route_key' => $routeKey,
                        'method' => $summary['method'] ?? $definition['method'],
                        'route' => $summary['route'] ?? $definition['route'],
                        'path' => $summary['path'] ?? $definition['path'],
                        'count' => $count,
                        'duration_sum_ms' => $durationSum,
                        'avg_duration_ms' => $count > 0 ? round($durationSum / $count, 2) : 0.0,
                        'min_duration_ms' => (float) ($summary['duration_min_ms'] ?? 0),
                        'max_duration_ms' => (float) ($summary['duration_max_ms'] ?? 0),
                        'last_duration_ms' => (float) ($summary['last_duration_ms'] ?? 0),
                        'status_2xx' => (int) ($summary['status_2xx'] ?? 0),
                        'status_3xx' => (int) ($summary['status_3xx'] ?? 0),
                        'status_4xx' => (int) ($summary['status_4xx'] ?? 0),
                        'status_5xx' => (int) ($summary['status_5xx'] ?? 0),
                        'updated_at' => $summary['updated_at'] ?? null,
                        'latency_buckets' => collect(self::LATENCY_BUCKETS_MS)
                            ->mapWithKeys(fn (int $bucket): array => ['le_'.$bucket => (int) ($buckets['le_'.$bucket] ?? 0)])
                            ->all(),
                        'series' => $this->seriesPayload($redis, $routeKey),
                    ];
                })
                ->sortByDesc('count')
                ->values();

            $totalRequests = (int) $routes->sum('count');
            $totalDuration = (float) $routes->sum('duration_sum_ms');

            return [
                'redisAvailable' => true,
                'routes' => $routes,
                'summary' => [
                    'total_requests' => $totalRequests,
                    'avg_duration_ms' => $totalRequests > 0 ? round($totalDuration / $totalRequests, 2) : 0.0,
                    'error_4xx' => (int) $routes->sum('status_4xx'),
                    'error_5xx' => (int) $routes->sum('status_5xx'),
                    'tracked_routes' => (int) $routes->count(),
                ],
                'histogramBuckets' => self::LATENCY_BUCKETS_MS,
                'overallSeries' => $this->seriesPayload($redis, 'overall'),
            ];
        } catch (Throwable $e) {
            return [
                'redisAvailable' => false,
                'routes' => $this->monitoredRoutes()->map(fn (array $definition): array => [
                    'route_key' => $definition['route_key'],
                    'method' => $definition['method'],
                    'route' => $definition['route'],
                    'path' => $definition['path'],
                    'count' => 0,
                    'duration_sum_ms' => 0.0,
                    'avg_duration_ms' => 0.0,
                    'min_duration_ms' => 0.0,
                    'max_duration_ms' => 0.0,
                    'last_duration_ms' => 0.0,
                    'status_2xx' => 0,
                    'status_3xx' => 0,
                    'status_4xx' => 0,
                    'status_5xx' => 0,
                    'updated_at' => null,
                    'latency_buckets' => collect(self::LATENCY_BUCKETS_MS)
                        ->mapWithKeys(fn (int $bucket): array => ['le_'.$bucket => 0])
                        ->all(),
                    'series' => $this->emptySeriesPayload(),
                ])->values(),
                'summary' => [
                    'total_requests' => 0,
                    'avg_duration_ms' => 0.0,
                    'error_4xx' => 0,
                    'error_5xx' => 0,
                    'tracked_routes' => (int) $this->monitoredRoutes()->count(),
                ],
                'histogramBuckets' => self::LATENCY_BUCKETS_MS,
                'overallSeries' => $this->emptySeriesPayload(),
                'redisError' => class_basename($e).' - '.$e->getMessage(),
            ];
        }
    }

    /**
     * @return array{0:string,1:string,2:string}
     */
    private function routeContext(Request $request): array
    {
        $method = strtoupper($request->method());
        $route = $request->route();
        $routeName = $route?->getName() ?? 'unnamed';
        $routeUri = $route?->uri() ?? ltrim($request->path(), '/');
        $routePath = '/'.ltrim($routeUri, '/');

        return [$method, $routeName, $routePath];
    }

    private function routeKey(string $method, string $routeName, string $routePath): string
    {
        $base = $routeName !== 'unnamed' ? $routeName : $routePath;

        return Str::of($method.'_'.$base)
            ->replace(['/', '.', '{', '}', '-'], '_')
            ->squish()
            ->lower()
            ->trim('_')
            ->value();
    }

    private function statusGroup(int $statusCode): string
    {
        return (string) floor($statusCode / 100).'xx';
    }

    private function summaryKey(string $routeKey): string
    {
        return 'api-monitor:route:'.$routeKey.':summary';
    }

    private function bucketKey(string $routeKey): string
    {
        return 'api-monitor:route:'.$routeKey.':buckets';
    }

    private function routesIndexKey(): string
    {
        return 'api-monitor:routes';
    }

    /**
     * @return Collection<int, array{route_key:string,method:string,route:string,path:string}>
     */
    private function monitoredRoutes(): Collection
    {
        /** @var \Illuminate\Routing\RouteCollectionInterface $routeCollection */
        $routeCollection = app('router')->getRoutes();

        return collect($routeCollection->getRoutes())
            ->filter(fn (Route $route): bool => in_array('api.monitor', $route->gatherMiddleware(), true))
            ->map(function (Route $route): array {
                $method = collect($route->methods())
                    ->reject(static fn (string $candidate): bool => in_array($candidate, ['HEAD', 'OPTIONS'], true))
                    ->first() ?? 'GET';

                $name = $route->getName() ?? 'unnamed';
                $path = '/'.ltrim($route->uri(), '/');

                return [
                    'route_key' => $this->routeKey($method, $name, $path),
                    'method' => $method,
                    'route' => $name,
                    'path' => $path,
                ];
            })
            ->sortBy(fn (array $definition): string => $definition['route'])
            ->values();
    }

    private function recordSeries(object $redis, string $seriesKey, Carbon $recordedAt, float $durationMs): void
    {
        $bucket = $recordedAt->copy()->startOfHour()->format('Y-m-d H:00');
        $key = $this->seriesKey($seriesKey, $bucket);

        $redis->hSet($key, 'bucket', $bucket);
        $redis->hIncrBy($key, 'count', 1);
        $redis->hIncrByFloat($key, 'duration_sum_ms', $durationMs);
    }

    /**
     * @return array{labels: array<int, string>, requests: array<int, int>, avgLatency: array<int, float>}
     */
    private function seriesPayload(object $redis, string $seriesKey): array
    {
        $labels = [];
        $requests = [];
        $avgLatency = [];

        foreach ($this->seriesBuckets() as $bucketAt) {
            $bucket = $bucketAt->format('Y-m-d H:00');
            $series = $redis->hGetAll($this->seriesKey($seriesKey, $bucket));
            $count = (int) ($series['count'] ?? 0);
            $sum = (float) ($series['duration_sum_ms'] ?? 0);

            $labels[] = $bucketAt->format('H:i');
            $requests[] = $count;
            $avgLatency[] = $count > 0 ? round($sum / $count, 2) : 0.0;
        }

        return [
            'labels' => $labels,
            'requests' => $requests,
            'avgLatency' => $avgLatency,
        ];
    }

    /**
     * @return array{labels: array<int, string>, requests: array<int, int>, avgLatency: array<int, float>}
     */
    private function emptySeriesPayload(): array
    {
        $labels = array_map(
            static fn (Carbon $bucketAt): string => $bucketAt->format('H:i'),
            $this->seriesBuckets()
        );

        return [
            'labels' => $labels,
            'requests' => array_fill(0, count($labels), 0),
            'avgLatency' => array_fill(0, count($labels), 0.0),
        ];
    }

    /**
     * @return list<Carbon>
     */
    private function seriesBuckets(): array
    {
        $start = now()->copy()->subHours(self::SERIES_WINDOW_HOURS - 1)->startOfHour();
        $buckets = [];

        for ($index = 0; $index < self::SERIES_WINDOW_HOURS; $index++) {
            $buckets[] = $start->copy()->addHours($index);
        }

        return $buckets;
    }

    private function seriesKey(string $seriesKey, string $bucket): string
    {
        return 'api-monitor:series:'.$seriesKey.':'.$bucket;
    }

    private function formatFloat(float $value): string
    {
        return number_format($value, 2, '.', '');
    }
}
