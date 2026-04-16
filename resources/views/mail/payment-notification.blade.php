<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Payment Notification</title>
    <style>
        body { font-family: Arial, sans-serif; color: #0f172a; background: #f8fafc; margin: 0; padding: 0; }
        .wrapper { max-width: 560px; margin: 40px auto; background: #fff; border-radius: 10px; overflow: hidden; box-shadow: 0 2px 12px rgba(0,0,0,.08); }
        .header { padding: 24px 32px; color: #fff; }
        .header.approved { background: #166534; }
        .header.rejected { background: #991b1b; }
        .header h1 { margin: 0; font-size: 20px; }
        .body { padding: 28px 32px; }
        .body p { line-height: 1.7; }
        .amount { font-size: 28px; font-weight: bold; color: #0f172a; margin: 12px 0; }
        .detail-row { display: flex; justify-content: space-between; border-bottom: 1px solid #e2e8f0; padding: 8px 0; font-size: 14px; }
        .footer { padding: 16px 32px; background: #f1f5f9; text-align: center; font-size: 12px; color: #64748b; }
    </style>
</head>
<body>
<div class="wrapper">
    <div class="header {{ $event === 'payment_approved' ? 'approved' : 'rejected' }}">
        <h1>
            @if($event === 'payment_approved')
                ✔ Payment Approved
            @else
                ✖ Payment Not Approved
            @endif
        </h1>
    </div>

    <div class="body">
        <p>Dear <strong>{{ $payment->invoice?->customer?->name ?? 'Resident' }}</strong>,</p>

        @if($event === 'payment_approved')
            <p>Your payment has been <strong>approved</strong>. Your invoice is now marked as <strong>paid</strong>. Thank you!</p>
        @else
            <p>Your payment has been <strong>rejected</strong> by the dormitory owner. Please review your payment slip and submit again, or contact the dorm office.</p>
        @endif

        <div class="amount">{{ number_format((float) $payment->amount, 2) }} THB</div>

        <div class="detail-row"><span>Invoice</span><span>{{ $payment->invoice?->invoice_no ?? '-' }}</span></div>
        <div class="detail-row"><span>Room</span><span>{{ $payment->invoice?->room?->room_number ?? '-' }}</span></div>
        <div class="detail-row"><span>Payment Date</span><span>{{ optional($payment->payment_date)->format('d/m/Y') }}</span></div>
        <div class="detail-row"><span>Method</span><span>{{ ucfirst($payment->method) }}</span></div>

        @if($event === 'payment_approved')
            <p style="margin-top:20px">You may download your receipt from the invoice portal:</p>
            @if($invoiceUrl)
                <p><a href="{{ $invoiceUrl }}" style="color:#166534;font-weight:bold;">View Invoice &amp; Download Receipt</a></p>
            @endif
        @else
            <p style="margin-top:20px">Please resubmit via your invoice portal:
                @if($invoiceUrl)
                    <a href="{{ $invoiceUrl }}" style="color:#991b1b;">Open Invoice</a>
                @endif
            </p>
        @endif
    </div>

    <div class="footer">MesukDom Dormitory Management &mdash; This is an automated notification.</div>
</div>
</body>
</html>
