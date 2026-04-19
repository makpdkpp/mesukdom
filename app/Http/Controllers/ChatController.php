<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Jobs\SendLineMessageJob;
use App\Models\Customer;
use App\Models\LineMessage;
use App\Support\TenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

final class ChatController extends Controller
{
    public function index(Request $request): View
    {
        $selectedCustomerId = (int) $request->query('customer_id', 0);

        $linkedCustomers = Customer::query()
            ->with('room')
            ->whereNotNull('line_user_id')
            ->orderBy('name')
            ->get();

        $selectedCustomer = $linkedCustomers->firstWhere('id', $selectedCustomerId);

        $messages = LineMessage::query()
            ->with('customer')
            ->when($selectedCustomer !== null, fn ($query) => $query->where('customer_id', $selectedCustomer->id))
            ->latest('sent_at')
            ->latest('id')
            ->take(50)
            ->get()
            ->reverse()
            ->values();

        return view('dashboard.chat', [
            'linkedCustomers' => $linkedCustomers,
            'selectedCustomer' => $selectedCustomer,
            'messages' => $messages,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $tenant = app(TenantContext::class)->tenant();
        abort_if(! $tenant, 403);

        $validated = $request->validate([
            'customer_id' => ['required', 'integer', Rule::exists('customers', 'id')],
            'message' => ['required', 'string', 'max:2000'],
        ]);

        $customer = Customer::query()
            ->where('id', (int) $validated['customer_id'])
            ->firstOrFail();

        if (! $customer->line_user_id) {
            return back()->with('error', 'Selected resident is not linked to LINE yet.');
        }

        $tenantAccessToken = is_string($tenant->line_channel_access_token)
            ? trim($tenant->line_channel_access_token)
            : '';
        $fallbackAccessToken = config('services.line.channel_access_token');
        $fallbackToken = is_string($fallbackAccessToken)
            ? trim($fallbackAccessToken)
            : '';

        if ($tenantAccessToken === '' && $fallbackToken === '') {
            return back()->with('error', 'LINE channel access token is not configured. Please update LINE settings before sending chat.');
        }

        SendLineMessageJob::dispatchSync(
            $tenant->id,
            'chat_manual_sent',
            $customer->line_user_id,
            trim((string) $validated['message']),
            $customer->name,
            $customer->id,
            ['source' => 'app_chat']
        );

        return redirect()
            ->route('app.chat', ['customer_id' => $customer->id])
            ->with('status', 'Message sent to LINE successfully.');
    }
}
