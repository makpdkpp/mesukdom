@extends('layouts.app')

@section('content')
<div class="row">
    <div class="col-md-4">
        <div class="card card-info">
            <div class="card-header"><h3 class="card-title">Issue Invoice</h3></div>
            <form method="POST" action="{{ route('app.invoices.store') }}">
                @csrf
                <div class="card-body">
                    <div class="form-group">
                        <label>Contract</label>
                        <select name="contract_id" class="form-control" required>
                            @foreach($contracts as $contract)
                                <option value="{{ $contract->id }}">{{ $contract->customer->name }} - {{ $contract->room->room_number }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group"><label>Water Fee</label><input name="water_fee" type="number" step="0.01" class="form-control" value="0"></div>
                    <div class="form-group"><label>Electricity Fee</label><input name="electricity_fee" type="number" step="0.01" class="form-control" value="0"></div>
                    <div class="form-group"><label>Service Fee</label><input name="service_fee" type="number" step="0.01" class="form-control" value="0"></div>
                    <div class="form-group"><label>Due Date</label><input name="due_date" type="date" class="form-control" required></div>
                    <div class="form-group">
                        <label>Status</label>
                        <select name="status" class="form-control">
                            <option value="draft">Draft</option>
                            <option value="sent">Sent</option>
                            <option value="paid">Paid</option>
                            <option value="overdue">Overdue</option>
                        </select>
                    </div>
                </div>
                <div class="card-footer"><button class="btn btn-info">Create Invoice</button></div>
            </form>
        </div>
    </div>
    <div class="col-md-8">
        <div class="card">
            <div class="card-header"><h3 class="card-title">Invoices & LINE OA</h3></div>
            <div class="card-body table-responsive p-0">
                <table class="table table-hover text-nowrap">
                    <thead><tr><th>No.</th><th>Resident</th><th>Total</th><th>Due</th><th>Status</th><th>Actions</th></tr></thead>
                    <tbody>
                    @forelse($invoices as $invoice)
                        <tr>
                            <td>{{ $invoice->invoice_no }}</td>
                            <td>{{ $invoice->customer->name ?? '-' }}</td>
                            <td>{{ number_format((float) $invoice->total_amount, 2) }}</td>
                            <td>{{ $invoice->due_date->format('d/m/Y') }}</td>
                            <td>{{ ucfirst($invoice->status) }}</td>
                            <td>
                                <a href="{{ route('resident.invoice', $invoice) }}" class="btn btn-xs btn-outline-primary">Resident View</a>
                                <form method="POST" action="{{ route('app.invoices.remind', $invoice) }}" class="d-inline">
                                    @csrf
                                    <button class="btn btn-xs btn-outline-success">Send LINE</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center text-muted">No invoices found</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
