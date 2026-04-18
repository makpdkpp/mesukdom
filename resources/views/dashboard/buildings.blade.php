@extends('layouts.adminlte', ['title' => 'Building', 'heading' => 'Building'])

@push('head')
<style>
    .room-type-table td,
    .room-type-table th {
        vertical-align: middle;
    }

    .room-type-chip {
        display: inline-flex;
        align-items: center;
        gap: 0.35rem;
        padding: 0.3rem 0.6rem;
        border-radius: 999px;
        background: #eff6ff;
        color: #1e3a8a;
        font-size: 0.8rem;
        font-weight: 600;
        margin-right: 0.4rem;
        margin-bottom: 0.4rem;
    }

    .room-type-chip small {
        color: #475569;
        font-weight: 500;
    }
</style>
@endpush

@php($defaultRoomTypes = old('room_types', [['name' => 'Standard', 'price' => 3500]]))

@section('content')
<div class="row">
    <div class="col-lg-5">
        <div class="card card-primary">
            <div class="card-header">
                <h3 class="card-title">Add Building</h3>
            </div>
            <form method="POST" action="{{ route('app.buildings.store') }}" class="building-room-type-form">
                @csrf
                <div class="card-body">
                    <div class="form-group">
                        <label>Building Name</label>
                        <input name="name" class="form-control" value="{{ old('name') }}" placeholder="เช่น อาคาร A" required>
                    </div>
                    <div class="form-group">
                        <label>Floors</label>
                        <input name="floor_count" type="number" min="1" max="100" class="form-control" value="{{ old('floor_count', 1) }}" required>
                    </div>
                    <div class="form-group mb-0">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <label class="mb-0">Room Types</label>
                            <button type="button" class="btn btn-sm btn-outline-primary" data-add-room-type>เพิ่ม Room Type</button>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-sm room-type-table mb-0">
                                <thead>
                                    <tr>
                                        <th>Name</th>
                                        <th style="width: 140px;">Price</th>
                                        <th style="width: 48px;"></th>
                                    </tr>
                                </thead>
                                <tbody class="room-type-rows">
                                    @foreach($defaultRoomTypes as $index => $roomType)
                                        <tr>
                                            <td><input name="room_types[{{ $index }}][name]" class="form-control" value="{{ $roomType['name'] ?? '' }}" required></td>
                                            <td><input name="room_types[{{ $index }}][price]" type="number" step="0.01" min="0" class="form-control" value="{{ $roomType['price'] ?? 0 }}" required></td>
                                            <td class="text-right"><button type="button" class="btn btn-sm btn-outline-danger" data-remove-room-type>&times;</button></td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <div class="card-footer">
                    <button class="btn btn-primary">Save Building</button>
                </div>
            </form>
        </div>
    </div>

    <div class="col-lg-7">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h3 class="card-title mb-0">Building Catalog</h3>
                <span class="text-sm text-muted">กำหนดอาคาร, จำนวนชั้น, และ room types พร้อมราคา</span>
            </div>
            <div class="card-body table-responsive p-0">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Building</th>
                            <th>Floors</th>
                            <th>Room Types</th>
                            <th>Rooms</th>
                            <th class="text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($buildings as $building)
                            <tr>
                                <td>{{ $building->name }}</td>
                                <td>{{ $building->floor_count }}</td>
                                <td>
                                    @foreach($building->normalizedRoomTypes() as $roomType)
                                        <span class="room-type-chip">{{ $roomType['name'] }} <small>{{ number_format((float) $roomType['price'], 2) }}</small></span>
                                    @endforeach
                                </td>
                                <td>{{ $building->rooms_count }}</td>
                                <td class="text-right">
                                    <button type="button" class="btn btn-xs btn-outline-primary" data-toggle="collapse" data-target="#building-edit-{{ $building->id }}" aria-expanded="false" aria-controls="building-edit-{{ $building->id }}">Edit</button>
                                    <form method="POST" action="{{ route('app.buildings.destroy', $building) }}" class="d-inline" onsubmit="return confirm('Delete building {{ $building->name }}?');">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn btn-xs btn-outline-danger" @disabled($building->rooms_count > 0)>Delete</button>
                                    </form>
                                </td>
                            </tr>
                            <tr class="collapse bg-light" id="building-edit-{{ $building->id }}">
                                <td colspan="5">
                                    <form method="POST" action="{{ route('app.buildings.update', $building) }}" class="p-3 building-room-type-form">
                                        @csrf
                                        @method('PUT')
                                        <div class="form-row">
                                            <div class="form-group col-md-5 mb-2">
                                                <label class="small">Building Name</label>
                                                <input name="name" class="form-control" value="{{ $building->name }}" required>
                                            </div>
                                            <div class="form-group col-md-2 mb-2">
                                                <label class="small">Floors</label>
                                                <input name="floor_count" type="number" min="1" max="100" class="form-control" value="{{ $building->floor_count }}" required>
                                            </div>
                                        </div>
                                        <div class="d-flex justify-content-between align-items-center mb-2 mt-2">
                                            <label class="small mb-0">Room Types</label>
                                            <button type="button" class="btn btn-sm btn-outline-primary" data-add-room-type>เพิ่ม Room Type</button>
                                        </div>
                                        <div class="table-responsive">
                                            <table class="table table-sm room-type-table mb-2">
                                                <thead>
                                                    <tr>
                                                        <th>Name</th>
                                                        <th style="width: 140px;">Price</th>
                                                        <th style="width: 48px;"></th>
                                                    </tr>
                                                </thead>
                                                <tbody class="room-type-rows">
                                                    @foreach($building->normalizedRoomTypes() as $index => $roomType)
                                                        <tr>
                                                            <td><input name="room_types[{{ $index }}][name]" class="form-control form-control-sm" value="{{ $roomType['name'] }}" required></td>
                                                            <td><input name="room_types[{{ $index }}][price]" type="number" step="0.01" min="0" class="form-control form-control-sm" value="{{ $roomType['price'] }}" required></td>
                                                            <td class="text-right"><button type="button" class="btn btn-sm btn-outline-danger" data-remove-room-type>&times;</button></td>
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                        @if($building->rooms_count > 0)
                                            <div class="small text-muted mb-2">อาคารนี้มีห้องอยู่แล้ว {{ $building->rooms_count }} ห้อง การลดจำนวนชั้นหรือเอา room type ที่กำลังใช้ออกจะถูกป้องกันอัตโนมัติ</div>
                                        @endif
                                        <button class="btn btn-sm btn-primary">Save Changes</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center text-muted py-4">No buildings yet</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const createRow = (index, name = '', price = '0') => {
            return `
                <tr>
                    <td><input name="room_types[${index}][name]" class="form-control form-control-sm" value="${name}" required></td>
                    <td><input name="room_types[${index}][price]" type="number" step="0.01" min="0" class="form-control form-control-sm" value="${price}" required></td>
                    <td class="text-right"><button type="button" class="btn btn-sm btn-outline-danger" data-remove-room-type>&times;</button></td>
                </tr>
            `;
        };

        const renumberRows = (form) => {
            form.querySelectorAll('.room-type-rows tr').forEach((row, index) => {
                const nameInput = row.querySelector('input[name*="[name]"]');
                const priceInput = row.querySelector('input[name*="[price]"]');

                if (nameInput) {
                    nameInput.name = `room_types[${index}][name]`;
                }

                if (priceInput) {
                    priceInput.name = `room_types[${index}][price]`;
                }
            });
        };

        document.querySelectorAll('.building-room-type-form').forEach((form) => {
            form.addEventListener('click', (event) => {
                const addButton = event.target.closest('[data-add-room-type]');
                if (addButton) {
                    const tbody = form.querySelector('.room-type-rows');
                    tbody.insertAdjacentHTML('beforeend', createRow(tbody.querySelectorAll('tr').length));
                    renumberRows(form);
                    return;
                }

                const removeButton = event.target.closest('[data-remove-room-type]');
                if (!removeButton) {
                    return;
                }

                const tbody = form.querySelector('.room-type-rows');
                if (tbody.querySelectorAll('tr').length === 1) {
                    return;
                }

                removeButton.closest('tr').remove();
                renumberRows(form);
            });
        });
    });
</script>
@endpush