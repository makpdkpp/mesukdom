<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Receipt {{ $payment->id }}</title>
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            color: #0f172a;
            font-size: 12px;
            margin: 24px;
        }

        .header,
        .summary,
        .details {
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
            color: #166534;
        }

        .muted {
            color: #64748b;
        }

        .pill {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 999px;
            background: #dcfce7;
            color: #166534;
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
        .details td {
            border: 1px solid #cbd5e1;
            padding: 10px;
            text-align: left;
        }

        .details th {
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
                <div class="muted">Payment Receipt</div>
            </td>
            <td class="text-right">
                <div class="pill">{{ strtoupper($payment->status) }}</div>
                <h2 style="margin: 12px 0 6px;">{{ $payment->receipt_no ?? 'RECEIPT-'.$payment->id }}</h2>
                <div class="muted">Payment date: {{ optional($payment->payment_date)->format('d/m/Y') }}</div>
                <div class="muted">Invoice: {{ $invoice->invoice_no ?? '-' }}</div>
            </td>
        </tr>
    </table>

    <div class="card">
        <p class="section-title">Receipt Summary</p>
        <table class="summary">
            <tr>
                <td><strong>Resident:</strong> {{ $invoice->customer->name ?? '-' }}</td>
                <td><strong>Room:</strong> {{ $invoice->room->room_number ?? '-' }}</td>
            </tr>
            <tr>
                <td><strong>Method:</strong> {{ ucfirst($payment->method) }}</td>
                <td><strong>Amount Received:</strong> {{ number_format((float) $payment->amount, 2) }} THB</td>
            </tr>
            <tr>
                <td><strong>Invoice Status:</strong> {{ ucfirst($invoice->status ?? '-') }}</td>
                <td><strong>Receipt Status:</strong> {{ ucfirst($payment->status) }}</td>
            </tr>
        </table>
    </div>

    <div class="card">
        <p class="section-title">Referenced Charges</p>
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
                    <td class="text-right">{{ number_format((float) ($invoice->water_fee ?? 0), 2) }}</td>
                </tr>
                <tr>
                    <td>Electricity fee</td>
                    <td class="text-right">{{ number_format((float) ($invoice->electricity_fee ?? 0), 2) }}</td>
                </tr>
                <tr>
                    <td>Service fee</td>
                    <td class="text-right">{{ number_format((float) ($invoice->service_fee ?? 0), 2) }}</td>
                </tr>
                <tr>
                    <th>Invoice total</th>
                    <th class="text-right">{{ number_format((float) ($invoice->total_amount ?? 0), 2) }}</th>
                </tr>
                <tr>
                    <th>Received amount</th>
                    <th class="text-right">{{ number_format((float) $payment->amount, 2) }}</th>
                </tr>
            </tbody>
        </table>
    </div>

    @if($payment->notes)
        <div class="card">
            <p class="section-title">Notes</p>
            <div>{{ $payment->notes }}</div>
        </div>
    @endif
</body>
</html>