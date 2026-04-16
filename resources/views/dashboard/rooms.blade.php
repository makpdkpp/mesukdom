@extends('layouts.adminlte')

@section('content')
<div class="row">
    <div class="col-lg-4">
        <div class="card card-primary">
            <div class="card-header"><h3 class="card-title">Add Room</h3></div>
            <form method="POST" action="{{ route('app.rooms.store') }}">
                @csrf
                <div class="card-body">
                    <div class="form-group"><label>Room Number</label><input name="room_number" class="form-control" value="{{ old('room_number') }}" required></div>
                    <div class="form-group"><label>Floor</label><input name="floor" type="number" min="1" class="form-control" value="{{ old('floor', 1) }}" required></div>
                    <div class="form-group"><label>Room Type</label><input name="room_type" class="form-control" value="{{ old('room_type', 'Standard') }}" required></div>
                    <div class="form-group"><label>Price</label><input name="price" type="number" step="0.01" class="form-control" value="{{ old('price', 3500) }}" required></div>
                    <div class="form-group">
                        <label>Status</label>
                        <select name="status" class="form-control">
                            <option value="vacant" @selected(old('status', 'vacant') === 'vacant')>Vacant</option>
                            <option value="occupied" @selected(old('status') === 'occupied')>Occupied</option>
                            <option value="maintenance" @selected(old('status') === 'maintenance')>Maintenance</option>
                        </select>
                    </div>
                </div>
                <div class="card-footer"><button class="btn btn-primary">Save Room</button></div>
            </form>
        </div>
    </div>

    <div class="col-lg-8">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h3 class="card-title mb-0">Room Status</h3>
                <span class="text-sm text-muted">Add, edit, and remove rooms for the current tenant</span>
            </div>
            <div class="card-body table-responsive p-0">
                <table class="table table-hover text-nowrap">
                    <thead><tr><th>Room</th><th>Floor</th><th>Type</th><th>Price</th><th>Status</th><th class="text-right">Actions</th></tr></thead>
                    <tbody>
                    @forelse($rooms as $room)
                        <tr>
                            <td>{{ $room->room_number }}</td>
                            <td>{{ $room->floor }}</td>
                            <td>{{ $room->room_type }}</td>
                            <td>{{ number_format((float) $room->price, 2) }}</td>
                            <td>{{ ucfirst($room->status) }}</td>
                            <td class="text-right">
                                <button class="btn btn-xs btn-outline-primary" type="button" data-toggle="collapse" data-target="#room-edit-{{ $room->id }}" aria-expanded="false" aria-controls="room-edit-{{ $room->id }}">Edit</button>
                                <form method="POST" action="{{ route('app.rooms.destroy', $room) }}" class="d-inline" onsubmit="return confirm('Delete room {{ $room->room_number }}?');">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn-xs btn-outline-danger">Delete</button>
                                </form>
                            </td>
                        </tr>
                        <tr class="collapse bg-light" id="room-edit-{{ $room->id }}">
                            <td colspan="6">
                                <form method="POST" action="{{ route('app.rooms.update', $room) }}" class="p-3">
                                    @csrf
                                    @method('PUT')
                                    <div class="form-row">
                                        <div class="form-group col-md-2 mb-2">
                                            <label class="small">Room</label>
                                            <input name="room_number" class="form-control form-control-sm" value="{{ $room->room_number }}" required>
                                        </div>
                                        <div class="form-group col-md-2 mb-2">
                                            <label class="small">Floor</label>
                                            <input name="floor" type="number" min="1" class="form-control form-control-sm" value="{{ $room->floor }}" required>
                                        </div>
                                        <div class="form-group col-md-3 mb-2">
                                            <label class="small">Type</label>
                                            <input name="room_type" class="form-control form-control-sm" value="{{ $room->room_type }}" required>
                                        </div>
                                        <div class="form-group col-md-2 mb-2">
                                            <label class="small">Price</label>
                                            <input name="price" type="number" step="0.01" class="form-control form-control-sm" value="{{ $room->price }}" required>
                                        </div>
                                        <div class="form-group col-md-2 mb-2">
                                            <label class="small">Status</label>
                                            <select name="status" class="form-control form-control-sm">
                                                <option value="vacant" @selected($room->status === 'vacant')>Vacant</option>
                                                <option value="occupied" @selected($room->status === 'occupied')>Occupied</option>
                                                <option value="maintenance" @selected($room->status === 'maintenance')>Maintenance</option>
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
                        <tr><td colspan="6" class="text-center text-muted">No rooms found</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
