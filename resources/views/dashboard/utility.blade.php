@extends('layouts.adminlte')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card card-warning">
            <div class="card-header d-flex flex-wrap justify-content-between align-items-center" style="gap:12px;">
                <h3 class="card-title mb-0"><i class="fas fa-bolt mr-2"></i>Utility</h3>
                <form method="GET" action="{{ route('app.utility') }}" class="form-inline" style="gap:8px;">
                    <label for="billing-month" class="mb-0">Billing Month</label>
                    <input id="billing-month" type="month" name="month" class="form-control" value="{{ $billingMonth }}">
                    <button class="btn btn-outline-dark">Load</button>
                </form>
            </div>
            <div class="card-body">
                <div class="alert alert-light border">
                    <div><strong>Default water rate:</strong> {{ number_format((float) ($tenant?->default_water_fee ?? 0), 2) }} / unit</div>
                    <div><strong>Default electricity rate:</strong> {{ number_format((float) ($tenant?->default_electricity_fee ?? 0), 2) }} / unit</div>
                </div>

                <div class="table-responsive">
                    <table class="table table-bordered table-hover text-nowrap align-middle">
                        <thead>
                            <tr>
                                <th>Room</th>
                                <th>Resident</th>
                                <th>Room Price</th>
                                <th>Water Units</th>
                                <th>Electricity Units</th>
                                <th>Other Charges</th>
                                <th>Note</th>
                                <th class="text-right">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($contracts as $contract)
                                @php($utility = $utilityRecords->get((string) $contract->id))
                                <tr>
                                    <td>{{ $contract->room?->room_number ?? '-' }}</td>
                                    <td>{{ $contract->customer?->name ?? '-' }}</td>
                                    <td>{{ number_format((float) ($contract->room?->price ?? $contract->monthly_rent), 2) }}</td>
                                    <td colspan="5" class="p-0">
                                        <form method="POST" action="{{ route('app.utility.store') }}" class="p-2">
                                            @csrf
                                            <input type="hidden" name="contract_id" value="{{ $contract->id }}">
                                            <input type="hidden" name="billing_month" value="{{ $billingMonth }}">
                                            <div class="d-flex flex-wrap align-items-end" style="gap:8px;">
                                                <div style="min-width:120px;">
                                                    <label class="small text-muted mb-1 d-block">Water</label>
                                                    <input name="water_units" type="number" min="0" step="0.01" class="form-control" value="{{ old('contract_id') == $contract->id ? old('water_units') : ($utility?->water_units ?? 0) }}">
                                                </div>
                                                <div style="min-width:120px;">
                                                    <label class="small text-muted mb-1 d-block">Electricity</label>
                                                    <input name="electricity_units" type="number" min="0" step="0.01" class="form-control" value="{{ old('contract_id') == $contract->id ? old('electricity_units') : ($utility?->electricity_units ?? 0) }}">
                                                </div>
                                                <div style="min-width:140px;">
                                                    <label class="small text-muted mb-1 d-block">Other Charges</label>
                                                    <input name="other_amount" type="number" min="0" step="0.01" class="form-control" value="{{ old('contract_id') == $contract->id ? old('other_amount') : ($utility?->other_amount ?? 0) }}">
                                                </div>
                                                <div class="flex-grow-1" style="min-width:200px;">
                                                    <label class="small text-muted mb-1 d-block">Note</label>
                                                    <input name="other_description" type="text" class="form-control" value="{{ old('contract_id') == $contract->id ? old('other_description') : ($utility?->other_description ?? '') }}" placeholder="เช่น ค่าส่วนกลาง / ค่าซ่อม">
                                                </div>
                                                <div>
                                                    <button class="btn btn-warning"><i class="fas fa-save mr-1"></i>Save</button>
                                                </div>
                                            </div>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="text-center text-muted py-4">No active contracts found for this month</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
