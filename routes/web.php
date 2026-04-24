<?php

use App\Http\Controllers\BroadcastController;
use App\Http\Controllers\AdminPortalController;
use App\Http\Controllers\BillingController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\PricingController;
use App\Http\Controllers\ResidentLineLinkController;
use App\Http\Controllers\ResidentSupportController;
use App\Support\PublicSiteMetrics;
use Illuminate\Support\Facades\Route;

Route::get('/', function (PublicSiteMetrics $metrics) {
    return view('landing', $metrics->landingPayload());
})->name('landing');

Route::get('/pricing', [PricingController::class, 'index'])->name('pricing');

Route::redirect('/dashboard', '/app/dashboard')->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware(['auth', 'verified', 'role:owner,staff', 'tenant.active'])->prefix('app')->group(function (): void {
    Route::get('/billing', [BillingController::class, 'index'])->name('app.billing');
    Route::post('/billing/checkout', [BillingController::class, 'checkout'])->name('app.billing.checkout');
    Route::get('/billing/success', [BillingController::class, 'success'])->name('app.billing.success');
    Route::get('/billing/cancel', [BillingController::class, 'cancel'])->name('app.billing.cancel');
    Route::post('/billing/portal', [BillingController::class, 'portal'])->name('app.billing.portal');
});

Route::middleware(['auth', 'verified', 'role:owner,staff', 'tenant.active', 'tenant.write'])->prefix('app')->group(function (): void {
    Route::get('/dashboard', [DashboardController::class, 'dashboard'])->name('app.dashboard');
    Route::get('/room-status', [DashboardController::class, 'roomStatus'])->name('app.room-status');
    Route::get('/utility', [DashboardController::class, 'utilities'])->name('app.utility');
    Route::post('/utility', [DashboardController::class, 'storeUtilityRecord'])->name('app.utility.store');
    Route::get('/buildings', [DashboardController::class, 'buildings'])->name('app.buildings');
    Route::post('/buildings', [DashboardController::class, 'storeBuilding'])->name('app.buildings.store');
    Route::put('/buildings/{building}', [DashboardController::class, 'updateBuilding'])->name('app.buildings.update');
    Route::delete('/buildings/{building}', [DashboardController::class, 'destroyBuilding'])->name('app.buildings.destroy');

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
    Route::post('/payments', [DashboardController::class, 'storePayment'])->middleware('api.monitor')->name('app.payments.store');
    Route::get('/payments/{payment}/receipt', [DashboardController::class, 'downloadReceiptPdf'])->name('app.payments.receipt');
    Route::get('/payments/{payment}/slip', [DashboardController::class, 'viewSlip'])->name('app.payments.slip');
    Route::patch('/payments/{payment}/recheck-slip', [DashboardController::class, 'recheckPaymentSlip'])->middleware('api.monitor')->name('app.payments.recheck-slip');
    Route::patch('/payments/{payment}/approve', [DashboardController::class, 'approvePayment'])->name('app.payments.approve');
    Route::patch('/payments/{payment}/reject', [DashboardController::class, 'rejectPayment'])->name('app.payments.reject');
    
    Route::get('/broadcasts', [BroadcastController::class, 'index'])->name('app.broadcasts');
    Route::post('/broadcasts', [BroadcastController::class, 'store'])->name('app.broadcasts.store');
    Route::get('/line-activity', [DashboardController::class, 'lineActivity'])->name('app.line-activity');
    Route::get('/repairs', [DashboardController::class, 'repairs'])->name('app.repairs');
    Route::patch('/repairs/{repairRequest}', [DashboardController::class, 'updateRepairRequest'])->name('app.repairs.update');

    Route::get('/settings', [DashboardController::class, 'settings'])->name('app.settings');
    Route::post('/settings', [DashboardController::class, 'updateSettings'])->name('app.settings.update');
    Route::post('/settings/line-rich-menu/sync', [DashboardController::class, 'syncLineRichMenu'])->name('app.settings.line-rich-menu.sync');

    Route::post('/owner-line/link-token', [DashboardController::class, 'createOwnerLineLink'])->name('app.owner-line.link-token');
    Route::post('/owner-line/unlink', [DashboardController::class, 'unlinkOwnerLine'])->name('app.owner-line.unlink');

    Route::get('/invoices/{invoice}/promptpay-qr', [DashboardController::class, 'promptpayQr'])->name('app.invoices.promptpay-qr');
});

