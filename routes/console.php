<?php

use App\Console\Commands\ExpireContracts;
use App\Console\Commands\GenerateMonthlyInvoices;
use App\Console\Commands\SendInvoiceLinks;
use App\Console\Commands\SendTrialExpiryReminders;
use App\Console\Commands\SendContractExpiryReminders;
use App\Console\Commands\SendOverdueWarnings;
use App\Console\Commands\SendPaymentReminders;
use App\Console\Commands\SendUtilityFeeEntryReminders;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Generate invoices daily; each tenant decides whether today is its configured billing day.
Schedule::command(GenerateMonthlyInvoices::class)->dailyAt('08:00')->withoutOverlapping();

// Remind owners to record monthly utility/service fees before billing is generated.
Schedule::command(SendUtilityFeeEntryReminders::class)->dailyAt('07:30')->withoutOverlapping();

// Send invoice links daily; each tenant decides whether today is its configured send day.
Schedule::command(SendInvoiceLinks::class)->dailyAt('08:30')->withoutOverlapping();

// Send reminders for invoices due in 3 days — runs every morning at 09:00
Schedule::command(SendPaymentReminders::class, ['--days=3'])->dailyAt('09:00')->withoutOverlapping();

// Send warnings for overdue invoices every morning at 10:00
Schedule::command(SendOverdueWarnings::class, ['--days=1'])->dailyAt('10:00')->withoutOverlapping();

// Notify residents when contracts are nearing expiry 30 days out
Schedule::command(SendContractExpiryReminders::class, ['--days=30'])->dailyAt('08:15')->withoutOverlapping();

// Send renewal reminders again 7 days before contract end
Schedule::command(SendContractExpiryReminders::class, ['--days=7'])->dailyAt('08:30')->withoutOverlapping();

// Expire past-due contracts and free up rooms — runs every morning at 07:00
Schedule::command(ExpireContracts::class)->dailyAt('07:00')->withoutOverlapping();

// Notify tenant owners when trial is about to expire (3 days out) — runs every morning at 08:45
Schedule::command(SendTrialExpiryReminders::class, ['--days=3'])->dailyAt('08:45')->withoutOverlapping();
