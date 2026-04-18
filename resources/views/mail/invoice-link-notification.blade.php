<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Invoice Notification</title>
    <style>
        body { font-family: Arial, sans-serif; color: #0f172a; background: #f8fafc; margin: 0; padding: 0; }
        .wrapper { max-width: 560px; margin: 40px auto; background: #fff; border-radius: 10px; overflow: hidden; box-shadow: 0 2px 12px rgba(0,0,0,.08); }
        .header { padding: 24px 32px; color: #fff; }
        .header.invoice { background: #1d4ed8; }
        .header.overdue { background: #b91c1c; }
        .body { padding: 28px 32px; }
        .body p { line-height: 1.7; }
        .amount { font-size: 28px; font-weight: bold; margin: 12px 0; }
        .detail-row { display: flex; justify-content: space-between; border-bottom: 1px solid #e2e8f0; padding: 8px 0; font-size: 14px; }
        .cta { display: inline-block; margin-top: 16px; padding: 10px 14px; border-radius: 10px; color: #fff; text-decoration: none; font-weight: bold; background: #0f172a; }
        .footer { padding: 16px 32px; background: #f1f5f9; text-align: center; font-size: 12px; color: #64748b; }
    </style>
</head>
<body>
<div class="wrapper">
    <div class="header {{ $notificationType === 'overdue_warning' ? 'overdue' : 'invoice' }}">
        <h1>{{ $notificationType === 'overdue_warning' ? 'Overdue Invoice Reminder' : 'Invoice Ready' }}</h1>
    </div>

    <div class="body">
        <p>Dear <strong>{{ $customer?->name ?? 'Resident' }}</strong>,</p>

        @if($notificationType === 'overdue_warning')
            <p>Your invoice is overdue. Please review the details below and complete payment as soon as possible.</p>
        @else
            <p>Your monthly invoice is ready. You can open the resident invoice page from the link below.</p>
        @endif

        <div class="amount">{{ number_format((float) $invoice->total_amount, 2) }} THB</div>

        <div class="detail-row"><span>Invoice</span><span>{{ $invoice->invoice_no }}</span></div>
        <div class="detail-row"><span>Due Date</span><span>{{ optional($invoice->due_date)->format('d/m/Y') }}</span></div>
        <div class="detail-row"><span>Status</span><span>{{ strtoupper($invoice->status) }}</span></div>
        <div class="detail-row"><span>Room Price</span><span>{{ number_format((float) ($invoice->room_fee ?? $invoice->contract?->monthly_rent ?? 0), 2) }} THB</span></div>
        <div class="detail-row"><span>Water Fee</span><span>{{ number_format((float) $invoice->water_fee, 2) }} THB</span></div>
        <div class="detail-row"><span>Electricity Fee</span><span>{{ number_format((float) $invoice->electricity_fee, 2) }} THB</span></div>
        <div class="detail-row"><span>Other Charges</span><span>{{ number_format((float) $invoice->service_fee, 2) }} THB</span></div>

        <p>
            <a href="{{ $invoiceUrl }}" class="cta">Open Invoice</a>
        </p>
    </div>

    <div class="footer">MesukDorm Dormitory Management - Automated notification</div>
</div>
</body>
</html>
