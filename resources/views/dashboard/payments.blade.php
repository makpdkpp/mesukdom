@extends('layouts.adminlte')

@section('content')
<div class="row">
    <div class="col-lg-4">
        <div class="card card-secondary">
            <div class="card-header"><h3 class="card-title">Record Payment</h3></div>
            <form method="POST" action="{{ route('app.payments.store') }}" enctype="multipart/form-data">
                @csrf
                <div class="card-body">
                    <div class="form-group">
                        <label>Invoice</label>
                        <select name="invoice_id" class="form-control" required>
                            @foreach($invoices as $invoice)
                                <option value="{{ $invoice->id }}" @selected((string) old('invoice_id') === (string) $invoice->id)>{{ $invoice->invoice_no }} - {{ $invoice->customer->name ?? '-' }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group"><label>Amount</label><input name="amount" type="number" step="0.01" class="form-control" value="{{ old('amount') }}" required></div>
                    <div class="form-group"><label>Payment Date</label><input name="payment_date" type="date" class="form-control" value="{{ old('payment_date') }}" required></div>
                    <div class="form-group">
                        <label>Method</label>
                        <select name="method" class="form-control">
                            <option value="manual" @selected(old('method', 'manual') === 'manual')>Manual</option>
                            <option value="slip" @selected(old('method') === 'slip')>Upload Slip</option>
                            <option value="online" @selected(old('method') === 'online')>Online Gateway</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Status</label>
                        <select name="status" class="form-control">
                            <option value="pending" @selected(old('status', 'pending') === 'pending')>Pending</option>
                            <option value="approved" @selected(old('status') === 'approved')>Approved</option>
                            <option value="rejected" @selected(old('status') === 'rejected')>Rejected</option>
                        </select>
                    </div>
                    <div class="form-group" id="slip-upload-group" style="display:none">
                        <label>Payment Slip <span class="text-muted">(jpg/png/pdf, max 5 MB)</span></label>
                        <input name="slip" type="file" class="form-control-file" accept=".jpg,.jpeg,.png,.pdf">
                        @error('slip') <span class="text-danger small">{{ $message }}</span> @enderror
                    </div>
                    <div class="form-group"><label>Notes</label><textarea name="notes" class="form-control">{{ old('notes') }}</textarea></div>
                    <script>
                        (function () {
                            var methodEl = document.querySelector('select[name="method"]');
                            var slipGroup = document.getElementById('slip-upload-group');
                            function toggle() { slipGroup.style.display = methodEl.value === 'slip' ? '' : 'none'; }
                            methodEl.addEventListener('change', toggle);
                            toggle();
                        }());
                    </script>
                </div>
                <div class="card-footer"><button class="btn btn-secondary">Save Payment</button></div>
            </form>
        </div>
    </div>

    <div class="col-lg-8">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h3 class="card-title mb-0">Payment History</h3>
                <span class="text-sm text-muted">Track payment method and approval state for each invoice</span>
            </div>
            <div class="card-body table-responsive p-0">
                <table class="table table-hover text-nowrap">
                    <thead><tr><th>Invoice</th><th>Resident</th><th>Amount</th><th>Method</th><th>Status</th><th>SlipOK</th><th>Paid On</th><th class="text-right">Actions</th></tr></thead>
                    <tbody>
                    @forelse($payments as $payment)
                        <tr>
                            <td>{{ $payment->invoice->invoice_no ?? '-' }}</td>
                            <td>{{ $payment->invoice->customer->name ?? '-' }}</td>
                            <td>{{ number_format((float) $payment->amount, 2) }}</td>
                            <td>{{ ucfirst($payment->method) }}</td>
                            <td>{{ ucfirst($payment->status) }}</td>
                            <td>
                                @php($verificationStatus = $payment->verification_status ?: 'manual')
                                @php($badgeClass = match($verificationStatus) {
                                    'verified' => 'badge-success',
                                    'failed' => 'badge-danger',
                                    'review' => 'badge-warning',
                                    'skipped' => 'badge-secondary',
                                    default => 'badge-light',
                                })
                                <span class="badge {{ $badgeClass }}">{{ ucfirst($verificationStatus) }}</span>
                                @if($payment->verification_note)
                                    <div class="small text-muted mt-1" style="max-width:260px;white-space:normal;">{{ $payment->verification_note }}</div>
                                @endif
                            </td>
                            <td>{{ $payment->payment_date->format('d/m/Y') }}</td>
                            <td class="text-right">
                                @if($payment->slip_path)
                                    <a href="{{ route('app.payments.slip', $payment->id) }}" target="_blank" class="btn btn-xs btn-outline-info">View Slip</a>
                                @endif
                                @if($payment->status === 'pending' && $payment->method === 'slip' && $payment->verification_status === 'failed' && $payment->slip_path)
                                    <form method="POST" action="{{ route('app.payments.recheck-slip', $payment->id) }}" class="d-inline">
                                        @csrf @method('PATCH')
                                        <button class="btn btn-xs btn-warning" onclick="return confirm('Recheck this slip with SlipOK?')">Recheck SlipOK</button>
                                    </form>
                                @endif
                                @if($payment->status === 'pending')
                                    <form method="POST" action="{{ route('app.payments.approve', $payment->id) }}" class="d-inline">
                                        @csrf @method('PATCH')
                                        <button class="btn btn-xs btn-success" onclick="return confirm('Approve this payment?')">Approve</button>
                                    </form>
                                    <form method="POST" action="{{ route('app.payments.reject', $payment->id) }}" class="d-inline">
                                        @csrf @method('PATCH')
                                        <button class="btn btn-xs btn-danger" onclick="return confirm('Reject this payment?')">Reject</button>
                                    </form>
                                @elseif($payment->status === 'approved')
                                    <a href="{{ route('app.payments.receipt', $payment->id) }}" class="btn btn-xs btn-outline-dark">Receipt PDF</a>
                                @else
                                    <span class="badge badge-danger">Rejected</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="text-center text-muted">No payments found</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
