@extends('layouts.adminlte')

@section('content')
<div class="row">
    <div class="col-lg-3 col-6">
        <div class="small-box bg-primary">
            <div class="inner">
                <h3>{{ $tenantCount }}</h3>
                <p>Total Tenants</p>
            </div>
            <div class="icon"><i class="fas fa-city"></i></div>
        </div>
    </div>
    <div class="col-lg-3 col-6">
        <div class="small-box bg-success">
            <div class="inner">
                <h3>{{ $activeUsers }}</h3>
                <p>Active Users</p>
            </div>
            <div class="icon"><i class="fas fa-user-check"></i></div>
        </div>
    </div>
    <div class="col-lg-3 col-6">
        <div class="small-box bg-info">
            <div class="inner">
                <h3>{{ number_format($saasRevenue, 0) }}</h3>
                <p>SaaS Revenue</p>
            </div>
            <div class="icon"><i class="fas fa-coins"></i></div>
        </div>
    </div>
    <div class="col-lg-3 col-6">
        <div class="small-box bg-dark">
            <div class="inner">
                <h3>{{ $slipOkUsageTotal }}</h3>
                <p>SlipOK Calls This Month</p>
            </div>
            <div class="icon"><i class="fas fa-receipt"></i></div>
        </div>
    </div>
</div>

<div class="card card-outline card-secondary">
    <div class="card-header">
        <h3 class="card-title mb-0">System Monitoring</h3>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-4 mb-3">
                <div class="border rounded p-3 h-100">
                    <div class="text-muted text-uppercase small">Server Status</div>
                    <div class="h5 mb-1">{{ $serverStatus }}</div>
                    <div class="small text-muted">PHP {{ PHP_VERSION }}</div>
                </div>
            </div>
            <div class="col-md-4 mb-3">
                <div class="border rounded p-3 h-100">
                    <div class="text-muted text-uppercase small">Queue Status</div>
                    <div class="h5 mb-1">{{ strtoupper($queueConnection) }}</div>
                    <div class="small text-muted">
                        @if($pendingJobs === null)
                            Pending jobs: N/A
                        @else
                            Pending jobs: {{ $pendingJobs }}
                        @endif
                    </div>
                </div>
            </div>
            <div class="col-md-4 mb-3">
                <div class="border rounded p-3 h-100">
                    <div class="text-muted text-uppercase small">Redis</div>
                    <div class="h5 mb-1">
                        @if(data_get($redisHealth, 'ok'))
                            OK
                        @else
                            FAIL
                        @endif
                    </div>
                    <div class="small text-muted">Client: {{ data_get($redisHealth, 'client', 'n/a') }}</div>
                    <div class="small text-muted">
                        Default:
                        @if(data_get($redisHealth, 'connections.default.ok'))
                            {{ data_get($redisHealth, 'connections.default.ping', 'PONG') }}
                        @else
                            Error
                        @endif
                    </div>
                    @if(!data_get($redisHealth, 'connections.default.ok') && data_get($redisHealth, 'connections.default.error'))
                        <div class="small text-muted">{{ data_get($redisHealth, 'connections.default.error') }}</div>
                    @endif
                    <div class="small text-muted">
                        Line:
                        @if(data_get($redisHealth, 'connections.line.ok'))
                            {{ data_get($redisHealth, 'connections.line.ping', 'PONG') }}
                        @else
                            Error
                        @endif
                    </div>
                    @if(!data_get($redisHealth, 'connections.line.ok') && data_get($redisHealth, 'connections.line.error'))
                        <div class="small text-muted">{{ data_get($redisHealth, 'connections.line.error') }}</div>
                    @endif
                </div>
            </div>
            <div class="col-md-4 mb-3">
                <div class="border rounded p-3 h-100">
                    <div class="text-muted text-uppercase small">Maintenance</div>
                    <div class="h5 mb-2">Migrate</div>
                    <form method="POST" action="{{ route('admin.maintenance.migrate') }}">
                        @csrf
                        <div class="form-group mb-2">
                            <input type="password" name="migrate_token" class="form-control form-control-sm" placeholder="MIGRATE_TOKEN" autocomplete="off" required>
                        </div>
                        <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Run migrations now?');">Run migrations</button>
                    </form>
                    @if(session('migrateOutput'))
                        <pre class="mt-2 mb-0 small" style="white-space: pre-wrap;">{{ session('migrateOutput') }}</pre>
                    @endif
                </div>
            </div>
            <div class="col-md-4 mb-3">
                <div class="border rounded p-3 h-100">
                    <div class="text-muted text-uppercase small">Failed Jobs</div>
                    <div class="h5 mb-1">{{ $failedJobsCount }}</div>
                    <div class="small text-muted">From failed_jobs table</div>
                </div>
            </div>
            <div class="col-md-4 mb-3">
                <div class="border rounded p-3 h-100">
                    <div class="text-muted text-uppercase small">API Usage</div>
                    <div class="h5 mb-1">{{ $apiUsageTotal }}</div>
                    <div class="small text-muted">SlipOK calls this month</div>
                </div>
            </div>
            <div class="col-md-4 mb-3">
                <div class="border rounded p-3 h-100">
                    <div class="text-muted text-uppercase small">Notification Logs</div>
                    <div class="h5 mb-1">{{ $notificationLogs->count() }}</div>
                    <div class="small text-muted">Latest {{ $notificationLogs->count() }} records</div>
                </div>
            </div>
            <div class="col-md-4 mb-3">
                <div class="border rounded p-3 h-100">
                    <div class="text-muted text-uppercase small">Payment Logs</div>
                    <div class="h5 mb-1">{{ $paymentLogs->count() }}</div>
                    <div class="small text-muted">Latest {{ $paymentLogs->count() }} records</div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header"><h3 class="card-title">Notification Logs</h3></div>
            <div class="card-body table-responsive p-0">
                <table class="table table-hover text-nowrap mb-0">
                    <thead><tr><th>Channel</th><th>Event</th><th>Status</th><th>Time</th></tr></thead>
                    <tbody>
                    @forelse($notificationLogs as $log)
                        <tr>
                            <td>{{ strtoupper($log->channel) }}</td>
                            <td>{{ $log->event }}</td>
                            <td>{{ ucfirst($log->status) }}</td>
                            <td>{{ $log->created_at?->format('Y-m-d H:i') }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="text-center text-muted">No notification logs</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header"><h3 class="card-title">Payment Logs</h3></div>
            <div class="card-body table-responsive p-0">
                <table class="table table-hover text-nowrap mb-0">
                    <thead><tr><th>Receipt</th><th>Status</th><th>Amount</th><th>Time</th></tr></thead>
                    <tbody>
                    @forelse($paymentLogs as $payment)
                        <tr>
                            <td>{{ $payment->receipt_no ?: 'N/A' }}</td>
                            <td>{{ ucfirst($payment->status) }}</td>
                            <td>{{ number_format((float) $payment->amount, 2) }}</td>
                            <td>{{ $payment->created_at?->format('Y-m-d H:i') }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="text-center text-muted">No payment logs</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
