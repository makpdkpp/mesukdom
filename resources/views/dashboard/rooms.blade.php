@extends('layouts.adminlte', ['title' => 'Rooms', 'heading' => 'Rooms'])

@php($defaultBuildingId = old('building_id', optional($buildings->first())->id))

@section('content')
<div class="row">
    <div class="col-lg-4">
        <div class="card card-primary">
            <div class="card-header"><h3 class="card-title">Add Room</h3></div>
            <form method="POST" action="{{ route('app.rooms.store') }}" class="room-catalog-form" data-selected-floor="{{ old('floor', 1) }}" data-selected-room-type="{{ old('room_type') }}">
                @csrf
                <div class="card-body">
                    @if($buildings->isEmpty())
                        <div class="alert alert-warning mb-3">Please create at least one building before adding rooms.</div>
                    @endif
                    <div class="form-group">
                        <label>Building</label>
                        <select name="building_id" class="form-control room-building-select" @disabled($buildings->isEmpty()) required>
                            @foreach($buildings as $building)
                                <option value="{{ $building->id }}" @selected((string) $defaultBuildingId === (string) $building->id)>{{ $building->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group"><label>Room Number</label><input name="room_number" class="form-control" value="{{ old('room_number') }}" required @disabled($buildings->isEmpty())></div>
                    <div class="form-group">
                        <label>Floor</label>
                        <select name="floor" class="form-control room-floor-select" @disabled($buildings->isEmpty()) required></select>
                    </div>
                    <div class="form-group">
                        <label>Room Type</label>
                        <select name="room_type" class="form-control room-type-select" @disabled($buildings->isEmpty()) required></select>
                    </div>
                    <div class="form-group">
                        <label>Price</label>
                        <input name="price" class="form-control room-price-input bg-white" readonly>
                    </div>
                    <div class="form-group">
                        <label>Status</label>
                        <select name="status" class="form-control" @disabled($buildings->isEmpty())>
                            <option value="vacant" @selected(old('status', 'vacant') === 'vacant')>ว่าง</option>
                            <option value="occupied" @selected(old('status') === 'occupied')>ไม่ว่าง</option>
                            <option value="maintenance" @selected(old('status') === 'maintenance')>กำลังปรับปรุง</option>
                        </select>
                    </div>
                </div>
                <div class="card-footer"><button class="btn btn-primary" @disabled($buildings->isEmpty())>Save Room</button></div>
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
                    <thead><tr><th>Building</th><th>Room</th><th>Floor</th><th>Type</th><th>Price</th><th>Status</th><th class="text-right">Actions</th></tr></thead>
                    <tbody>
                    @forelse($rooms as $room)
                        @php($selectedBuildingId = $room->building_id ?: optional($buildings->firstWhere('name', $room->building))->id)
                        @php($statusLabel = $room->status === 'vacant' ? 'ว่าง' : ($room->status === 'maintenance' ? 'กำลังปรับปรุง' : 'ไม่ว่าง'))
                        <tr>
                            <td>{{ $room->building }}</td>
                            <td>{{ $room->room_number }}</td>
                            <td>{{ $room->floor }}</td>
                            <td>{{ $room->room_type }}</td>
                            <td>{{ number_format((float) $room->price, 2) }}</td>
                            <td>{{ $statusLabel }}</td>
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
                            <td colspan="7">
                                <form method="POST" action="{{ route('app.rooms.update', $room) }}" class="p-3 room-catalog-form" data-selected-floor="{{ $room->floor }}" data-selected-room-type="{{ $room->room_type }}">
                                    @csrf
                                    @method('PUT')
                                    <div class="form-row">
                                        <div class="form-group col-md-2 mb-2">
                                            <label class="small">Building</label>
                                            <select name="building_id" class="form-control form-control-sm room-building-select" required>
                                                @foreach($buildings as $building)
                                                    <option value="{{ $building->id }}" @selected((string) $selectedBuildingId === (string) $building->id)>{{ $building->name }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="form-group col-md-2 mb-2">
                                            <label class="small">Room</label>
                                            <input name="room_number" class="form-control form-control-sm" value="{{ $room->room_number }}" required>
                                        </div>
                                        <div class="form-group col-md-2 mb-2">
                                            <label class="small">Floor</label>
                                            <select name="floor" class="form-control form-control-sm room-floor-select" required></select>
                                        </div>
                                        <div class="form-group col-md-2 mb-2">
                                            <label class="small">Type</label>
                                            <select name="room_type" class="form-control form-control-sm room-type-select" required></select>
                                        </div>
                                        <div class="form-group col-md-2 mb-2">
                                            <label class="small">Price</label>
                                            <input name="price" class="form-control form-control-sm room-price-input bg-white" readonly>
                                        </div>
                                        <div class="form-group col-md-2 mb-2">
                                            <label class="small">Status</label>
                                            <select name="status" class="form-control form-control-sm">
                                                <option value="vacant" @selected($room->status === 'vacant')>ว่าง</option>
                                                <option value="occupied" @selected($room->status === 'occupied')>ไม่ว่าง</option>
                                                <option value="maintenance" @selected($room->status === 'maintenance')>กำลังปรับปรุง</option>
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
                        <tr><td colspan="7" class="text-center text-muted">No rooms found</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection

<script type="application/json" id="building-catalog-data">@json($buildingCatalog)</script>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const buildingCatalogData = document.getElementById('building-catalog-data');
        const buildingCatalog = buildingCatalogData ? JSON.parse(buildingCatalogData.textContent || '[]') : [];

        const findBuilding = (buildingId) => {
            return buildingCatalog.find((building) => String(building.id) === String(buildingId)) || null;
        };

        const renderFloorOptions = (select, floorCount, selectedFloor) => {
            const options = [];
            for (let floor = 1; floor <= floorCount; floor += 1) {
                const selected = String(floor) === String(selectedFloor) ? 'selected' : '';
                options.push(`<option value="${floor}" ${selected}>ชั้น ${floor}</option>`);
            }
            select.innerHTML = options.join('');
        };

        const renderRoomTypeOptions = (select, roomTypes, selectedRoomType) => {
            const options = roomTypes.map((roomType) => {
                const selected = String(roomType.name) === String(selectedRoomType) ? 'selected' : '';
                return `<option value="${roomType.name}" data-price="${roomType.price}" ${selected}>${roomType.name}</option>`;
            });
            select.innerHTML = options.join('');
        };

        const syncPrice = (typeSelect, priceInput) => {
            const selectedOption = typeSelect.options[typeSelect.selectedIndex];
            const price = Number(selectedOption?.dataset.price || 0);
            priceInput.value = price.toFixed(2);
        };

        const syncRoomForm = (form, resetSelections = false) => {
            const buildingSelect = form.querySelector('.room-building-select');
            const floorSelect = form.querySelector('.room-floor-select');
            const roomTypeSelect = form.querySelector('.room-type-select');
            const priceInput = form.querySelector('.room-price-input');

            if (!buildingSelect || !floorSelect || !roomTypeSelect || !priceInput) {
                return;
            }

            const selectedBuilding = findBuilding(buildingSelect.value) || buildingCatalog[0] || null;
            if (!selectedBuilding) {
                floorSelect.innerHTML = '';
                roomTypeSelect.innerHTML = '';
                priceInput.value = '';
                return;
            }

            if (String(buildingSelect.value) !== String(selectedBuilding.id)) {
                buildingSelect.value = String(selectedBuilding.id);
            }

            const selectedFloor = resetSelections
                ? 1
                : (form.dataset.selectedFloor || floorSelect.value || 1);
            const selectedRoomType = resetSelections
                ? selectedBuilding.room_types[0]?.name || ''
                : (form.dataset.selectedRoomType || roomTypeSelect.value || selectedBuilding.room_types[0]?.name || '');

            renderFloorOptions(floorSelect, selectedBuilding.floor_count, selectedFloor);
            renderRoomTypeOptions(roomTypeSelect, selectedBuilding.room_types, selectedRoomType);
            syncPrice(roomTypeSelect, priceInput);

            form.dataset.selectedFloor = floorSelect.value;
            form.dataset.selectedRoomType = roomTypeSelect.value;
        };

        document.querySelectorAll('.room-catalog-form').forEach((form) => {
            syncRoomForm(form);

            const buildingSelect = form.querySelector('.room-building-select');
            const floorSelect = form.querySelector('.room-floor-select');
            const roomTypeSelect = form.querySelector('.room-type-select');
            const priceInput = form.querySelector('.room-price-input');

            buildingSelect?.addEventListener('change', () => syncRoomForm(form, true));
            floorSelect?.addEventListener('change', () => {
                form.dataset.selectedFloor = floorSelect.value;
            });
            roomTypeSelect?.addEventListener('change', () => {
                form.dataset.selectedRoomType = roomTypeSelect.value;
                syncPrice(roomTypeSelect, priceInput);
            });
        });
    });
</script>
@endpush
