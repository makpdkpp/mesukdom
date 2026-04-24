@extends('layouts.adminlte')

@section('content')
<div class="row">
    <div class="col-lg-3 col-6">
        <div class="small-box bg-primary">
            <div class="inner">
                <h3>{{ number_format(data_get($summary, 'total_requests', 0)) }}</h3>
                <p>Total API Requests</p>
            </div>
            <div class="icon"><i class="fas fa-exchange-alt"></i></div>
        </div>
    </div>
    <div class="col-lg-3 col-6">
        <div class="small-box bg-info">
            <div class="inner">
                <h3>{{ number_format((float) data_get($summary, 'avg_duration_ms', 0), 2) }} ms</h3>
                <p>Average Latency</p>
            </div>
            <div class="icon"><i class="fas fa-stopwatch"></i></div>
        </div>
    </div>
    <div class="col-lg-3 col-6">
        <div class="small-box bg-warning">
            <div class="inner">
                <h3>{{ number_format(data_get($summary, 'error_4xx', 0)) }}</h3>
                <p>4xx Responses</p>
            </div>
            <div class="icon"><i class="fas fa-exclamation-triangle"></i></div>
        </div>
    </div>
    <div class="col-lg-3 col-6">
        <div class="small-box bg-danger">
            <div class="inner">
                <h3>{{ number_format(data_get($summary, 'error_5xx', 0)) }}</h3>
                <p>5xx Responses</p>
            </div>
            <div class="icon"><i class="fas fa-bug"></i></div>
        </div>
    </div>
</div>

<div class="card card-outline card-secondary">
    <div class="card-header">
        <h3 class="card-title mb-0">API Monitoring Overview</h3>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-4 mb-3">
                <div class="border rounded p-3 h-100">
                    <div class="text-muted text-uppercase small">Aggregation Store</div>
                    <div class="h5 mb-1">{{ $redisAvailable ? 'Redis online' : 'Redis unavailable' }}</div>
                    <div class="small text-muted">Request logs are still written even when Redis is down.</div>
                    @if(! $redisAvailable && !empty($redisError))
                        <div class="small text-muted mt-2">{{ $redisError }}</div>
                    @endif
                </div>
            </div>
            <div class="col-md-4 mb-3">
                <div class="border rounded p-3 h-100">
                    <div class="text-muted text-uppercase small">Tracked Endpoints</div>
                    <div class="h5 mb-1">{{ number_format(data_get($summary, 'tracked_routes', 0)) }}</div>
                    <div class="small text-muted">Grouped by route name or URI pattern to avoid high-cardinality noise.</div>
                </div>
            </div>
            <div class="col-md-4 mb-3">
                <div class="border rounded p-3 h-100">
                    <div class="text-muted text-uppercase small">Prometheus Ready</div>
                    <div class="h5 mb-1">Histogram-ready buckets</div>
                    <div class="small text-muted">Latency buckets stored today can be exported to Prometheus and visualized in Grafana later.</div>
                </div>
            </div>
        </div>
        <div class="alert alert-light border mb-0">
            <strong>Current pipeline:</strong> structured logs per request + Redis counters, sums, min/max, and latency buckets.
            <br>
            <strong>Future exporter:</strong> map these same counters to <code>requests_total</code>, <code>request_duration_ms_sum</code>, and histogram buckets for Prometheus.
        </div>
    </div>
</div>

<div class="row">
    <div class="col-lg-12">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title mb-0">Request Trend</h3>
            </div>
            <div class="card-body">
                <div class="position-relative" style="height: 320px;">
                    <canvas id="api-overall-trend"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card card-outline card-secondary">
    <div class="card-header">
        <h3 class="card-title mb-0">Monitored Endpoint Charts</h3>
    </div>
    <div class="card-body">
        <div class="text-muted small mb-2">Every route with <code>api.monitor</code> gets its own line chart below, even if it has not received traffic yet.</div>
        <div class="small text-muted mb-0">Currently tracking {{ number_format($routes->count()) }} monitored endpoint chart(s) below, plus 1 overall request trend chart above.</div>
    </div>
</div>

<div class="row">
    @foreach($routes as $route)
        <div class="col-xl-6">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title mb-0">{{ $route['route'] }}</h3>
                </div>
                <div class="card-body">
                    <div class="small text-muted mb-1">{{ $route['method'] }} {{ $route['path'] }}</div>
                    <div class="small text-muted mb-3">Requests: {{ number_format($route['count']) }} | Avg latency: {{ number_format((float) $route['avg_duration_ms'], 2) }} ms</div>
                    <div class="position-relative" style="height: 320px;">
                        <canvas id="chart-{{ $route['route_key'] }}"></canvas>
                    </div>
                </div>
            </div>
        </div>
    @endforeach
