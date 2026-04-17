<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Plan;
use App\Models\SaasInvoice;
use App\Models\PlatformSetting;
use App\Support\TenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Stripe\StripeClient;

final class BillingController extends Controller
{
    public function index(): View
    {
        $tenant = app(TenantContext::class)->tenant();

        return view('dashboard.billing', [
            'tenant' => $tenant,
            'invoices' => $tenant
                ? SaasInvoice::query()->withoutGlobalScopes()->where('tenant_id', $tenant->id)->latest('issued_at')->take(50)->get()
                : collect(),
        ]);
    }

    public function checkout(Request $request): RedirectResponse
    {
        $tenant = app(TenantContext::class)->tenant();

        abort_unless($tenant, 404, 'Tenant not found.');

        $validated = $request->validate([
            'plan_id' => ['required', 'integer', 'exists:plans,id'],
        ]);

        $plan = Plan::query()->findOrFail((int) $validated['plan_id']);
        abort_if(blank($plan->stripe_price_id), 422, 'Selected plan is not available for Stripe checkout yet.');

        $platformSetting = PlatformSetting::current();
        abort_if(! $platformSetting->hasStripeCredentials(), 422, 'Stripe is not configured by Platform Admin.');

        $stripe = new StripeClient((string) $platformSetting->stripe_secret_key);

        $customerId = $tenant->stripe_customer_id;
        if (blank($customerId)) {
            $customer = $stripe->customers->create([
                'name' => $tenant->name,
                'metadata' => [
                    'tenant_id' => (string) $tenant->id,
                ],
            ]);

            $customerId = $customer->id;
            $tenant->update(['stripe_customer_id' => $customerId]);
        }

        $successUrl = url('/app/billing/success?session_id={CHECKOUT_SESSION_ID}');
        $cancelUrl = url('/app/billing/cancel');

        $session = $stripe->checkout->sessions->create([
            'mode' => 'subscription',
            'customer' => $customerId,
            'line_items' => [
                [
                    'price' => $plan->stripe_price_id,
                    'quantity' => 1,
                ],
            ],
            'allow_promotion_codes' => true,
            'success_url' => $successUrl,
            'cancel_url' => $cancelUrl,
            'metadata' => [
                'tenant_id' => (string) $tenant->id,
                'plan_id' => (string) $plan->id,
            ],
            'subscription_data' => [
                'metadata' => [
                    'tenant_id' => (string) $tenant->id,
                    'plan_id' => (string) $plan->id,
                ],
            ],
        ]);

        return redirect()->away($session->url);
    }

    public function success(Request $request): View
    {
        $tenant = app(TenantContext::class)->tenant();
        abort_unless($tenant, 404);

        $sessionId = (string) $request->query('session_id', '');
        abort_if($sessionId === '', 422, 'Missing session_id');

        $platformSetting = PlatformSetting::current();
        abort_if(! $platformSetting->hasStripeCredentials(), 422, 'Stripe is not configured by Platform Admin.');

        $stripe = new StripeClient((string) $platformSetting->stripe_secret_key);

        try {
            $session = $stripe->checkout->sessions->retrieve($sessionId, [
                'expand' => ['subscription', 'customer'],
            ]);

            $subscription = $session->subscription;
            $subscriptionId = is_object($subscription) && property_exists($subscription, 'id') ? $subscription->id : null;
            $status = is_object($subscription) && property_exists($subscription, 'status') ? $subscription->status : null;
            $currentPeriodEnd = is_object($subscription) && property_exists($subscription, 'current_period_end')
                ? (int) $subscription->current_period_end
                : null;

            $tenant->update([
                'stripe_subscription_id' => $subscriptionId,
                'subscription_status' => $status,
                'subscription_current_period_end' => $currentPeriodEnd ? now()->setTimestamp($currentPeriodEnd) : null,
            ]);
        } catch (\Throwable $e) {
            Log::warning('Stripe checkout success sync failed', [
                'tenant_id' => $tenant->id,
                'session_id' => $sessionId,
                'error' => $e->getMessage(),
            ]);
        }

        return view('dashboard.billing-success', [
            'tenant' => $tenant,
        ]);
    }

    public function cancel(): View
    {
        $tenant = app(TenantContext::class)->tenant();

        return view('dashboard.billing-cancel', [
            'tenant' => $tenant,
        ]);
    }

    public function portal(): RedirectResponse
    {
        $tenant = app(TenantContext::class)->tenant();

        abort_unless($tenant, 404, 'Tenant not found.');

        $platformSetting = PlatformSetting::current();
        abort_if(! $platformSetting->hasStripeCredentials(), 422, 'Stripe is not configured by Platform Admin.');
        abort_if(blank($tenant->stripe_customer_id), 422, 'Stripe customer not found for this tenant yet.');

        $stripe = new StripeClient((string) $platformSetting->stripe_secret_key);

        $session = $stripe->billingPortal->sessions->create([
            'customer' => $tenant->stripe_customer_id,
            'return_url' => url('/app/billing'),
        ]);

        return redirect()->away($session->url);
    }
}
