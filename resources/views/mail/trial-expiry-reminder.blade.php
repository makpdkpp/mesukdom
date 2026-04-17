<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Trial Expiry Reminder</title>
    <style>
        body { font-family: Arial, sans-serif; color: #0f172a; background: #f8fafc; margin: 0; padding: 0; }
        .wrapper { max-width: 560px; margin: 40px auto; background: #fff; border-radius: 10px; overflow: hidden; box-shadow: 0 2px 12px rgba(0,0,0,.08); }
        .header { padding: 24px 32px; color: #fff; background: #0f172a; }
        .header h1 { margin: 0; font-size: 20px; }
        .body { padding: 28px 32px; }
        .body p { line-height: 1.7; }
        .badge { display: inline-block; padding: 6px 12px; border-radius: 999px; background: #fef3c7; color: #92400e; font-weight: bold; font-size: 12px; }
        .cta { display: inline-block; margin-top: 14px; padding: 10px 14px; border-radius: 10px; background: #0f172a; color: #fff; text-decoration: none; font-weight: bold; }
        .footer { padding: 16px 32px; background: #f1f5f9; text-align: center; font-size: 12px; color: #64748b; }
    </style>
</head>
<body>
<div class="wrapper">
    <div class="header">
        <h1>Trial Expiry Reminder</h1>
    </div>

    <div class="body">
        <p>Hi <strong>{{ $owner->name }}</strong>,</p>

        <p>Your MesukDom trial for <strong>{{ $tenant->name }}</strong> is ending soon.</p>

        <p>
            <span class="badge">
                Trial ends on {{ optional($tenant->trial_ends_at)->format('d/m/Y') }}
            </span>
        </p>

        @if($days <= 0)
            <p>Your trial ends <strong>today</strong>. You can still view your data, but changes will be locked until you upgrade.</p>
        @else
            <p>Your trial ends in <strong>{{ $days }}</strong> days. You can still view your data, but changes will be locked after the trial ends until you upgrade.</p>
        @endif

        <p>
            <a class="cta" href="{{ url('/pricing') }}">View plans & upgrade</a>
        </p>

        <p style="margin-top: 16px; color: #475569;">
            If you have any questions, contact support.
        </p>
    </div>

    <div class="footer">MesukDom — Automated message</div>
</div>
</body>
</html>
