@extends('layouts.adminlte')

@section('content')
<div class="row">
    <div class="col-lg-4">
        <div class="card card-info">
            <div class="card-header"><h3 class="card-title">Issue Invoice</h3></div>
            <form method="POST" action="{{ route('app.invoices.store') }}">
                @csrf
                <div class="card-body">
                    <div class="form-group">
                        <label>Contract</label>
                        <select name="contract_id" class="form-control" required>
                            @foreach($contracts as $contract)
                                <option value="{{ $contract->id }}" @selected((string) old('contract_id') === (string) $contract->id)>
                                    {{ $contract->customer->name }} - {{ $contract->room->room_number }} (Contract #{{ $contract->id }})
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group"><label>Water Fee</label><input name="water_fee" type="number" step="0.01" class="form-control" value="{{ old('water_fee', 0) }}"></div>
                    <div class="form-group"><label>Electricity Fee</label><input name="electricity_fee" type="number" step="0.01" class="form-control" value="{{ old('electricity_fee', 0) }}"></div>
                    <div class="form-group"><label>Service Fee</label><input name="service_fee" type="number" step="0.01" class="form-control" value="{{ old('service_fee', 0) }}"></div>
                    <div class="form-group"><label>Due Date</label><input name="due_date" type="date" class="form-control" value="{{ old('due_date') }}" required></div>
                    <div class="form-group">
                        <label>Status</label>
                        <select name="status" class="form-control">
                            <option value="draft" @selected(old('status', 'draft') === 'draft')>Draft</option>
                            <option value="sent" @selected(old('status') === 'sent')>Sent</option>
                            <option value="paid" @selected(old('status') === 'paid')>Paid</option>
                            <option value="overdue" @selected(old('status') === 'overdue')>Overdue</option>
                        </select>
                    </div>
                </div>
                <div class="card-footer"><button class="btn btn-info">Create Invoice</button></div>
            </form>
        </div>
    </div>

    <div class="col-lg-8">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h3 class="card-title mb-0">Invoices & LINE OA</h3>
                <span class="text-sm text-muted">Issue invoices, track status, and trigger reminders from the same screen</span>
            </div>
            <div class="card-body table-responsive p-0">
                <table class="table table-hover text-nowrap">
                    <thead><tr><th>No.</th><th>Resident</th><th>Room</th><th>Total</th><th>Due</th><th>Status</th><th class="text-right">Actions</th></tr></thead>
                    <tbody>
                    @forelse($invoices as $invoice)
                        <tr>
                            <td>{{ $invoice->invoice_no }}</td>
                            <td>{{ $invoice->customer->name ?? '-' }}</td>
                            <td>{{ $invoice->room->room_number ?? '-' }}</td>
                            <td>{{ number_format((float) $invoice->total_amount, 2) }}</td>
                            <td>{{ $invoice->due_date->format('d/m/Y') }}</td>
                            <td>{{ ucfirst($invoice->status) }}</td>
                            <td class="text-right">
                                <a href="{{ route('app.invoices.pdf', $invoice->id) }}" class="btn btn-xs btn-outline-dark">PDF</a>
                                <a href="{{ $invoice->signedResidentUrl() }}" class="btn btn-xs btn-outline-primary" target="_blank">Resident Link</a>
                                <button type="button" class="btn btn-xs btn-outline-secondary" onclick="navigator.clipboard.writeText('{{ $invoice->signedResidentUrl() }}').then(()=>this.textContent='Copied!')">Copy Link</button>
                                <form method="POST" action="{{ route('app.invoices.remind', $invoice->id) }}" class="d-inline">
                                    @csrf
                                    <button class="btn btn-xs btn-outline-success">Send LINE</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-center text-muted">No invoices found</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
