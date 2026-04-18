<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Utility Fee Entry Reminder</title>
    <style>
        body { font-family: Arial, sans-serif; color: #0f172a; background: #f8fafc; margin: 0; padding: 0; }
        .wrapper { max-width: 560px; margin: 40px auto; background: #fff; border-radius: 10px; overflow: hidden; box-shadow: 0 2px 12px rgba(0,0,0,.08); }
        .header { padding: 24px 32px; color: #fff; background: #92400e; }
        .body { padding: 28px 32px; }
        .body p { line-height: 1.7; }
        .footer { padding: 16px 32px; background: #f1f5f9; text-align: center; font-size: 12px; color: #64748b; }
    </style>
</head>
<body>
<div class="wrapper">
    <div class="header">
        <h1>Utility Fee Entry Reminder</h1>
    </div>
    <div class="body">
        <p>Hi <strong>{{ $owner->name }}</strong>,</p>
        <p>This is a reminder to review and record this month's water units, electricity units, and other charges for <strong>{{ $tenant->name }}</strong> before invoices are generated.</p>
        <p>You can update the default fees or review invoices in the tenant settings and billing screens.</p>
    </div>
    <div class="footer">MesukDorm Dormitory Management - Automated reminder</div>
</div>
</body>
</html>
