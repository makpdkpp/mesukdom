@extends('layouts.adminlte', ['title' => 'Contracts', 'heading' => 'Contracts'])

@section('content')
<div class="row">
    <div class="col-lg-4">
        <div class="card card-warning">
            <div class="card-header"><h3 class="card-title">Create Contract</h3></div>
            <form method="POST" action="{{ route('app.contracts.store') }}" class="contract-form" data-selected-room-id="{{ old('room_id') }}">
                @csrf
                <div class="card-body">
                    <div class="form-group">
                        <label>Resident</label>
                        <select name="customer_id" class="form-control contract-customer-select" required>
                            @foreach($customers as $customer)
                                <option value="{{ $customer->id }}" @selected((string) old('customer_id') === (string) $customer->id)>{{ $customer->name }}</option>
                            @endforeach
                        </select>
                        <small class="form-text text-muted contract-room-hint">เลือก Resident แล้วระบบจะดึง Room ให้อัตโนมัติ</small>
                    </div>
                    <div class="form-group">
                        <label>Room</label>
                        <input type="hidden" name="room_id" class="contract-room-id" value="{{ old('room_id') }}">
                        <input type="text" class="form-control contract-room-display bg-white" value="" readonly>
                    </div>
                    <div class="form-group"><label>Start Date</label><input name="start_date" type="date" class="form-control contract-start-date" value="{{ old('start_date') }}" required></div>
                    <div class="form-group"><label>End Date</label><input name="end_date" type="date" class="form-control contract-end-date" value="{{ old('end_date') }}" required></div>
                    <div class="form-group"><label>Deposit</label><input name="deposit" type="number" step="0.01" class="form-control" value="{{ old('deposit', 5000) }}" required></div>
                    <div class="form-group">
                        <label>Monthly Rent</label>
                        <input name="monthly_rent" type="number" step="0.01" class="form-control contract-monthly-rent" value="{{ old('monthly_rent') }}" readonly>
                        <small class="form-text text-muted contract-rent-hint">ระบบจะคำนวณค่าเช่าให้อัตโนมัติจาก Room และช่วงวันที่</small>
                    </div>
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
                                <form method="POST" action="{{ route('app.contracts.update', $contract) }}" class="p-3 contract-form" data-selected-room-id="{{ $contract->room_id }}">
                                    @csrf
                                    @method('PUT')
                                    <div class="form-row">
                                        <div class="form-group col-md-2 mb-2">
                                            <label class="small">Resident</label>
                                            <select name="customer_id" class="form-control form-control-sm contract-customer-select" required>
                                                @foreach($customers as $customer)
                                                    <option value="{{ $customer->id }}" @selected((string) $contract->customer_id === (string) $customer->id)>{{ $customer->name }}</option>
                                                @endforeach
                                            </select>
                                            <small class="form-text text-muted contract-room-hint">Room sync ตาม Resident</small>
                                        </div>
                                        <div class="form-group col-md-2 mb-2">
                                            <label class="small">Room</label>
                                            <input type="hidden" name="room_id" class="contract-room-id" value="{{ $contract->room_id }}">
                                            <input type="text" class="form-control form-control-sm contract-room-display bg-white" value="" readonly>
                                        </div>
                                        <div class="form-group col-md-2 mb-2">
                                            <label class="small">Start</label>
                                            <input name="start_date" type="date" class="form-control form-control-sm contract-start-date" value="{{ $contract->start_date->format('Y-m-d') }}" required>
                                        </div>
                                        <div class="form-group col-md-2 mb-2">
                                            <label class="small">End</label>
                                            <input name="end_date" type="date" class="form-control form-control-sm contract-end-date" value="{{ $contract->end_date->format('Y-m-d') }}" required>
                                        </div>
                                        <div class="form-group col-md-1 mb-2">
                                            <label class="small">Deposit</label>
                                            <input name="deposit" type="number" step="0.01" class="form-control form-control-sm" value="{{ $contract->deposit }}" required>
                                        </div>
                                        <div class="form-group col-md-2 mb-2">
                                            <label class="small">Rent</label>
                                            <input name="monthly_rent" type="number" step="0.01" class="form-control form-control-sm contract-monthly-rent" value="{{ $contract->monthly_rent }}" readonly>
                                            <small class="form-text text-muted contract-rent-hint">Auto</small>
                                        </div>
                                        <div class="form-group col-md-2 mb-2">
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

