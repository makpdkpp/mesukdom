<?php

namespace App\Http\Controllers;

use App\Models\Contract;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\NotificationLog;
use App\Models\Payment;
use App\Models\Room;
use App\Models\Tenant;
use App\Models\User;
use App\Support\TenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function dashboard(): View
    {
        $stats = [
            'rooms_total' => Room::count(),
            'rooms_vacant' => Room::where('status', 'vacant')->count(),
            'rooms_occupied' => Room::where('status', 'occupied')->count(),
            'monthly_revenue' => Payment::where('status', 'approved')->whereMonth('payment_date', now()->month)->sum('amount'),
            'overdue_invoices' => Invoice::where('status', 'overdue')->count(),
            'pending_payments' => Payment::where('status', 'pending')->count(),
        ];

        return view('dashboard.index', [
            'tenant' => app(TenantContext::class)->tenant(),
            'stats' => $stats,
            'rooms' => Room::query()->orderBy('room_number')->get(),
            'recentInvoices' => Invoice::query()->with(['customer', 'room'])->latest('due_date')->take(5)->get(),
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
        Room::create($request->validate([
            'room_number' => ['required', 'string', 'max:50'],
            'floor' => ['required', 'integer', 'min:1'],
            'room_type' => ['required', 'string', 'max:100'],
            'price' => ['required', 'numeric', 'min:0'],
            'status' => ['required', 'in:vacant,occupied,maintenance'],
        ]));

        return back()->with('status', 'Room saved successfully.');
    }

    public function customers(): View
    {
        return view('dashboard.customers', [
            'customers' => Customer::query()->with('room')->orderBy('name')->get(),
            'rooms' => Room::query()->orderBy('room_number')->get(),
        ]);
    }

    public function storeCustomer(Request $request): RedirectResponse
    {
        Customer::create($request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255'],
            'line_id' => ['nullable', 'string', 'max:100'],
            'id_card' => ['nullable', 'string', 'max:100'],
            'room_id' => ['nullable', 'exists:rooms,id'],
        ]));

        return back()->with('status', 'Customer saved successfully.');
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
        $validated = $request->validate([
            'customer_id' => ['required', 'exists:customers,id'],
            'room_id' => ['required', 'exists:rooms,id'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after:start_date'],
            'deposit' => ['required', 'numeric', 'min:0'],
            'monthly_rent' => ['nullable', 'numeric', 'min:0'],
            'status' => ['required', 'in:active,expired,cancelled'],
        ]);

        $room = Room::query()->findOrFail($validated['room_id']);
        $validated['monthly_rent'] = $validated['monthly_rent'] ?? $room->price;

        Contract::create($validated);
        $room->update(['status' => 'occupied']);

        return back()->with('status', 'Contract saved successfully.');
    }

    public function invoices(): View
    {
        return view('dashboard.invoices', [
            'invoices' => Invoice::query()->with(['customer', 'room', 'payments'])->latest('due_date')->get(),
            'contracts' => Contract::query()->with(['customer', 'room'])->latest()->get(),
        ]);
    }

    public function storeInvoice(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'contract_id' => ['required', 'exists:contracts,id'],
            'water_fee' => ['nullable', 'numeric', 'min:0'],
            'electricity_fee' => ['nullable', 'numeric', 'min:0'],
            'service_fee' => ['nullable', 'numeric', 'min:0'],
            'status' => ['required', 'in:draft,sent,paid,overdue'],
            'due_date' => ['required', 'date'],
        ]);

        $contract = Contract::query()->with(['customer', 'room'])->findOrFail($validated['contract_id']);

        $water = (float) ($validated['water_fee'] ?? 0);
        $electricity = (float) ($validated['electricity_fee'] ?? 0);
        $service = (float) ($validated['service_fee'] ?? 0);
        $total = (float) $contract->monthly_rent + $water + $electricity + $service;

        $invoice = Invoice::create([
            'contract_id' => $contract->id,
            'customer_id' => $contract->customer_id,
            'room_id' => $contract->room_id,
            'water_fee' => $water,
            'electricity_fee' => $electricity,
            'service_fee' => $service,
            'status' => $validated['status'],
            'due_date' => $validated['due_date'],
            'total_amount' => $total,
        ]);

        $this->sendLineNotification($invoice, 'invoice_created');

        return back()->with('status', 'Invoice issued and LINE notification queued.');
    }

    public function remindInvoice(Invoice $invoice): RedirectResponse
    {
        $this->sendLineNotification($invoice->loadMissing(['customer', 'room']), 'reminder_sent');

        return back()->with('status', 'Reminder processed.');
    }

    public function payments(): View
    {
        return view('dashboard.payments', [
            'payments' => Payment::query()->with('invoice.customer')->latest('payment_date')->get(),
            'invoices' => Invoice::query()->with(['customer', 'room'])->latest('due_date')->get(),
        ]);
    }

    public function storePayment(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'invoice_id' => ['required', 'exists:invoices,id'],
            'amount' => ['required', 'numeric', 'min:0'],
            'payment_date' => ['required', 'date'],
            'method' => ['required', 'in:manual,slip,online'],
            'status' => ['required', 'in:pending,approved,rejected'],
            'notes' => ['nullable', 'string'],
        ]);

        $payment = Payment::create($validated);

        if ($payment->status === 'approved') {
            $payment->invoice?->update(['status' => 'paid']);
        }

        return back()->with('status', 'Payment recorded successfully.');
    }

    public function admin(): View
    {
        return view('dashboard.admin', [
            'tenantCount' => Tenant::count(),
            'activeUsers' => User::count(),
            'saasRevenue' => Payment::query()->where('status', 'approved')->sum('amount'),
            'failedJobs' => 0,
            'notificationLogs' => NotificationLog::query()->latest()->take(12)->get(),
        ]);
    }

    public function residentInvoice(Invoice $invoice): View
    {
        return view('resident.invoice', [
            'invoice' => $invoice->load(['customer', 'room', 'payments']),
        ]);
    }

    protected function sendLineNotification(Invoice $invoice, string $event): void
    {
        $invoice->loadMissing(['customer', 'room']);

        $message = sprintf(
            'Invoice %s for room %s is %s. Total: %s THB, due: %s',
            $invoice->invoice_no,
            $invoice->room?->room_number ?? '-',
            $event,
            number_format((float) $invoice->total_amount, 2),
            optional($invoice->due_date)->format('d/m/Y')
        );

        $status = 'queued';
        $payload = ['invoice_id' => $invoice->id, 'event' => $event];
        $lineToken = config('services.line.channel_access_token');
        $lineUserId = $invoice->customer?->line_user_id;

        if ($lineToken && $lineUserId) {
            $response = Http::withToken($lineToken)
                ->post('https://api.line.me/v2/bot/message/push', [
                    'to' => $lineUserId,
                    'messages' => [
                        [
                            'type' => 'text',
                            'text' => $message,
                        ],
                    ],
                ]);

            $status = $response->successful() ? 'sent' : 'failed';
            $payload['response'] = $response->json() ?: $response->body();
        }

        NotificationLog::create([
            'tenant_id' => $invoice->tenant_id,
            'channel' => 'line',
            'event' => $event,
            'target' => $invoice->customer?->name,
            'message' => $message,
            'status' => $status,
            'payload' => $payload,
        ]);
    }
}
