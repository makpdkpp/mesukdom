@extends('layouts.app')

@section('content')
<div class="row">
    <div class="col-md-4">
        <div class="card card-success">
            <div class="card-header"><h3 class="card-title">Add Resident</h3></div>
            <form method="POST" action="{{ route('app.customers.store') }}">
                @csrf
                <div class="card-body">
                    <div class="form-group"><label>Name</label><input name="name" class="form-control" required></div>
                    <div class="form-group"><label>Phone</label><input name="phone" class="form-control"></div>
                    <div class="form-group"><label>Email</label><input name="email" type="email" class="form-control"></div>
                    <div class="form-group"><label>LINE ID</label><input name="line_id" class="form-control"></div>
                    <div class="form-group"><label>ID Card</label><input name="id_card" class="form-control"></div>
                    <div class="form-group">
                        <label>Room</label>
                        <select name="room_id" class="form-control">
                            <option value="">-- Select room --</option>
                            @foreach($rooms as $room)
                                <option value="{{ $room->id }}">{{ $room->room_number }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="card-footer"><button class="btn btn-success">Save Resident</button></div>
            </form>
        </div>
    </div>
    <div class="col-md-8">
        <div class="card">
            <div class="card-header"><h3 class="card-title">Residents</h3></div>
            <div class="card-body table-responsive p-0">
                <table class="table table-hover text-nowrap">
                    <thead><tr><th>Name</th><th>Phone</th><th>Email</th><th>Room</th><th>LINE</th></tr></thead>
                    <tbody>
                    @forelse($customers as $customer)
                        <tr>
                            <td>{{ $customer->name }}</td>
                            <td>{{ $customer->phone }}</td>
                            <td>{{ $customer->email }}</td>
                            <td>{{ $customer->room->room_number ?? '-' }}</td>
                            <td>{{ $customer->line_id ?: 'Not linked' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center text-muted">No residents found</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