<script type="application/json" id="customer-room-catalog-data">@json($customerRoomCatalog)</script>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const catalogElement = document.getElementById('customer-room-catalog-data');
        const customerRoomCatalog = catalogElement ? JSON.parse(catalogElement.textContent || '[]') : [];

        const findCustomer = (customerId) => {
            return customerRoomCatalog.find((customer) => String(customer.id) === String(customerId)) || null;
        };

        const daysInMonth = (date) => {
            return new Date(date.getFullYear(), date.getMonth() + 1, 0).getDate();
        };

        const formatCurrency = (amount) => {
            return Number(amount || 0).toFixed(2);
        };

        const calculateRent = (basePrice, startValue, endValue) => {
            const price = Number(basePrice || 0);
            if (!startValue || !endValue) {
                return price;
            }

            const startDate = new Date(startValue + 'T00:00:00');
            const endDate = new Date(endValue + 'T00:00:00');

            if (Number.isNaN(startDate.getTime()) || Number.isNaN(endDate.getTime()) || endDate < startDate) {
                return price;
            }

            if (startDate.getFullYear() === endDate.getFullYear() && startDate.getMonth() === endDate.getMonth()) {
                const coveredDays = Math.floor((endDate - startDate) / 86400000) + 1;
                return (price / daysInMonth(startDate)) * coveredDays;
            }

            return price;
        };

        const syncContractForm = (form) => {
            const customerSelect = form.querySelector('.contract-customer-select');
            const roomIdInput = form.querySelector('.contract-room-id');
            const roomDisplayInput = form.querySelector('.contract-room-display');
            const startDateInput = form.querySelector('.contract-start-date');
            const endDateInput = form.querySelector('.contract-end-date');
            const rentInput = form.querySelector('.contract-monthly-rent');
            const roomHint = form.querySelector('.contract-room-hint');
            const rentHint = form.querySelector('.contract-rent-hint');

            if (!customerSelect || !roomIdInput || !roomDisplayInput || !rentInput) {
                return;
            }

            const customer = findCustomer(customerSelect.value);
            const selectedRoomId = form.dataset.selectedRoomId;
            const roomId = customer?.room_id || selectedRoomId || roomIdInput.value;

            if (roomId) {
                roomIdInput.value = String(roomId);
                form.dataset.selectedRoomId = String(roomId);
            } else {
                roomIdInput.value = '';
            }

            roomDisplayInput.value = customer?.room_label || '';

            if (roomHint) {
                roomHint.textContent = customer?.room_label
                    ? `Room: ${customer.room_label}`
                    : 'Resident นี้ยังไม่ได้ผูกกับ Room';
            }

            const rent = calculateRent(customer?.room_price || 0, startDateInput?.value || '', endDateInput?.value || '');
            rentInput.value = formatCurrency(rent);

            if (rentHint) {
                rentHint.textContent = startDateInput?.value && endDateInput?.value
                    ? 'ระบบคำนวณจาก Room ของ Resident และช่วงวันที่ที่เลือก'
                    : 'ระบบจะคำนวณค่าเช่าให้อัตโนมัติจาก Room และช่วงวันที่';
            }
        };

        document.querySelectorAll('.contract-form').forEach((form) => {
            syncContractForm(form);

            const customerSelect = form.querySelector('.contract-customer-select');
            const startDateInput = form.querySelector('.contract-start-date');
            const endDateInput = form.querySelector('.contract-end-date');

            customerSelect?.addEventListener('change', () => syncContractForm(form));
            startDateInput?.addEventListener('change', () => syncContractForm(form));
            endDateInput?.addEventListener('change', () => syncContractForm(form));
        });
    });
</script>
@endpush
