@extends('layouts.adminlte')

@section('content')
<div class="card card-outline card-warning">
    <div class="card-header d-flex align-items-center justify-content-between">
        <h3 class="card-title"><i class="fas fa-tools mr-2"></i>Repair Requests</h3>
        <form method="GET" action="{{ route('app.repairs') }}" class="form-inline" style="gap:8px;">
            <label for="status" class="mb-0 text-muted small">Status</label>
            <select id="status" name="status" class="form-control form-control-sm" onchange="this.form.submit()">
                <option value="open" @selected($statusFilter === 'open')>Open</option>
                <option value="all" @selected($statusFilter === 'all')>All</option>
                <option value="pending" @selected($statusFilter === 'pending')>Pending</option>
                <option value="in_progress" @selected($statusFilter === 'in_progress')>In Progress</option>
                <option value="resolved" @selected($statusFilter === 'resolved')>Resolved</option>
            </select>
        </form>
    </div>
    <div class="card-body table-responsive p-0">
        <table class="table table-hover mb-0">
            <thead>
                <tr>
                    <th>Submitted</th>
                    <th>Resident</th>
                    <th>Room</th>
                    <th>Issue</th>
                    <th>Source</th>
                    <th>Status</th>
                    <th style="width: 180px;">Update</th>
                </tr>
            </thead>
            <tbody>
                @forelse($repairs as $repair)
                    <tr>
                        <td>{{ $repair->submitted_at?->format('d/m/Y H:i') ?? $repair->created_at?->format('d/m/Y H:i') }}</td>
                        <td>{{ $repair->customer?->name ?? '-' }}</td>
                        <td>{{ $repair->room?->room_number ?? '-' }}</td>
                        <td style="max-width: 360px; white-space: normal;">
                            <div class="font-weight-bold">{{ $repair->title }}</div>
                            <div class="small text-muted">{{ $repair->description }}</div>
                        </td>
                        <td><span class="badge badge-light">{{ $repair->source }}</span></td>
                        <td>
                            @php
                                $statusBadge = [
                                    'pending' => 'warning',
                                    'in_progress' => 'primary',
                                    'resolved' => 'success',
                                ][$repair->status] ?? 'secondary';
                            @endphp
                            <span class="badge badge-{{ $statusBadge }}">{{ strtoupper(str_replace('_', ' ', $repair->status)) }}</span>
                        </td>
                        <td>
                            <form method="POST" action="{{ route('app.repairs.update', $repair) }}" class="d-flex" style="gap:6px;">
                                @csrf
                                @method('PATCH')
                                <select name="status" class="form-control form-control-sm">
                                    <option value="pending" @selected($repair->status === 'pending')>Pending</option>
                                    <option value="in_progress" @selected($repair->status === 'in_progress')>In Progress</option>
                                    <option value="resolved" @selected($repair->status === 'resolved')>Resolved</option>
                                </select>
                                <button type="submit" class="btn btn-sm btn-outline-primary">Save</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center text-muted py-4">No repair requests yet</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
