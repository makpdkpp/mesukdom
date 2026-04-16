@extends('layouts.app')

@section('content')
<div class="row">
    <div class="col-lg-4 col-6">
        <div class="small-box bg-primary"><div class="inner"><h3>{{ $tenantCount }}</h3><p>Total Tenants</p></div><div class="icon"><i class="fas fa-city"></i></div></div>
    </div>
    <div class="col-lg-4 col-6">
        <div class="small-box bg-success"><div class="inner"><h3>{{ $activeUsers }}</h3><p>Active Users</p></div><div class="icon"><i class="fas fa-user-check"></i></div></div>
    </div>
    <div class="col-lg-4 col-6">
        <div class="small-box bg-info"><div class="inner"><h3>{{ number_format($saasRevenue, 0) }}</h3><p>SaaS Revenue</p></div><div class="icon"><i class="fas fa-coins"></i></div></div>
    </div>
</div>

<div class="card">
    <div class="card-header"><h3 class="card-title">System Monitoring & Notification Logs</h3></div>
    <div class="card-body table-responsive p-0">
        <table class="table table-hover text-nowrap">
            <thead><tr><th>Channel</th><th>Event</th><th>Target</th><th>Status</th><th>Message</th></tr></thead>
            <tbody>
            @forelse($notificationLogs as $log)
                <tr>
                    <td>{{ strtoupper($log->channel) }}</td>
                    <td>{{ $log->event }}</td>
                    <td>{{ $log->target }}</td>
                    <td>{{ ucfirst($log->status) }}</td>
                    <td>{{ $log->message }}</td>
                </tr>
            @empty
                <tr><td colspan="5" class="text-center text-muted">No logs yet</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
