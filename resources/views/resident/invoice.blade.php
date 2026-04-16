<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Resident Invoice</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
</head>
<body class="bg-light">
<div class="container py-5">
    <div class="card shadow-sm">
        <div class="card-header bg-success text-white">
            <h3 class="mb-0">Resident Invoice Portal</h3>
        </div>
        <div class="card-body">
            <div class="row mb-4">
                <div class="col-md-6">
                    <h5>Invoice {{ $invoice->invoice_no }}</h5>
                    <p class="mb-1"><strong>Resident:</strong> {{ $invoice->customer->name ?? '-' }}</p>
                    <p class="mb-1"><strong>Room:</strong> {{ $invoice->room->room_number ?? '-' }}</p>
                    <p class="mb-1"><strong>Status:</strong> {{ ucfirst($invoice->status) }}</p>
                </div>
                <div class="col-md-6 text-md-right">
                    <h2 class="text-success">{{ number_format((float) $invoice->total_amount, 2) }} THB</h2>
                    <p class="mb-1"><strong>Due:</strong> {{ $invoice->due_date->format('d/m/Y') }}</p>
                    <p class="mb-0 text-muted">Access via Email / LINE OA / SMS link</p>
                </div>
            </div>

            <table class="table table-bordered">
                <tr><th>Monthly rent</th><td>{{ number_format((float) $invoice->contract->monthly_rent, 2) }}</td></tr>
                <tr><th>Water fee</th><td>{{ number_format((float) $invoice->water_fee, 2) }}</td></tr>
                <tr><th>Electricity fee</th><td>{{ number_format((float) $invoice->electricity_fee, 2) }}</td></tr>
                <tr><th>Service fee</th><td>{{ number_format((float) $invoice->service_fee, 2) }}</td></tr>
            </table>

            <h5 class="mt-4">Payment History</h5>
            <ul class="list-group">
                @forelse($invoice->payments as $payment)
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <span>{{ ucfirst($payment->method) }} &bull; {{ ucfirst($payment->status) }}</span>
                        <span class="d-flex align-items-center gap-2">
                            {{ number_format((float) $payment->amount, 2) }} THB
                            @if($payment->status === 'approved')
                                &nbsp;
                                <a href="{{ \Illuminate\Support\Facades\URL::signedRoute('resident.invoice.receipt', [$invoice->public_id, $payment->id]) }}" class="btn btn-sm btn-outline-success ml-2">Download Receipt</a>
                            @endif
                        </span>
                    </li>
                @empty
                    <li class="list-group-item text-muted">No payment records yet</li>
                @endforelse
            </ul>

            @if(!in_array($invoice->status, ['paid', 'cancelled']))
            {{-- PromptPay QR Section --}}
            @if($invoice->tenant?->promptpay_number && isset($promptpayQr))
            <div class="card mt-4 border-primary">
                <div class="card-header bg-primary text-white"><strong>PromptPay QR Code</strong></div>
                <div class="card-body text-center">
                    <p class="mb-2">สแกน QR เพื่อชำระเงิน {{ number_format((float) $invoice->total_amount, 2) }} THB</p>
                    <img src="data:image/svg+xml;base64,{{ base64_encode($promptpayQr) }}"
                         alt="PromptPay QR" width="240" height="240" class="border rounded p-2 mb-2">
                    <p class="text-muted small mb-0">หลังชำระเงิน กรุณาแนบสลิปด้านล่าง</p>
                </div>
            </div>
            @endif

            <div class="card mt-4 border-success">
                <div class="card-header bg-success text-white"><strong>Submit Payment Slip</strong></div>
                <div class="card-body">
                    @if(session('status'))
                        <div class="alert alert-success">{{ session('status') }}</div>
                    @endif
                    @if(session('error'))
                        <div class="alert alert-danger">{{ session('error') }}</div>
                    @endif
                    <form method="POST" action="{{ route('resident.invoice.pay-slip', $invoice->public_id) }}" enctype="multipart/form-data">
                        @csrf
                        <div class="form-group">
                            <label>Amount Paid (THB)</label>
                            <input name="amount" type="number" step="0.01" class="form-control @error('amount') is-invalid @enderror" value="{{ old('amount', $invoice->total_amount) }}" required>
                            @error('amount') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="form-group">
                            <label>Payment Date</label>
                            <input name="payment_date" type="date" class="form-control @error('payment_date') is-invalid @enderror" value="{{ old('payment_date', now()->toDateString()) }}" required>
                            @error('payment_date') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="form-group">
                            <label>Payment Slip <span class="text-muted">(jpg/png/pdf, max 5 MB)</span></label>
                            <input name="slip" type="file" class="form-control-file @error('slip') is-invalid @enderror" accept=".jpg,.jpeg,.png,.pdf" required>
                            @error('slip') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <button type="submit" class="btn btn-success">Submit Slip</button>
                    </form>
                </div>
            </div>
            @else
            <div class="alert alert-success mt-4">This invoice has been settled. Thank you! You can download your receipt from the payment history above.</div>
            @endif
        </div>
    </div>
</div>
</body>
</html>
