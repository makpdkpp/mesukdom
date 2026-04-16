<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\LineWebhookController;
use App\Http\Controllers\PricingController;
use Illuminate\Support\Facades\Route;

Route::view('/', 'landing')->name('landing');

Route::get('/pricing', [PricingController::class, 'index'])->name('pricing');

Route::redirect('/dashboard', '/app/dashboard')->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware(['auth', 'verified'])->prefix('app')->group(function (): void {
    Route::get('/dashboard', [DashboardController::class, 'dashboard'])->name('app.dashboard');

    Route::get('/rooms', [DashboardController::class, 'rooms'])->name('app.rooms');
    Route::post('/rooms', [DashboardController::class, 'storeRoom'])->name('app.rooms.store');

    Route::get('/customers', [DashboardController::class, 'customers'])->name('app.customers');
    Route::post('/customers', [DashboardController::class, 'storeCustomer'])->name('app.customers.store');

    Route::get('/contracts', [DashboardController::class, 'contracts'])->name('app.contracts');
    Route::post('/contracts', [DashboardController::class, 'storeContract'])->name('app.contracts.store');

    Route::get('/invoices', [DashboardController::class, 'invoices'])->name('app.invoices');
    Route::post('/invoices', [DashboardController::class, 'storeInvoice'])->name('app.invoices.store');
    Route::post('/invoices/{invoice}/remind', [DashboardController::class, 'remindInvoice'])->name('app.invoices.remind');

    Route::get('/payments', [DashboardController::class, 'payments'])->name('app.payments');
    Route::post('/payments', [DashboardController::class, 'storePayment'])->name('app.payments.store');
});

Route::get('/admin', [DashboardController::class, 'admin'])->name('admin.dashboard');
Route::get('/resident/invoices/{invoice:public_id}', [DashboardController::class, 'residentInvoice'])->name('resident.invoice');
Route::post('/line/webhook', LineWebhookController::class)->name('line.webhook');
