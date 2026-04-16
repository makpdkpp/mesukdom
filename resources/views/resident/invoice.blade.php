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
                    <li class="list-group-item d-flex justify-content-between">
                        <span>{{ ucfirst($payment->method) }} • {{ ucfirst($payment->status) }}</span>
                        <span>{{ number_format((float) $payment->amount, 2) }} THB</span>
                    </li>
                @empty
                    <li class="list-group-item text-muted">No payment records yet</li>
                @endforelse
            </ul>
        </div>
    </div>
</div>
</body>
</html>
