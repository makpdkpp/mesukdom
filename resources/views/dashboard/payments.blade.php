@extends('layouts.app')

@section('content')
<div class="row">
    <div class="col-md-4">
        <div class="card card-secondary">
            <div class="card-header"><h3 class="card-title">Record Payment</h3></div>
            <form method="POST" action="{{ route('app.payments.store') }}">
                @csrf
                <div class="card-body">
                    <div class="form-group">
                        <label>Invoice</label>
                        <select name="invoice_id" class="form-control" required>
                            @foreach($invoices as $invoice)
                                <option value="{{ $invoice->id }}">{{ $invoice->invoice_no }} - {{ $invoice->customer->name ?? '-' }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group"><label>Amount</label><input name="amount" type="number" step="0.01" class="form-control" required></div>
                    <div class="form-group"><label>Payment Date</label><input name="payment_date" type="date" class="form-control" required></div>
                    <div class="form-group">
                        <label>Method</label>
                        <select name="method" class="form-control">
                            <option value="manual">Manual</option>
                            <option value="slip">Upload Slip</option>
                            <option value="online">Online Gateway</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Status</label>
                        <select name="status" class="form-control">
                            <option value="pending">Pending</option>
                            <option value="approved">Approved</option>
                            <option value="rejected">Rejected</option>
                        </select>
                    </div>
                    <div class="form-group"><label>Notes</label><textarea name="notes" class="form-control"></textarea></div>
                </div>
                <div class="card-footer"><button class="btn btn-secondary">Save Payment</button></div>
            </form>
        </div>
    </div>
    <div class="col-md-8">
        <div class="card">
            <div class="card-header"><h3 class="card-title">Payment History</h3></div>
            <div class="card-body table-responsive p-0">
                <table class="table table-hover text-nowrap">
                    <thead><tr><th>Invoice</th><th>Resident</th><th>Amount</th><th>Method</th><th>Status</th></tr></thead>
                    <tbody>
                    @forelse($payments as $payment)
                        <tr>
                            <td>{{ $payment->invoice->invoice_no ?? '-' }}</td>
                            <td>{{ $payment->invoice->customer->name ?? '-' }}</td>
                            <td>{{ number_format((float) $payment->amount, 2) }}</td>
                            <td>{{ ucfirst($payment->method) }}</td>
                            <td>{{ ucfirst($payment->status) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center text-muted">No payments found</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
