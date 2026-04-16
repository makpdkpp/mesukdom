@extends('layouts.adminlte')

@section('content')
<div class="row">
    <div class="col-lg-4">
        <div class="card card-warning">
            <div class="card-header"><h3 class="card-title">Create Contract</h3></div>
            <form method="POST" action="{{ route('app.contracts.store') }}">
                @csrf
                <div class="card-body">
                    <div class="form-group">
                        <label>Resident</label>
                        <select name="customer_id" class="form-control" required>
                            @foreach($customers as $customer)
                                <option value="{{ $customer->id }}" @selected((string) old('customer_id') === (string) $customer->id)>{{ $customer->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Room</label>
                        <select name="room_id" class="form-control" required>
                            @foreach($rooms as $room)
                                <option value="{{ $room->id }}" @selected((string) old('room_id') === (string) $room->id)>{{ $room->room_number }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group"><label>Start Date</label><input name="start_date" type="date" class="form-control" value="{{ old('start_date') }}" required></div>
                    <div class="form-group"><label>End Date</label><input name="end_date" type="date" class="form-control" value="{{ old('end_date') }}" required></div>
                    <div class="form-group"><label>Deposit</label><input name="deposit" type="number" step="0.01" class="form-control" value="{{ old('deposit', 5000) }}" required></div>
                    <div class="form-group"><label>Monthly Rent</label><input name="monthly_rent" type="number" step="0.01" class="form-control" value="{{ old('monthly_rent') }}"></div>
                    <div class="form-group">
                        <label>Status</label>
                        <select name="status" class="form-control">
                            <option value="active" @selected(old('status', 'active') === 'active')>Active</option>
                            <option value="expired" @selected(old('status') === 'expired')>Expired</option>
                            <option value="cancelled" @selected(old('status') === 'cancelled')>Cancelled</option>
                        </select>
                    </div>
                </div>
                <div class="card-footer"><button class="btn btn-warning">Save Contract</button></div>
            </form>
        </div>
    </div>

    <div class="col-lg-8">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h3 class="card-title mb-0">Rental Contracts</h3>
                <span class="text-sm text-muted">Manage active, expired, and cancelled contracts for the current tenant</span>
            </div>
            <div class="card-body table-responsive p-0">
                <table class="table table-hover text-nowrap">
                    <thead><tr><th>Resident</th><th>Room</th><th>Period</th><th>Rent</th><th>Status</th><th class="text-right">Actions</th></tr></thead>
                    <tbody>
                    @forelse($contracts as $contract)
                        <tr>
                            <td>{{ $contract->customer->name }}</td>
                            <td>{{ $contract->room->room_number }}</td>
                            <td>{{ $contract->start_date->format('d/m/Y') }} - {{ $contract->end_date->format('d/m/Y') }}</td>
                            <td>{{ number_format((float) $contract->monthly_rent, 2) }}</td>
                            <td>{{ ucfirst($contract->status) }}</td>
                            <td class="text-right">
                                <button class="btn btn-xs btn-outline-primary" type="button" data-toggle="collapse" data-target="#contract-edit-{{ $contract->id }}" aria-expanded="false" aria-controls="contract-edit-{{ $contract->id }}">Edit</button>
                                <form method="POST" action="{{ route('app.contracts.destroy', $contract) }}" class="d-inline" onsubmit="return confirm('Delete contract for {{ $contract->customer->name }}?');">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn-xs btn-outline-danger">Delete</button>
                                </form>
                            </td>
                        </tr>
                        <tr class="collapse bg-light" id="contract-edit-{{ $contract->id }}">
                            <td colspan="6">
                                <form method="POST" action="{{ route('app.contracts.update', $contract) }}" class="p-3">
                                    @csrf
                                    @method('PUT')
                                    <div class="form-row">
                                        <div class="form-group col-md-2 mb-2">
                                            <label class="small">Resident</label>
                                            <select name="customer_id" class="form-control form-control-sm" required>
                                                @foreach($customers as $customer)
                                                    <option value="{{ $customer->id }}" @selected((string) $contract->customer_id === (string) $customer->id)>{{ $customer->name }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="form-group col-md-2 mb-2">
                                            <label class="small">Room</label>
                                            <select name="room_id" class="form-control form-control-sm" required>
                                                @foreach($rooms as $room)
                                                    <option value="{{ $room->id }}" @selected((string) $contract->room_id === (string) $room->id)>{{ $room->room_number }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="form-group col-md-2 mb-2">
                                            <label class="small">Start</label>
                                            <input name="start_date" type="date" class="form-control form-control-sm" value="{{ $contract->start_date->format('Y-m-d') }}" required>
                                        </div>
                                        <div class="form-group col-md-2 mb-2">
                                            <label class="small">End</label>
                                            <input name="end_date" type="date" class="form-control form-control-sm" value="{{ $contract->end_date->format('Y-m-d') }}" required>
                                        </div>
                                        <div class="form-group col-md-1 mb-2">
                                            <label class="small">Deposit</label>
                                            <input name="deposit" type="number" step="0.01" class="form-control form-control-sm" value="{{ $contract->deposit }}" required>
                                        </div>
                                        <div class="form-group col-md-1 mb-2">
                                            <label class="small">Rent</label>
                                            <input name="monthly_rent" type="number" step="0.01" class="form-control form-control-sm" value="{{ $contract->monthly_rent }}">
                                        </div>
                                        <div class="form-group col-md-1 mb-2">
                                            <label class="small">Status</label>
                                            <select name="status" class="form-control form-control-sm">
                                                <option value="active" @selected($contract->status === 'active')>Active</option>
                                                <option value="expired" @selected($contract->status === 'expired')>Expired</option>
                                                <option value="cancelled" @selected($contract->status === 'cancelled')>Cancelled</option>
                                            </select>
                                        </div>
                                        <div class="form-group col-md-1 mb-2 d-flex align-items-end">
                                            <button class="btn btn-sm btn-primary btn-block">Save</button>
                                        </div>
                                    </div>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center text-muted">No contracts found</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
