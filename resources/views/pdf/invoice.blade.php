<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>{{ $invoice->invoice_no }}</title>
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            color: #0f172a;
            font-size: 12px;
            margin: 24px;
        }

        .header,
        .summary,
        .details,
        .payments {
            width: 100%;
            border-collapse: collapse;
        }

        .header td {
            vertical-align: top;
            padding-bottom: 18px;
        }

        .brand {
            font-size: 24px;
            font-weight: bold;
            color: #0f766e;
        }

        .muted {
            color: #64748b;
        }

        .pill {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 999px;
            background: #e0f2fe;
            color: #0369a1;
            font-size: 11px;
            font-weight: bold;
        }

        .card {
            border: 1px solid #cbd5e1;
            border-radius: 10px;
            padding: 16px;
            margin-bottom: 18px;
        }

        .summary td {
            width: 50%;
            padding: 6px 0;
        }

        .details th,
        .details td,
        .payments th,
        .payments td {
            border: 1px solid #cbd5e1;
            padding: 10px;
            text-align: left;
        }

        .details th,
        .payments th {
            background: #f8fafc;
        }

        .text-right {
            text-align: right;
        }

        .section-title {
            font-size: 14px;
            font-weight: bold;
            margin: 0 0 10px;
        }
    </style>
</head>
<body>
    <table class="header">
        <tr>
            <td>
                <div class="brand">MesukDom</div>
                <div class="muted">Dormitory Management SaaS</div>
                <div class="muted">Invoice PDF</div>
            </td>
            <td class="text-right">
                <div class="pill">{{ strtoupper($invoice->status) }}</div>
                <h2 style="margin: 12px 0 6px;">{{ $invoice->invoice_no }}</h2>
                <div class="muted">Issued: {{ optional($invoice->issued_at)->format('d/m/Y') }}</div>
                <div class="muted">Due: {{ optional($invoice->due_date)->format('d/m/Y') }}</div>
            </td>
        </tr>
    </table>

    <div class="card">
        <p class="section-title">Invoice Summary</p>
        <table class="summary">
            <tr>
                <td><strong>Resident:</strong> {{ $invoice->customer->name ?? '-' }}</td>
                <td><strong>Room:</strong> {{ $invoice->room->room_number ?? '-' }}</td>
            </tr>
            <tr>
                <td><strong>Contract Period:</strong> {{ optional($invoice->contract?->start_date)->format('d/m/Y') }} - {{ optional($invoice->contract?->end_date)->format('d/m/Y') }}</td>
                <td><strong>Total Amount:</strong> {{ number_format((float) $invoice->total_amount, 2) }} THB</td>
            </tr>
        </table>
    </div>

    <div class="card">
        <p class="section-title">Charges</p>
        <table class="details">
            <thead>
                <tr>
                    <th>Item</th>
                    <th class="text-right">Amount (THB)</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>Monthly rent</td>
                    <td class="text-right">{{ number_format((float) ($invoice->contract->monthly_rent ?? 0), 2) }}</td>
                </tr>
                <tr>
                    <td>Water fee</td>
                    <td class="text-right">{{ number_format((float) $invoice->water_fee, 2) }}</td>
                </tr>
                <tr>
                    <td>Electricity fee</td>
                    <td class="text-right">{{ number_format((float) $invoice->electricity_fee, 2) }}</td>
                </tr>
                <tr>
                    <td>Service fee</td>
                    <td class="text-right">{{ number_format((float) $invoice->service_fee, 2) }}</td>
                </tr>
                <tr>
                    <th>Total</th>
                    <th class="text-right">{{ number_format((float) $invoice->total_amount, 2) }}</th>
                </tr>
            </tbody>
        </table>
    </div>

    <div class="card">
        <p class="section-title">Payment History</p>
        <table class="payments">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Method</th>
                    <th>Status</th>
                    <th class="text-right">Amount (THB)</th>
                </tr>
            </thead>
            <tbody>
                @forelse($invoice->payments as $payment)
                    <tr>
                        <td>{{ optional($payment->payment_date)->format('d/m/Y') }}</td>
                        <td>{{ ucfirst($payment->method) }}</td>
                        <td>{{ ucfirst($payment->status) }}</td>
                        <td class="text-right">{{ number_format((float) $payment->amount, 2) }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="muted">No payment records yet</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</body>
</html>