</div>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h3 class="card-title mb-0">Endpoint Metrics</h3>
        <span class="text-muted small">Buckets: <= {{ implode(' ms, <= ', $histogramBuckets) }} ms</span>
    </div>
    <div class="card-body table-responsive p-0">
        <table class="table table-hover text-nowrap mb-0">
            <thead>
            <tr>
                <th>Method</th>
                <th>Route</th>
                <th>Path</th>
                <th>Requests</th>
                <th>Avg</th>
                <th>Min</th>
                <th>Max</th>
                <th>Last</th>
                <th>2xx</th>
                <th>4xx</th>
                <th>5xx</th>
                <th>Updated</th>
            </tr>
            </thead>
            <tbody>
            @forelse($routes as $route)
                <tr>
                    <td>{{ $route['method'] }}</td>
                    <td>{{ $route['route'] }}</td>
                    <td>{{ $route['path'] }}</td>
                    <td>{{ number_format($route['count']) }}</td>
                    <td>{{ number_format((float) $route['avg_duration_ms'], 2) }} ms</td>
                    <td>{{ number_format((float) $route['min_duration_ms'], 2) }} ms</td>
                    <td>{{ number_format((float) $route['max_duration_ms'], 2) }} ms</td>
                    <td>{{ number_format((float) $route['last_duration_ms'], 2) }} ms</td>
                    <td>{{ number_format($route['status_2xx']) }}</td>
                    <td>{{ number_format($route['status_4xx']) }}</td>
                    <td>{{ number_format($route['status_5xx']) }}</td>
                    <td>{{ $route['updated_at'] ?? '-' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="12" class="text-center text-muted py-4">No API traffic recorded yet.</td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
<script>
    const overallSeries = @json($overallSeries);
    const monitoredRoutes = @json($routes->values()->all());

    const chartOptions = (requestsLabel, latencyLabel) => ({
        responsive: true,
        maintainAspectRatio: false,
        interaction: {
            mode: 'index',
            intersect: false,
        },
        scales: {
            yRequests: {
                type: 'linear',
                position: 'left',
                beginAtZero: true,
                ticks: {
                    precision: 0,
                },
                title: {
                    display: true,
                    text: requestsLabel,
                },
            },
            yLatency: {
                type: 'linear',
                position: 'right',
                beginAtZero: true,
                grid: {
                    drawOnChartArea: false,
                },
                title: {
                    display: true,
                    text: latencyLabel,
                },
            },
        },
        plugins: {
            legend: {
                position: 'bottom',
            },
        },
    });

    new Chart(document.getElementById('api-overall-trend'), {
        type: 'line',
        data: {
            labels: overallSeries.labels,
            datasets: [
                {
                    label: 'Requests',
                    data: overallSeries.requests,
                    borderColor: '#0d6efd',
                    backgroundColor: 'rgba(13, 110, 253, 0.15)',
                    yAxisID: 'yRequests',
                    tension: 0.3,
                    fill: true,
                },
                {
                    label: 'Avg latency (ms)',
                    data: overallSeries.avgLatency,
                    borderColor: '#fd7e14',
                    backgroundColor: 'rgba(253, 126, 20, 0.15)',
                    yAxisID: 'yLatency',
                    tension: 0.3,
                    fill: false,
                },
            ],
        },
        options: chartOptions('Requests', 'Avg latency (ms)'),
    });

    const endpointPalette = [
        ['#198754', 'rgba(25, 135, 84, 0.15)', '#dc3545'],
        ['#6f42c1', 'rgba(111, 66, 193, 0.15)', '#fd7e14'],
        ['#0dcaf0', 'rgba(13, 202, 240, 0.15)', '#6610f2'],
        ['#20c997', 'rgba(32, 201, 151, 0.15)', '#e83e8c'],
        ['#ffc107', 'rgba(255, 193, 7, 0.18)', '#0d6efd'],
        ['#fd7e14', 'rgba(253, 126, 20, 0.15)', '#198754'],
    ];

    monitoredRoutes.forEach((route, index) => {
        const canvas = document.getElementById(`chart-${route.route_key}`);

        if (!canvas) {
            return;
        }

        const palette = endpointPalette[index % endpointPalette.length];

        new Chart(canvas, {
            type: 'line',
            data: {
                labels: route.series.labels,
                datasets: [
                    {
                        label: 'Requests',
                        data: route.series.requests,
                        borderColor: palette[0],
                        backgroundColor: palette[1],
                        yAxisID: 'yRequests',
                        tension: 0.3,
                        fill: true,
                    },
                    {
                        label: 'Avg latency (ms)',
                        data: route.series.avgLatency,
                        borderColor: palette[2],
                        backgroundColor: 'transparent',
                        yAxisID: 'yLatency',
                        tension: 0.3,
                        fill: false,
                    },
                ],
            },
            options: chartOptions('Requests', 'Avg latency (ms)'),
        });
    });
</script>
@endpush
