<?php

use App\Console\Commands\ExpireContracts;
use App\Console\Commands\GenerateMonthlyInvoices;
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

// Expire past-due contracts and free up rooms — runs every morning at 07:00
Schedule::command(ExpireContracts::class)->dailyAt('07:00');
