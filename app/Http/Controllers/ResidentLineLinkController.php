<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\CustomerLineLink;
use App\Models\Tenant;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

final class ResidentLineLinkController extends Controller
{
    public function create(Request $request, Tenant $tenant): View
    {
        return view('resident.line-link', [
            'tenant' => $tenant,
            'lineUserId' => (string) $request->query('line_user_id', ''),
            'prefilledToken' => strtoupper((string) $request->query('token', '')),
            'linkedCustomer' => null,
        ]);
    }

    public function store(Request $request, Tenant $tenant): View|RedirectResponse
    {
        $validated = $request->validate([
            'link_token' => ['required', 'string', 'size:6', 'regex:/^[A-Z0-9]+$/'],
        ]);

        $lineUserId = (string) $request->query('line_user_id', '');

        if ($lineUserId === '') {
            return back()->withErrors(['link_token' => 'Missing LINE user identity for this linking session.']);
        }

        $token = strtoupper($validated['link_token']);

        $lineLink = CustomerLineLink::query()
            ->where('tenant_id', $tenant->id)
            ->where('link_token', $token)
            ->whereNull('used_at')
            ->where('expired_at', '>', now())
            ->first();

        if (! $lineLink) {
            return back()->withInput()->withErrors(['link_token' => 'Link code is invalid or has expired.']);
        }

        /** @var Customer $customer */
        $customer = DB::transaction(function () use ($lineLink, $lineUserId, $tenant): Customer {
            Customer::query()
                ->where('tenant_id', $tenant->id)
                ->where('line_user_id', $lineUserId)
                ->update([
                    'line_user_id' => null,
                    'line_linked_at' => null,
                ]);

            $customer = $lineLink->customer()->with('room')->firstOrFail();
            $customer->update([
                'line_user_id' => $lineUserId,
                'line_linked_at' => now(),
            ]);

            $lineLink->update(['used_at' => now()]);

            return $customer->fresh(['room']);
        });

        return view('resident.line-link', [
            'tenant' => $tenant,
            'lineUserId' => $lineUserId,
            'prefilledToken' => $token,
            'linkedCustomer' => $customer,
        ]);
    }
}
