@extends('layouts.app')

@section('content')
<div class="row">
    <div class="col-md-4">
        <div class="card card-warning">
            <div class="card-header"><h3 class="card-title">Create Contract</h3></div>
            <form method="POST" action="{{ route('app.contracts.store') }}">
                @csrf
                <div class="card-body">
                    <div class="form-group">
                        <label>Resident</label>
                        <select name="customer_id" class="form-control" required>
                            @foreach($customers as $customer)
                                <option value="{{ $customer->id }}">{{ $customer->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Room</label>
                        <select name="room_id" class="form-control" required>
                            @foreach($rooms as $room)
                                <option value="{{ $room->id }}">{{ $room->room_number }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group"><label>Start Date</label><input name="start_date" type="date" class="form-control" required></div>
                    <div class="form-group"><label>End Date</label><input name="end_date" type="date" class="form-control" required></div>
                    <div class="form-group"><label>Deposit</label><input name="deposit" type="number" step="0.01" class="form-control" value="5000" required></div>
                    <div class="form-group"><label>Monthly Rent</label><input name="monthly_rent" type="number" step="0.01" class="form-control"></div>
                    <div class="form-group">
                        <label>Status</label>
                        <select name="status" class="form-control">
                            <option value="active">Active</option>
                            <option value="expired">Expired</option>
                            <option value="cancelled">Cancelled</option>
                        </select>
                    </div>
                </div>
                <div class="card-footer"><button class="btn btn-warning">Save Contract</button></div>
            </form>
        </div>
    </div>
    <div class="col-md-8">
        <div class="card">
            <div class="card-header"><h3 class="card-title">Rental Contracts</h3></div>
            <div class="card-body table-responsive p-0">
                <table class="table table-hover text-nowrap">
                    <thead><tr><th>Resident</th><th>Room</th><th>Period</th><th>Rent</th><th>Status</th></tr></thead>
                    <tbody>
                    @forelse($contracts as $contract)
                        <tr>
                            <td>{{ $contract->customer->name }}</td>
                            <td>{{ $contract->room->room_number }}</td>
                            <td>{{ $contract->start_date->format('d/m/Y') }} - {{ $contract->end_date->format('d/m/Y') }}</td>
                            <td>{{ number_format((float) $contract->monthly_rent, 2) }}</td>
                            <td>{{ ucfirst($contract->status) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center text-muted">No contracts found</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
