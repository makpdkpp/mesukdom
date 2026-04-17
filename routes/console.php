<?php

use App\Console\Commands\ExpireContracts;
use App\Console\Commands\GenerateMonthlyInvoices;
use App\Console\Commands\SendContractExpiryReminders;
use App\Console\Commands\SendOverdueWarnings;
use App\Console\Commands\SendPaymentReminders;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Generate monthly invoices for all active contracts — runs on the 1st of each month at 08:00
Schedule::command(GenerateMonthlyInvoices::class)->monthlyOn(1, '08:00');

// Send reminders for invoices due in 3 days — runs every morning at 09:00
Schedule::command(SendPaymentReminders::class, ['--days=3'])->dailyAt('09:00');

// Send warnings for overdue invoices every morning at 10:00
Schedule::command(SendOverdueWarnings::class, ['--days=1'])->dailyAt('10:00');

// Notify residents when contracts are nearing expiry 30 days out
Schedule::command(SendContractExpiryReminders::class, ['--days=30'])->dailyAt('08:15');

// Send renewal reminders again 7 days before contract end
Schedule::command(SendContractExpiryReminders::class, ['--days=7'])->dailyAt('08:30');

// Expire past-due contracts and free up rooms — runs every morning at 07:00
Schedule::command(ExpireContracts::class)->dailyAt('07:00');
