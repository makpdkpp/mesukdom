<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Support\ApiMonitorMetrics;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Redis;
use Tests\TestCase;

final class ApiMonitorMetricsTest extends TestCase
{
    public function test_record_stores_request_aggregates_in_redis(): void
    {
        Carbon::setTestNow('2026-04-24 10:15:00');
        $connection = new FakeRedisConnection();
        Redis::shouldReceive('connection')->andReturn($connection);

        $request = Request::create('/api/line/webhook', 'POST');
        $request->setRouteResolver(static fn (): object => new class
        {
            public function getName(): string
            {
                return 'api.line.webhook';
            }

            public function uri(): string
            {
                return 'api/line/webhook';
            }
        });

        $metrics = app(ApiMonitorMetrics::class);
        $metrics->record($request, 200, 82.45);

        $summary = $connection->hGetAll('api-monitor:route:post_api_line_webhook:summary');
        $buckets = $connection->hGetAll('api-monitor:route:post_api_line_webhook:buckets');

        $this->assertSame('POST', $summary['method']);
        $this->assertSame('api.line.webhook', $summary['route']);
        $this->assertSame('/api/line/webhook', $summary['path']);
        $this->assertSame('1', $summary['count']);
        $this->assertSame('82.45', $summary['duration_sum_ms']);
        $this->assertSame('82.45', $summary['duration_min_ms']);
        $this->assertSame('82.45', $summary['duration_max_ms']);
        $this->assertSame('1', $summary['status_2xx']);
        $this->assertSame('1', $buckets['le_100']);
        $this->assertSame('1', $buckets['le_250']);
        $this->assertSame('1', $connection->hGetAll('api-monitor:series:overall:2026-04-24 10:00')['count']);
        $this->assertSame('1', $connection->hGetAll('api-monitor:series:post_api_line_webhook:2026-04-24 10:00')['count']);

        Carbon::setTestNow();
    }

    public function test_dashboard_payload_reads_redis_aggregates(): void
    {
        Carbon::setTestNow('2026-04-24 08:20:00');
        $connection = new FakeRedisConnection();
        Redis::shouldReceive('connection')->andReturn($connection);

        $request = Request::create('/app/payments/55/recheck-slip', 'PATCH');
        $request->setRouteResolver(static fn (): object => new class
        {
            public function getName(): string
            {
                return 'app.payments.recheck-slip';
            }

            public function uri(): string
            {
                return 'app/payments/{payment}/recheck-slip';
            }
        });

        $metrics = app(ApiMonitorMetrics::class);
        $metrics->record($request, 200, 100.00);
        Carbon::setTestNow('2026-04-24 09:10:00');
        $metrics->record($request, 500, 300.00);

        $payload = $metrics->dashboardPayload();

        $this->assertTrue($payload['redisAvailable']);
        $this->assertSame(2, $payload['summary']['total_requests']);
        $this->assertSame(200.0, $payload['summary']['avg_duration_ms']);
        $this->assertSame(1, $payload['summary']['error_5xx']);
        $this->assertGreaterThanOrEqual(6, count($payload['routes']));
        $targetRoute = collect($payload['routes'])->firstWhere('route', 'app.payments.recheck-slip');
        $this->assertNotNull($targetRoute);
        $this->assertSame(2, $targetRoute['count']);
        $this->assertSame(1, $targetRoute['status_5xx']);
        $this->assertCount(12, $targetRoute['series']['labels']);
        $this->assertCount(12, $payload['overallSeries']['labels']);
        $this->assertSame([1, 1], array_values(array_filter($targetRoute['series']['requests'])));

        Carbon::setTestNow();
    }
}

final class FakeRedisConnection
{
    /** @var array<string, array<string, true>> */
    private array $sets = [];

    /** @var array<string, array<string, string>> */
    private array $hashes = [];

    public function sadd(string $key, string $member): int
    {
        $exists = isset($this->sets[$key][$member]);
        $this->sets[$key][$member] = true;

        return $exists ? 0 : 1;
    }

    public function hSet(string $key, string $field, string $value): int
    {
        $this->hashes[$key][$field] = $value;

        return 1;
    }

    public function hIncrBy(string $key, string $field, int $value): int
    {
        $current = (int) ($this->hashes[$key][$field] ?? '0');
        $next = $current + $value;
        $this->hashes[$key][$field] = (string) $next;

        return $next;
    }

    public function hIncrByFloat(string $key, string $field, float $value): float
    {
        $current = (float) ($this->hashes[$key][$field] ?? '0');
        $next = $current + $value;
        $this->hashes[$key][$field] = number_format($next, 2, '.', '');

        return $next;
    }

    public function hGet(string $key, string $field): ?string
    {
        return $this->hashes[$key][$field] ?? null;
    }

    /**
     * @return array<int, string>
     */
    public function sMembers(string $key): array
    {
        return array_keys($this->sets[$key] ?? []);
    }

    /**
     * @return array<string, string>
     */
    public function hGetAll(string $key): array
    {
        return $this->hashes[$key] ?? [];
    }
}
