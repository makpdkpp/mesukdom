@extends('layouts.adminlte')

@section('content')
<div class="row">
    <div class="col-lg-3 col-6">
        <div class="small-box bg-info">
            <div class="inner">
                <h3>{{ $stats['rooms_total'] }}</h3>
                <p>Total Rooms</p>
            </div>
            <div class="icon"><i class="fas fa-building"></i></div>
        </div>
    </div>
    <div class="col-lg-3 col-6">
        <div class="small-box bg-success">
            <div class="inner">
                <h3>{{ $stats['rooms_vacant'] }}</h3>
                <p>Vacant Rooms</p>
            </div>
            <div class="icon"><i class="fas fa-door-open"></i></div>
        </div>
    </div>
    <div class="col-lg-3 col-6">
        <div class="small-box bg-warning">
            <div class="inner">
                <h3>{{ number_format($stats['monthly_revenue'], 0) }}</h3>
                <p>Monthly Revenue</p>
            </div>
            <div class="icon"><i class="fas fa-wallet"></i></div>
        </div>
    </div>
    <div class="col-lg-3 col-6">
        <div class="small-box bg-danger">
            <div class="inner">
                <h3>{{ $stats['overdue_invoices'] }}</h3>
                <p>Overdue Invoices</p>
            </div>
            <div class="icon"><i class="fas fa-exclamation-circle"></i></div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-8">
        <div class="card card-outline card-info">
            <div class="card-header">
                <h3 class="card-title">Revenue Trend</h3>
            </div>
            <div class="card-body">
                <p class="text-muted mb-4">Approved payments from the last 6 months for {{ $tenant->name ?? 'your dormitory' }}</p>
                <div class="d-flex align-items-end justify-content-between" style="gap: 0.75rem; min-height: 240px;">
                    @foreach($revenueTrend as $point)
                        @php($barHeight = max(12, (int) round(($point['total'] / $revenueTrendMax) * 160)))
                        <div class="d-flex flex-column align-items-center flex-fill text-center">
                            <div class="small font-weight-bold text-info mb-2">{{ number_format((float) $point['total'], 0) }}</div>
                            <svg viewBox="0 0 48 170" width="100%" height="170" aria-label="{{ $point['label'] }} revenue {{ number_format((float) $point['total'], 0) }}">
                                <rect x="6" y="{{ 170 - $barHeight }}" width="36" height="{{ $barHeight }}" rx="8" fill="#17a2b8"></rect>
                            </svg>
                            <div class="small text-muted mt-2">{{ $point['label'] }}</div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header"><h3 class="card-title">Dormitory Dashboard</h3></div>
            <div class="card-body">
                <p class="mb-3">Room Status for {{ $tenant->name ?? 'your dormitory' }}</p>
                <div class="table-responsive">
                    <table class="table table-bordered">
                        <thead>
                        <tr>
                            <th>Room</th>
                            <th>Floor</th>
                            <th>Type</th>
                            <th>Price</th>
                            <th>Status</th>
                        </tr>
                        </thead>
                        <tbody>
                        @forelse($rooms as $room)
                            <tr>
                                <td>{{ $room->room_number }}</td>
                                <td>{{ $room->floor }}</td>
                                <td>{{ $room->room_type }}</td>
                                <td>{{ number_format((float) $room->price, 2) }}</td>
                                <td><span class="badge badge-{{ $room->status === 'occupied' ? 'success' : ($room->status === 'maintenance' ? 'warning' : 'secondary') }}">{{ ucfirst($room->status) }}</span></td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="text-center text-muted">No rooms yet</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card card-outline card-primary">
            <div class="card-header"><h3 class="card-title">Quick Access</h3></div>
            <div class="card-body">
                <a href="{{ route('app.rooms') }}" class="btn btn-primary btn-block mb-2">Manage Rooms</a>
                <a href="{{ route('app.customers') }}" class="btn btn-outline-primary btn-block mb-2">Manage Residents</a>
                <a href="{{ route('app.invoices') }}" class="btn btn-outline-success btn-block">Issue Invoice</a>
            </div>
        </div>
        <div class="card card-outline card-success">
            <div class="card-header"><h3 class="card-title">Recent Invoices</h3></div>
            <div class="card-body p-0">
                <ul class="list-group list-group-flush">
                    @forelse($recentInvoices as $invoice)
                        <li class="list-group-item">
                            <strong>{{ $invoice->invoice_no }}</strong><br>
                            <small>{{ $invoice->customer->name ?? 'Resident' }} • {{ number_format((float) $invoice->total_amount, 2) }} THB</small>
                        </li>
                    @empty
                        <li class="list-group-item text-muted">No invoices yet</li>
                    @endforelse
                </ul>
            </div>
        </div>
    </div>
</div>
@endsection