Route::middleware(['auth', 'verified', 'role:super_admin,support_admin'])->prefix('admin')->group(function (): void {
    Route::get('/', [AdminPortalController::class, 'dashboard'])->name('admin.dashboard');
    Route::get('/api-monitor', [AdminPortalController::class, 'apiMonitor'])->name('admin.api-monitor');
    Route::get('/profile', [AdminPortalController::class, 'profile'])->name('admin.profile');
    Route::get('/dbmigration', [AdminPortalController::class, 'dbMigration'])->name('admin.dbmigration');
    Route::get('/tenants', [AdminPortalController::class, 'tenants'])->name('admin.tenants');
    Route::get('/platform', [AdminPortalController::class, 'index'])->name('admin.platform');
    Route::get('/packages', [AdminPortalController::class, 'packages'])->name('admin.packages');

    Route::get('/notifications', [AdminPortalController::class, 'notifications'])->name('admin.notifications');
    Route::post('/notifications', [AdminPortalController::class, 'updateNotifications'])->name('admin.notifications.update');
    Route::get('/platform-line', [AdminPortalController::class, 'platformLine'])->name('admin.platform-line');
    Route::post('/platform-line/settings', [AdminPortalController::class, 'updatePlatformLineSettings'])->name('admin.platform-line.settings.update');
    Route::post('/platform-line/link-token', [AdminPortalController::class, 'createPlatformLineLink'])->name('admin.platform-line.link-token');
    Route::post('/platform-line/unlink', [AdminPortalController::class, 'unlinkPlatformLine'])->name('admin.platform-line.unlink');
    Route::post('/platform-line/broadcast', [AdminPortalController::class, 'sendPlatformBroadcast'])->name('admin.platform-line.broadcast');

    Route::post('/maintenance/migrate', [AdminPortalController::class, 'migrate'])->name('admin.maintenance.migrate');
    Route::post('/maintenance/rollback', [AdminPortalController::class, 'rollback'])->name('admin.maintenance.rollback');

    Route::post('/packages', [AdminPortalController::class, 'storePackage'])->name('admin.packages.store');
    Route::patch('/packages/{plan}', [AdminPortalController::class, 'updatePackage'])->name('admin.packages.update');

    Route::post('/slipok/settings', [AdminPortalController::class, 'updateSlipOkSettings'])->name('admin.slipok.settings.update');
    Route::post('/stripe/settings', [AdminPortalController::class, 'updateStripeSettings'])->name('admin.stripe.settings.update');
    Route::patch('/plans/{plan}/slipok', [AdminPortalController::class, 'updatePlanSlipOkSettings'])->name('admin.plans.slipok.update');
    Route::patch('/tenants/{tenant}/plan', [AdminPortalController::class, 'updateTenantPlan'])->name('admin.tenants.plan.update');
    Route::patch('/tenants/{tenantId}/restore', [AdminPortalController::class, 'restoreTenant'])->name('admin.tenants.restore');
    Route::delete('/tenants/{tenant}', [AdminPortalController::class, 'destroyTenant'])->name('admin.tenants.destroy');
    Route::patch('/tenants/{tenant}/suspend', [DashboardController::class, 'suspendTenant'])->name('admin.tenants.suspend');
    Route::patch('/tenants/{tenant}/unsuspend', [DashboardController::class, 'unsuspendTenant'])->name('admin.tenants.unsuspend');
});
Route::middleware('signed')->group(function (): void {
    Route::get('/resident/invoices/{invoice:public_id}', [DashboardController::class, 'residentInvoice'])->name('resident.invoice');
    Route::get('/resident/invoices/{invoice:public_id}/payments/{payment}/receipt', [DashboardController::class, 'residentDownloadReceipt'])->name('resident.invoice.receipt');
    Route::get('/resident/line/link/{tenant}', [ResidentLineLinkController::class, 'create'])->name('resident.line.link.create');
    Route::post('/resident/line/link/{tenant}', [ResidentLineLinkController::class, 'store'])->name('resident.line.link.store');
    Route::get('/resident/line/repair/{customer}', [ResidentSupportController::class, 'createRepairRequest'])->name('resident.line.repair.create');
    Route::post('/resident/line/repair/{customer}', [ResidentSupportController::class, 'storeRepairRequest'])->name('resident.line.repair.store');
});
Route::post('/resident/invoices/{invoice:public_id}/pay-slip', [DashboardController::class, 'residentPaySlip'])->middleware('api.monitor')->name('resident.invoice.pay-slip');
