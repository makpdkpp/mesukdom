@extends('layouts.app')

@section('content')
<div class="row">
    <div class="col-md-4">
        <div class="card card-primary">
            <div class="card-header"><h3 class="card-title">Add Room</h3></div>
            <form method="POST" action="{{ route('app.rooms.store') }}">
                @csrf
                <div class="card-body">
                    <div class="form-group"><label>Room Number</label><input name="room_number" class="form-control" required></div>
                    <div class="form-group"><label>Floor</label><input name="floor" type="number" min="1" class="form-control" value="1" required></div>
                    <div class="form-group"><label>Room Type</label><input name="room_type" class="form-control" value="Standard" required></div>
                    <div class="form-group"><label>Price</label><input name="price" type="number" step="0.01" class="form-control" value="3500" required></div>
                    <div class="form-group">
                        <label>Status</label>
                        <select name="status" class="form-control">
                            <option value="vacant">Vacant</option>
                            <option value="occupied">Occupied</option>
                            <option value="maintenance">Maintenance</option>
                        </select>
                    </div>
                </div>
                <div class="card-footer"><button class="btn btn-primary">Save Room</button></div>
            </form>
        </div>
    </div>
    <div class="col-md-8">
        <div class="card">
            <div class="card-header"><h3 class="card-title">Room Status</h3></div>
            <div class="card-body table-responsive p-0">
                <table class="table table-hover text-nowrap">
                    <thead><tr><th>Room</th><th>Floor</th><th>Type</th><th>Price</th><th>Status</th></tr></thead>
                    <tbody>
                    @forelse($rooms as $room)
                        <tr>
                            <td>{{ $room->room_number }}</td>
                            <td>{{ $room->floor }}</td>
                            <td>{{ $room->room_type }}</td>
                            <td>{{ number_format((float) $room->price, 2) }}</td>
                            <td>{{ ucfirst($room->status) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center text-muted">No rooms found</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
