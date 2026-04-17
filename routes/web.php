<?php

use App\Http\Controllers\BroadcastController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\PricingController;
use App\Http\Controllers\ResidentSupportController;
use App\Support\PublicSiteMetrics;
use Illuminate\Support\Facades\Route;

Route::get('/', function (PublicSiteMetrics $metrics) {
    return view('landing', $metrics->landingPayload());
})->name('landing');

Route::get('/pricing', [PricingController::class, 'index'])->name('pricing');

Route::redirect('/dashboard', '/app/dashboard')->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware(['auth', 'verified', 'role:owner,staff', 'tenant.active'])->prefix('app')->group(function (): void {
    Route::get('/dashboard', [DashboardController::class, 'dashboard'])->name('app.dashboard');

    Route::get('/rooms', [DashboardController::class, 'rooms'])->name('app.rooms');
    Route::post('/rooms', [DashboardController::class, 'storeRoom'])->name('app.rooms.store');
    Route::put('/rooms/{room}', [DashboardController::class, 'updateRoom'])->name('app.rooms.update');
    Route::delete('/rooms/{room}', [DashboardController::class, 'destroyRoom'])->name('app.rooms.destroy');

    Route::get('/customers', [DashboardController::class, 'customers'])->name('app.customers');
    Route::post('/customers', [DashboardController::class, 'storeCustomer'])->name('app.customers.store');
    Route::put('/customers/{customer}', [DashboardController::class, 'updateCustomer'])->name('app.customers.update');
    Route::delete('/customers/{customer}', [DashboardController::class, 'destroyCustomer'])->name('app.customers.destroy');
    Route::post('/customers/{customer}/line-link', [DashboardController::class, 'createCustomerLineLink'])->name('app.customers.line-link.store');
    Route::post('/customers/{customer}/documents', [DashboardController::class, 'uploadCustomerDocument'])->name('app.customers.documents.store');
    Route::delete('/customers/{customer}/documents/{document}', [DashboardController::class, 'destroyCustomerDocument'])->name('app.customers.documents.destroy');

    Route::get('/contracts', [DashboardController::class, 'contracts'])->name('app.contracts');
    Route::post('/contracts', [DashboardController::class, 'storeContract'])->name('app.contracts.store');
    Route::put('/contracts/{contract}', [DashboardController::class, 'updateContract'])->name('app.contracts.update');
    Route::delete('/contracts/{contract}', [DashboardController::class, 'destroyContract'])->name('app.contracts.destroy');

    Route::get('/invoices', [DashboardController::class, 'invoices'])->name('app.invoices');
    Route::post('/invoices', [DashboardController::class, 'storeInvoice'])->name('app.invoices.store');
    Route::get('/invoices/{invoice}/pdf', [DashboardController::class, 'downloadInvoicePdf'])->name('app.invoices.pdf');
    Route::post('/invoices/{invoice}/remind', [DashboardController::class, 'remindInvoice'])->name('app.invoices.remind');

    Route::get('/payments', [DashboardController::class, 'payments'])->name('app.payments');
    Route::post('/payments', [DashboardController::class, 'storePayment'])->name('app.payments.store');
    Route::get('/payments/{payment}/receipt', [DashboardController::class, 'downloadReceiptPdf'])->name('app.payments.receipt');
    Route::get('/payments/{payment}/slip', [DashboardController::class, 'viewSlip'])->name('app.payments.slip');
    Route::patch('/payments/{payment}/approve', [DashboardController::class, 'approvePayment'])->name('app.payments.approve');
    Route::patch('/payments/{payment}/reject', [DashboardController::class, 'rejectPayment'])->name('app.payments.reject');
    
    Route::get('/broadcasts', [BroadcastController::class, 'index'])->name('app.broadcasts');
    Route::post('/broadcasts', [BroadcastController::class, 'store'])->name('app.broadcasts.store');

    Route::get('/settings', [DashboardController::class, 'settings'])->name('app.settings');
    Route::post('/settings', [DashboardController::class, 'updateSettings'])->name('app.settings.update');

    Route::get('/invoices/{invoice}/promptpay-qr', [DashboardController::class, 'promptpayQr'])->name('app.invoices.promptpay-qr');
});

Route::middleware(['auth', 'verified', 'role:super_admin,support_admin'])->prefix('admin')->group(function (): void {
    Route::get('/', [DashboardController::class, 'admin'])->name('admin.dashboard');
    Route::patch('/tenants/{tenant}/suspend', [DashboardController::class, 'suspendTenant'])->name('admin.tenants.suspend');
    Route::patch('/tenants/{tenant}/unsuspend', [DashboardController::class, 'unsuspendTenant'])->name('admin.tenants.unsuspend');
});
Route::middleware('signed')->group(function (): void {
    Route::get('/resident/invoices/{invoice:public_id}', [DashboardController::class, 'residentInvoice'])->name('resident.invoice');
    Route::get('/resident/invoices/{invoice:public_id}/payments/{payment}/receipt', [DashboardController::class, 'residentDownloadReceipt'])->name('resident.invoice.receipt');
    Route::get('/resident/line/repair/{customer}', [ResidentSupportController::class, 'createRepairRequest'])->name('resident.line.repair.create');
    Route::post('/resident/line/repair/{customer}', [ResidentSupportController::class, 'storeRepairRequest'])->name('resident.line.repair.store');
});
Route::post('/resident/invoices/{invoice:public_id}/pay-slip', [DashboardController::class, 'residentPaySlip'])->name('resident.invoice.pay-slip');
