<?php

namespace App\Http\Controllers;

use App\Jobs\SendLineMessageJob;
use App\Mail\PaymentNotification;
use App\Models\Contract;
use App\Models\Customer;
use App\Models\CustomerDocument;
use App\Models\CustomerLineLink;
use App\Models\Invoice;
use App\Models\LineMessage;
use App\Models\LineWebhookLog;
use App\Models\NotificationLog;
use App\Models\Payment;
use App\Models\Plan;
use App\Models\Room;
use App\Models\SlipVerificationUsage;
use App\Models\Tenant;
use App\Models\User;
use App\Services\Line\LineService;
use App\Services\PromptPayService;
use App\Services\SlipVerificationService;
use App\Support\TenantContext;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function dashboard(): View
    {
        $this->refreshInvoiceStatuses();

        $revenueTrend = $this->buildRevenueTrend();
        $tenant = app(TenantContext::class)->tenant();
        $subscriptionPlan = $tenant?->plan_id
            ? Plan::query()->find($tenant->plan_id)
            : null;
        $slipOkUsageThisMonth = $tenant
            ? SlipVerificationUsage::query()
                ->where('tenant_id', $tenant->id)
                ->where('provider', 'slipok')
                ->where('usage_month', now()->format('Y-m'))
                ->count()
            : 0;
        $slipOkUsageLimit = $subscriptionPlan?->slipOkMonthlyLimit() ?? 0;
        $slipOkEnabled = $subscriptionPlan?->supportsSlipOk() ?? false;
        $slipOkRemaining = $slipOkEnabled
            ? ($slipOkUsageLimit > 0 ? max(0, $slipOkUsageLimit - $slipOkUsageThisMonth) : null)
            : 0;
        $slipOkUsagePercent = $slipOkEnabled && $slipOkUsageLimit > 0
            ? min(100, (int) round(($slipOkUsageThisMonth / max(1, $slipOkUsageLimit)) * 100))
            : 0;

        $stats = [
            'rooms_total' => Room::count(),
            'rooms_vacant' => Room::where('status', 'vacant')->count(),
            'rooms_occupied' => Room::where('status', 'occupied')->count(),
            'monthly_revenue' => Payment::where('status', 'approved')->whereMonth('payment_date', now()->month)->sum('amount'),
            'overdue_invoices' => Invoice::where('status', 'overdue')->count(),
            'pending_payments' => Payment::where('status', 'pending')->count(),
        ];

        return view('dashboard.index', [
            'tenant' => $tenant,
            'stats' => $stats,
            'revenueTrend' => $revenueTrend,
            'revenueTrendMax' => max(1, $revenueTrend->max('total')),
            'rooms' => Room::query()->orderBy('room_number')->get(),
            'recentInvoices' => Invoice::query()->with(['customer', 'room'])->latest('due_date')->take(5)->get(),
            'subscriptionPlan' => $subscriptionPlan,
            'slipOkEnabled' => $slipOkEnabled,
            'slipOkUsageThisMonth' => $slipOkUsageThisMonth,
            'slipOkUsageLimit' => $slipOkUsageLimit,
            'slipOkRemaining' => $slipOkRemaining,
            'slipOkUsagePercent' => $slipOkUsagePercent,
        ]);
    }

    public function rooms(): View
    {
        return view('dashboard.rooms', [
            'rooms' => Room::query()->orderBy('room_number')->get(),
        ]);
    }

    public function storeRoom(Request $request): RedirectResponse
    {
        $tenant = app(TenantContext::class)->tenant();
        $plan = $tenant?->resolvedPlan();
        $roomsLimit = $plan?->roomsLimit() ?? 0;

        if ($roomsLimit > 0 && Room::query()->count() >= $roomsLimit) {
            return back()
                ->withInput()
                ->withErrors(['room_number' => 'Room limit reached for your current package. Please upgrade to add more rooms.']);
        }

        Room::create($this->validateRoom($request));

        return back()->with('status', 'Room saved successfully.');
    }

    public function updateRoom(Request $request, int $room): RedirectResponse
    {
        $room = Room::query()->findOrFail($room);
        $room->update($this->validateRoom($request));

        return back()->with('status', 'Room updated successfully.');
    }

    public function destroyRoom(int $room): RedirectResponse
    {
        $room = Room::query()->findOrFail($room);
        $room->delete();

        return back()->with('status', 'Room deleted successfully.');
    }

    public function customers(): View
    {
        return view('dashboard.customers', [
            'customers' => Customer::query()
                ->with(['room', 'contracts.room', 'documents', 'lineLinks'])
                ->orderBy('name')
                ->get(),
            'rooms' => Room::query()->orderBy('room_number')->get(),
        ]);
    }

    public function storeCustomer(Request $request): RedirectResponse
    {
        Customer::create($this->validateCustomer($request));

        return back()->with('status', 'Customer saved successfully.');
    }

    public function updateCustomer(Request $request, int $customer): RedirectResponse
    {
        $customer = Customer::query()->findOrFail($customer);
        $customer->update($this->validateCustomer($request));

        return back()->with('status', 'Customer updated successfully.');
    }

    public function destroyCustomer(int $customer): RedirectResponse
    {
        $customer = Customer::query()->findOrFail($customer);
        $customer->delete();

        return back()->with('status', 'Customer deleted successfully.');
    }

    public function createCustomerLineLink(int $customer): RedirectResponse
    {
        $customer = Customer::query()->findOrFail($customer);

        CustomerLineLink::query()
            ->where('customer_id', $customer->id)
            ->whereNull('used_at')
            ->delete();

        $token = $this->generateCustomerLineLinkToken($customer->tenant_id);
        $expiresAt = now()->addDay();

        $customer->lineLinks()->create([
            'tenant_id' => $customer->tenant_id,
            'link_token' => $token,
            'expired_at' => $expiresAt,
        ]);

        $tenant = Tenant::query()->find($customer->tenant_id);
        $linkingUrl = URL::temporarySignedRoute(
            'resident.line.link.create',
            $expiresAt,
            [
                'tenant' => $customer->tenant_id,
                'token' => $token,
            ]
        );

        return back()->with('status_card', [
            'theme' => 'warning',
            'title' => 'LINE link code ready',
            'customer' => $customer->name,
            'code' => $token,
            'instruction' => 'เพิ่มเพื่อน LINE OA ก่อน แล้วกดปุ่มยืนยันห้องพักเพื่อกรอกรหัส '.$token,
            'add_friend_url' => $tenant?->lineAddFriendUrl(),
            'link_url' => $linkingUrl,
            'expires_at' => $expiresAt->format('d/m/Y H:i'),
        ]);
    }

    private function generateCustomerLineLinkToken(int $tenantId): string
    {
        $alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';

        do {
            $token = collect(range(1, 6))
                ->map(fn () => $alphabet[random_int(0, strlen($alphabet) - 1)])
                ->implode('');
        } while (CustomerLineLink::query()
            ->where('tenant_id', $tenantId)
            ->where('link_token', $token)
            ->whereNull('used_at')
            ->exists());

        return $token;
    }

    public function uploadCustomerDocument(Request $request, Customer $customer): RedirectResponse
    {
        $request->validate([
            'document_type' => ['required', 'in:id_card,profile_photo,contract,other'],
            'file'          => ['required', 'file', 'mimes:jpg,jpeg,png,gif,pdf', 'max:5120'],
        ]);

        $file = $request->file('file');
        $tenantId = app(TenantContext::class)->id();
        $path = $file->store(
            'documents/' . $tenantId . '/' . $customer->id,
            'public'
        );

        $customer->documents()->create([
            'tenant_id'     => $tenantId,
            'document_type' => $request->input('document_type'),
            'original_name' => $file->getClientOriginalName(),
            'file_path'     => $path,
        ]);

        return back()->with('status', 'Document uploaded successfully.');
    }

    public function destroyCustomerDocument(Customer $customer, CustomerDocument $document): RedirectResponse
    {
        abort_unless($document->customer_id === $customer->id, 403);
        Storage::disk('public')->delete($document->file_path);
        $document->delete();

        return back()->with('status', 'Document deleted.');
    }

    public function contracts(): View
    {
        return view('dashboard.contracts', [
            'contracts' => Contract::query()->with(['customer', 'room'])->latest()->get(),
            'customers' => Customer::query()->orderBy('name')->get(),
            'rooms' => Room::query()->orderBy('room_number')->get(),
        ]);
    }

    public function storeContract(Request $request): RedirectResponse
    {
        $validated = $this->validateContract($request);

        $contract = Contract::create($validated);
        $this->syncRoomOccupancy($contract->room);

        return back()->with('status', 'Contract saved successfully.');
    }

    public function updateContract(Request $request, int $contract): RedirectResponse
    {
        $contract = Contract::query()->findOrFail($contract);
        $originalRoom = $contract->room;

        $contract->update($this->validateContract($request));
        $contract->refresh();

        if ($originalRoom && (! $contract->room || $originalRoom->isNot($contract->room))) {
            $this->syncRoomOccupancy($originalRoom->fresh());
        }

        $this->syncRoomOccupancy($contract->room);

        return back()->with('status', 'Contract updated successfully.');
    }

    public function destroyContract(int $contract): RedirectResponse
    {
        $contract = Contract::query()->findOrFail($contract);
        $room = $contract->room;

        $contract->delete();
        $this->syncRoomOccupancy($room?->fresh());

        return back()->with('status', 'Contract deleted successfully.');
    }

    public function invoices(): View
    {
        $this->refreshInvoiceStatuses();

        return view('dashboard.invoices', [
            'invoices' => Invoice::query()->with(['customer', 'room', 'payments'])->latest('due_date')->get(),
            'contracts' => Contract::query()
                ->with(['customer', 'room'])
                ->where('status', 'active')
                ->latest('start_date')
                ->latest('id')
                ->get(),
        ]);
    }

    public function storeInvoice(Request $request): RedirectResponse
    {
        $validated = $this->validateInvoice($request);
        $contractId = (int) $validated['contract_id'];

        $contract = Contract::query()->with(['customer', 'room'])->findOrFail($contractId);

        /** @var Customer|null $contractCustomer */
        $contractCustomer = $contract->customer;
        $resolvedRoomId = $contractCustomer?->room_id ?: $contract->room_id;

        $water = (float) ($validated['water_fee'] ?? 0);
        $electricity = (float) ($validated['electricity_fee'] ?? 0);
        $service = (float) ($validated['service_fee'] ?? 0);
        $total = (float) $contract->monthly_rent + $water + $electricity + $service;

        $invoice = Invoice::create([
            'contract_id' => $contract->id,
            'customer_id' => $contract->customer_id,
            'room_id' => $resolvedRoomId,
            'water_fee' => $water,
            'electricity_fee' => $electricity,
            'service_fee' => $service,
            'status' => $validated['status'],
            'due_date' => $validated['due_date'],
            'total_amount' => $total,
        ]);

        $invoice->markAsOverdueIfNecessary();

        $this->sendLineNotification($invoice, 'invoice_created');

        return back()->with('status', 'Invoice issued and LINE notification queued.');
    }

    public function downloadInvoicePdf(int $invoice): Response
    {
        $invoice = Invoice::query()
            ->with(['contract', 'customer', 'room', 'payments'])
            ->findOrFail($invoice);

        $invoice->markAsOverdueIfNecessary();

        return Pdf::loadView('pdf.invoice', [
            'invoice' => $invoice,
        ])->download($invoice->invoice_no.'.pdf');
    }

    public function remindInvoice(int $invoice): RedirectResponse
    {
        $invoice = Invoice::query()->findOrFail($invoice);
        $invoice->markAsOverdueIfNecessary();
        $this->sendLineNotification($invoice->loadMissing(['customer', 'room']), 'reminder_sent');

        return back()->with('status', 'Reminder processed.');
    }

    public function payments(): View
    {
        $this->refreshInvoiceStatuses();

        return view('dashboard.payments', [
            'payments' => Payment::query()->with('invoice.customer')->latest('payment_date')->get(),
            'invoices' => Invoice::query()->with(['customer', 'room'])->latest('due_date')->get(),
        ]);
    }

    public function lineActivity(): View
    {
        $recentWebhookLogs = LineWebhookLog::query()
            ->latest('id')
            ->take(20)
            ->get();

        $recentLineMessages = LineMessage::query()
            ->with('customer')
            ->latest('sent_at')
            ->take(20)
            ->get();

        $recentLineNotifications = NotificationLog::query()
            ->where('channel', 'line')
            ->latest('id')
            ->take(20)
            ->get();

        $linkedResidents = Customer::query()
            ->whereNotNull('line_user_id')
            ->count();

        return view('dashboard.line-activity', [
            'tenant' => app(TenantContext::class)->tenant(),
            'linkedResidents' => $linkedResidents,
            'webhookEventsToday' => LineWebhookLog::query()->whereDate('created_at', today())->count(),
            'outboundMessagesToday' => LineMessage::query()->where('direction', 'outbound')->whereDate('sent_at', today())->count(),
            'failedLineNotifications' => NotificationLog::query()->where('channel', 'line')->where('status', 'failed')->count(),
            'recentWebhookLogs' => $recentWebhookLogs,
            'recentLineMessages' => $recentLineMessages,
            'recentLineNotifications' => $recentLineNotifications,
        ]);
    }

    public function storePayment(Request $request, SlipVerificationService $slipVerificationService): RedirectResponse
    {
        $validated = $this->validatePayment($request);

        if ($request->hasFile('slip')) {
            $tenant = app(TenantContext::class)->tenant();
            $tenantId = $tenant?->id ?? 'shared';
            $validated['slip_path'] = $request->file('slip')->store("slips/{$tenantId}", 'local');
        }

        $payment = Payment::create($validated);
        $verificationOutcome = null;
        $autoApproved = false;

        if ($payment->method === 'slip' && $payment->status === 'pending' && $payment->slip_path) {
            $verificationOutcome = $slipVerificationService->verifyPayment($payment);
            $autoApproved = $this->autoApprovePaymentIfSlipVerified($payment);
        }

        if ($payment->status === 'approved') {
            $payment->invoice?->update(['status' => 'paid']);
        }

        $message = 'Payment recorded successfully.';

        if ($verificationOutcome !== null) {
            $message .= ' SlipOK: '.$verificationOutcome['message'];
        }

        if ($autoApproved) {
            $message .= ' Auto-approved and invoice marked as paid.';
        }

        return back()->with('status', $message);
    }

    public function viewSlip(int $payment): Response
    {
        $payment = Payment::query()->findOrFail($payment);

        if (! $payment->slip_path || ! Storage::disk('local')->exists($payment->slip_path)) {
            abort(404);
        }

        $mime = File::mimeType(Storage::disk('local')->path($payment->slip_path)) ?: 'application/octet-stream';

        return response(Storage::disk('local')->get($payment->slip_path), 200)
            ->header('Content-Type', $mime)
            ->header('Content-Disposition', 'inline; filename="'.basename($payment->slip_path).'"');
    }

    public function recheckPaymentSlip(int $payment, SlipVerificationService $slipVerificationService): RedirectResponse
    {
        $payment = Payment::query()->findOrFail($payment);

        if ($payment->status !== 'pending') {
            return back()->with('error', 'Only pending payments can be rechecked.');
        }

        if ($payment->method !== 'slip' || ! $payment->slip_path) {
            return back()->with('error', 'Only slip-based payments can be rechecked.');
        }

        if ($payment->verification_status !== 'failed') {
            return back()->with('error', 'Only failed SlipOK payments can be rechecked.');
        }

        $verificationOutcome = $slipVerificationService->verifyPayment($payment);
        $autoApproved = $this->autoApprovePaymentIfSlipVerified($payment);

        $message = 'SlipOK recheck completed: '.$verificationOutcome['message'];

        if ($autoApproved) {
            $message .= ' Auto-approved and invoice marked as paid.';
        }

        return back()->with('status', $message);
    }

    public function approvePayment(int $payment): RedirectResponse
    {
        $payment = Payment::query()->findOrFail($payment);

        if ($payment->status !== 'pending') {
            return back()->with('error', 'Only pending payments can be approved.');
        }

        $payment->update([
            'status' => 'approved',
            'receipt_no' => Payment::generateReceiptNo((int) $payment->tenant_id),
        ]);
        $payment->invoice?->update(['status' => 'paid']);

        $notifiablePayment = $payment->fresh();

        if (! $notifiablePayment instanceof Payment) {
            $notifiablePayment = $payment;
        }

        $this->sendPaymentEmailNotification(
            $notifiablePayment->loadMissing(['invoice.customer', 'invoice.room']),
            'payment_approved'
        );

        return back()->with('status', 'Payment approved and invoice marked as paid.');
    }

    public function rejectPayment(int $payment): RedirectResponse
    {
        $payment = Payment::query()->findOrFail($payment);

        if ($payment->status !== 'pending') {
            return back()->with('error', 'Only pending payments can be rejected.');
        }

        $payment->update(['status' => 'rejected']);

        $this->sendPaymentEmailNotification(
            $payment->fresh()->loadMissing(['invoice.customer', 'invoice.room']),
            'payment_rejected'
        );

        return back()->with('status', 'Payment rejected.');
    }

    public function downloadReceiptPdf(int $payment): Response
    {
        $payment = Payment::query()
            ->with(['invoice.contract', 'invoice.customer', 'invoice.room'])
            ->findOrFail($payment);

        return Pdf::loadView('pdf.receipt', [
            'payment' => $payment,
            'invoice' => $payment->invoice,
        ])->download(($payment->receipt_no ?? 'RECEIPT-'.$payment->id).'.pdf');
    }

    public function settings(): View
    {
        return view('dashboard.settings', [
            'tenant' => app(TenantContext::class)->tenant(),
        ]);
    }

    public function updateSettings(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'promptpay_number'          => ['nullable', 'string', 'max:20', 'regex:/^[0-9\-]+$/'],
            'line_channel_id'           => ['nullable', 'string', 'max:100'],
            'line_basic_id'             => ['nullable', 'string', 'max:100'],
            'line_channel_access_token' => ['nullable', 'string', 'max:500'],
            'line_channel_secret'       => ['nullable', 'string', 'max:255'],
            'support_contact_name'      => ['nullable', 'string', 'max:255'],
            'support_contact_phone'     => ['nullable', 'string', 'max:50'],
            'support_line_id'           => ['nullable', 'string', 'max:100'],
        ]);

        $tenant = app(TenantContext::class)->tenant();
        $webhookUrl = route('api.line.webhook');

        $tenant?->update([
            'promptpay_number'          => $validated['promptpay_number'] ?? null,
            'line_channel_id'           => $validated['line_channel_id'] ?? null,
            'line_basic_id'             => $validated['line_basic_id'] ?? null,
            'line_webhook_url'          => $webhookUrl,
            'line_channel_access_token' => $validated['line_channel_access_token'] ?? null,
            'line_channel_secret'       => $validated['line_channel_secret'] ?? null,
            'support_contact_name'      => $validated['support_contact_name'] ?? null,
            'support_contact_phone'     => $validated['support_contact_phone'] ?? null,
            'support_line_id'           => $validated['support_line_id'] ?? null,
        ]);

        return back()->with('status', 'Settings updated.');
    }

    public function syncLineRichMenu(LineService $lineService): RedirectResponse
    {
        $tenant = app(TenantContext::class)->tenant();
        abort_if(! $tenant, 403);

        if (! $tenant->line_channel_access_token || ! $tenant->line_channel_secret) {
            return back()->with('error', 'Please save LINE channel credentials before syncing the rich menu.');
        }

        try {
            $result = $lineService->syncResidentRichMenu($tenant);
        } catch (\Throwable $exception) {
            return back()->with('error', 'LINE rich menu sync failed: '.$exception->getMessage());
        }

        if (($result['status'] ?? 'failed') !== 'sent') {
            return back()->with('error', 'LINE rich menu sync failed.');
        }

        $tenant->update([
            'line_rich_menu_id' => $result['richMenuId'] ?? null,
            'line_webhook_url' => route('api.line.webhook'),
        ]);

        return back()->with('status', 'LINE rich menu synced successfully.');
    }

    public function promptpayQr(Invoice $invoice): \Illuminate\Http\Response
    {
        $tenant = $invoice->tenant;
        abort_if(! $tenant?->promptpay_number, 404, 'PromptPay not configured for this tenant.');

        $svg = app(PromptPayService::class)
            ->generateSvg($tenant->promptpay_number, (float) $invoice->total_amount);

        return response($svg, 200, ['Content-Type' => 'image/svg+xml', 'Cache-Control' => 'no-store']);
    }

    public function admin(): View
    {
        return view('dashboard.admin', [
            'tenantCount' => Tenant::count(),
            'activeUsers' => User::count(),
            'saasRevenue' => Payment::withoutGlobalScopes()->where('status', 'approved')->sum('amount'),
            'failedJobs' => 0,
            'notificationLogs' => NotificationLog::query()->latest()->take(12)->get(),
            'tenants' => Tenant::query()->orderBy('name')->get(),
        ]);
    }

    public function suspendTenant(Tenant $tenant): RedirectResponse
    {
        $tenant->update(['status' => 'suspended']);

        return back()->with('success', "Tenant '{$tenant->name}' has been suspended.");
    }

    public function unsuspendTenant(Tenant $tenant): RedirectResponse
    {
        $tenant->update(['status' => 'active']);

        return back()->with('success', "Tenant '{$tenant->name}' has been reactivated.");
    }

    public function residentInvoice(Invoice $invoice): View
    {
        abort_if($invoice->tenant?->status === 'suspended', 403, 'This service is currently unavailable.');

        $invoice->markAsOverdueIfNecessary();

        $invoice->loadMissing(['customer', 'room', 'payments', 'tenant']);

        $promptpayQr = null;
        $promptpayNumber = $invoice->tenant?->promptpay_number;
        if ($promptpayNumber && ! in_array($invoice->status, ['paid', 'cancelled'])) {
            $promptpayQr = app(PromptPayService::class)
                ->generateSvg($promptpayNumber, (float) $invoice->total_amount);
        }

        return view('resident.invoice', [
            'invoice'     => $invoice,
            'promptpayQr' => $promptpayQr,
        ]);
    }

    public function residentDownloadReceipt(Invoice $invoice, Payment $payment): Response
    {
        if ((int) $payment->invoice_id !== $invoice->id || $payment->status !== 'approved') {
            abort(404);
        }

        $payment->load(['invoice.contract', 'invoice.customer', 'invoice.room']);

        return Pdf::loadView('pdf.receipt', [
            'payment' => $payment,
            'invoice' => $payment->invoice,
        ])->download(($payment->receipt_no ?? 'RECEIPT-'.$payment->id).'.pdf');
    }

    public function residentPaySlip(Request $request, Invoice $invoice, SlipVerificationService $slipVerificationService): RedirectResponse
    {
        abort_if($invoice->tenant?->status === 'suspended', 403, 'This service is currently unavailable.');

        $invoice->markAsOverdueIfNecessary();

        if (in_array($invoice->status, ['paid', 'cancelled'], true)) {
            return back()->with('error', 'This invoice has already been settled.');
        }

        $request->validate([
            'slip' => ['required', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'],
            'amount' => ['required', 'numeric', 'min:0'],
            'payment_date' => ['required', 'date'],
        ]);

        $slipPath = $request->file('slip')->store("slips/{$invoice->tenant_id}/resident", 'local');

        $payment = Payment::create([
            'tenant_id' => $invoice->tenant_id,
            'invoice_id' => $invoice->id,
            'amount' => $request->input('amount'),
            'payment_date' => $request->input('payment_date'),
            'method' => 'slip',
            'status' => 'pending',
            'slip_path' => $slipPath,
            'notes' => 'Submitted by resident via portal',
        ]);

        $verificationOutcome = $slipVerificationService->verifyPayment($payment);
        $autoApproved = $this->autoApprovePaymentIfSlipVerified($payment);

        $message = 'Slip submitted successfully. SlipOK: '.$verificationOutcome['message'];

        if ($autoApproved) {
            $message .= ' Payment auto-approved and invoice marked as paid.';
        }

        return back()->with('status', $message);
    }

    protected function autoApprovePaymentIfSlipVerified(Payment $payment): bool
    {
        $payment->refresh();

        if ($payment->status !== 'pending' || $payment->verification_status !== 'verified') {
            return false;
        }

        $payment->update([
            'status' => 'approved',
            'receipt_no' => $payment->receipt_no ?: Payment::generateReceiptNo((int) $payment->tenant_id),
        ]);

        $payment->invoice?->update(['status' => 'paid']);

        $this->sendPaymentEmailNotification(
            $payment->fresh()->loadMissing(['invoice.customer', 'invoice.room']),
            'payment_approved'
        );

        return true;
    }

    protected function sendPaymentEmailNotification(Payment $payment, string $event): void
    {
        /** @var \App\Models\Customer|null $customer */
        $customer = $payment->invoice?->customer;
        $invoiceNo = $payment->invoice?->invoice_no ?? '-';
        $email = $customer?->email;

        $status = 'skipped';
        if ($email) {
            try {
                Mail::to($email)->send(new PaymentNotification($payment, $event));
                $status = 'sent';
            } catch (\Throwable) {
                $status = 'failed';
            }
        }

        NotificationLog::create([
            'tenant_id' => $payment->tenant_id,
            'channel' => 'email',
            'event' => $event,
            'target' => $customer?->name,
            'message' => "Payment {$event} for invoice {$invoiceNo}, amount ".number_format((float) $payment->amount, 2).' THB',
            'status' => $status,
            'payload' => [
                'payment_id' => $payment->id,
                'invoice_no' => $invoiceNo,
                'recipient' => $email,
            ],
        ]);
    }

    protected function sendLineNotification(Invoice $invoice, string $event): void
    {
        $invoice->loadMissing(['customer', 'room']);
        /** @var \App\Models\Customer|null $customer */
        $customer = $invoice->customer;

        $invoiceUrl = $invoice->signedResidentUrl();

        $message = sprintf(
            'Invoice %s for room %s is %s. Total: %s THB, due: %s. View: %s',
            $invoice->invoice_no,
            $invoice->room?->room_number ?? '-',
            $event,
            number_format((float) $invoice->total_amount, 2),
            optional($invoice->due_date)->format('d/m/Y'),
            $invoiceUrl
        );

        /** @var \App\Models\Tenant|null $invoiceTenant */
        $invoiceTenant = $invoice->tenant ?? app(TenantContext::class)->tenant();

        if (! $invoiceTenant) {
            return;
        }

        SendLineMessageJob::dispatch(
            $invoiceTenant->id,
            $event,
            $customer?->line_user_id,
            $message,
            $customer?->name,
            $customer?->id,
            ['invoice_id' => $invoice->id]
        );
    }

    protected function validateRoom(Request $request): array
    {
        return $request->validate([
            'building' => ['required', 'string', 'max:100'],
            'room_number' => ['required', 'string', 'max:50'],
            'floor' => ['required', 'integer', 'min:1'],
            'room_type' => ['required', 'string', 'max:100'],
            'price' => ['required', 'numeric', 'min:0'],
            'status' => ['required', 'in:vacant,occupied,maintenance'],
        ]);
    }

    protected function validateCustomer(Request $request): array
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255'],
            'line_id' => ['nullable', 'string', 'max:100'],
            'id_card' => ['nullable', 'string', 'max:100'],
            'room_id' => ['nullable', 'integer'],
        ]);

        if (! empty($validated['room_id'])) {
            Room::query()->findOrFail((int) $validated['room_id']);
        } else {
            $validated['room_id'] = null;
        }

        return $validated;
    }

    protected function validateContract(Request $request): array
    {
        $validated = $request->validate([
            'customer_id' => ['required', 'integer'],
            'room_id' => ['required', 'integer'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after:start_date'],
            'deposit' => ['required', 'numeric', 'min:0'],
            'monthly_rent' => ['nullable', 'numeric', 'min:0'],
            'status' => ['required', 'in:active,expired,cancelled'],
        ]);

        $customer = Customer::query()->findOrFail((int) $validated['customer_id']);
        $room = Room::query()->findOrFail((int) $validated['room_id']);

        $validated['customer_id'] = $customer->id;
        $validated['room_id'] = $room->id;
        $validated['monthly_rent'] = $validated['monthly_rent'] ?? $room->price;

        return $validated;
    }

    protected function validateInvoice(Request $request): array
    {
        $validated = $request->validate([
            'contract_id' => ['required', 'integer'],
            'water_fee' => ['nullable', 'numeric', 'min:0'],
            'electricity_fee' => ['nullable', 'numeric', 'min:0'],
            'service_fee' => ['nullable', 'numeric', 'min:0'],
            'status' => ['required', 'in:draft,sent,paid,overdue'],
            'due_date' => ['required', 'date'],
        ]);

        $contract = Contract::query()->findOrFail((int) $validated['contract_id']);

        if ($contract->status !== 'active') {
            throw ValidationException::withMessages([
                'contract_id' => 'Only active contracts can be invoiced.',
            ]);
        }

        $validated['contract_id'] = $contract->id;

        return $validated;
    }

    protected function validatePayment(Request $request): array
    {
        $validated = $request->validate([
            'invoice_id' => ['required', 'integer'],
            'amount' => ['required', 'numeric', 'min:0'],
            'payment_date' => ['required', 'date'],
            'method' => ['required', 'in:manual,slip,online'],
            'status' => ['required', 'in:pending,approved,rejected'],
            'notes' => ['nullable', 'string'],
            'slip' => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'],
        ]);

        $invoice = Invoice::query()->findOrFail((int) $validated['invoice_id']);
        $validated['invoice_id'] = $invoice->id;

        return $validated;
    }

    protected function syncRoomOccupancy(?Room $room): void
    {
        if (! $room) {
            return;
        }

        $hasActiveContract = Contract::query()
            ->where('room_id', $room->id)
            ->where('status', 'active')
            ->exists();

        $room->update([
            'status' => $hasActiveContract ? 'occupied' : 'vacant',
        ]);
    }

    protected function refreshInvoiceStatuses(): void
    {
        Invoice::markDueInvoicesAsOverdue();
    }

    protected function buildRevenueTrend()
    {
        $startMonth = now()->startOfMonth()->subMonths(5);

        $payments = Payment::query()
            ->where('status', 'approved')
            ->whereDate('payment_date', '>=', $startMonth)
            ->get(['amount', 'payment_date']);

        return collect(range(0, 5))->map(function (int $offset) use ($payments, $startMonth) {
            $month = $startMonth->copy()->addMonths($offset);
            $key = $month->format('Y-m');
            $total = (float) $payments
                ->filter(fn (Payment $payment) => $payment->payment_date?->format('Y-m') === $key)
                ->sum('amount');

            return [
                'month' => $key,
                'label' => $month->format('M Y'),
                'total' => round($total, 2),
            ];
        });
    }
}
