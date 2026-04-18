<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Plan;
use App\Models\SaasInvoice;
use App\Models\PlatformSetting;
use App\Models\Tenant;
use App\Support\TenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Stripe\Exception\ApiErrorException;
use Stripe\StripeClient;

final class BillingController extends Controller
{
    public function index(): View
    {
        $tenant = app(TenantContext::class)->tenant();
        $tenant?->loadMissing('subscriptionPlan');
        $platformSetting = PlatformSetting::current();

        return view('dashboard.billing', [
            'tenant' => $tenant,
            'currentPlan' => $tenant?->resolvedPlan(),
            'billingPortalAvailable' => $tenant !== null
                && $platformSetting->hasStripeCredentials()
                && filled($tenant->stripe_customer_id),
            'invoices' => $tenant
                ? SaasInvoice::query()->withoutGlobalScopes()->where('tenant_id', $tenant->id)->latest('issued_at')->take(50)->get()
                : collect(),
        ]);
    }

    public function checkout(Request $request): RedirectResponse
    {
        $tenant = app(TenantContext::class)->tenant();

        abort_unless($tenant !== null, 404, 'Tenant not found.');

        $validated = $request->validate([
            'plan_id' => ['required', 'integer', 'exists:plans,id'],
        ]);

        $plan = Plan::query()->findOrFail((int) $validated['plan_id']);
        abort_if(blank($plan->stripe_price_id), 422, 'Selected plan is not available for Stripe checkout yet.');
        if (! $this->isStripePriceId((string) $plan->stripe_price_id)) {
            throw ValidationException::withMessages([
                'plan_id' => 'Selected package is misconfigured. Stripe Price ID must start with price_. Please contact Platform Admin.',
            ]);
        }

        $platformSetting = PlatformSetting::current();
        abort_if(! $platformSetting->hasStripeCredentials(), 422, 'Stripe is not configured by Platform Admin.');

        $stripe = $this->stripeClient((string) $platformSetting->stripe_secret_key);

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

        try {
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

        } catch (ApiErrorException $e) {
            Log::warning('Stripe checkout session creation failed', [
                'tenant_id' => $tenant->id,
                'plan_id' => $plan->id,
                'stripe_price_id' => $plan->stripe_price_id,
                'error' => $e->getMessage(),
            ]);

            throw ValidationException::withMessages([
                'plan_id' => 'Unable to start Stripe checkout for this package. Please verify the Stripe Price ID in Package Management.',
            ]);
        }

        return redirect()->away($session->url);
    }

    public function success(Request $request): View|RedirectResponse
    {
        $tenant = app(TenantContext::class)->tenant();
        abort_unless($tenant !== null, 404);

        $sessionId = (string) $request->query('session_id', '');
        abort_if($sessionId === '', 422, 'Missing session_id');

        $platformSetting = PlatformSetting::current();
        abort_if(! $platformSetting->hasStripeCredentials(), 422, 'Stripe is not configured by Platform Admin.');

        $stripe = $this->stripeClient((string) $platformSetting->stripe_secret_key);

        try {
            $session = $stripe->checkout->sessions->retrieve($sessionId, [
                'expand' => ['subscription', 'customer'],
            ]);

            $this->syncTenantFromCheckoutSession($tenant, $session);
            $tenant->refresh();
        } catch (\Throwable $e) {
            Log::warning('Stripe checkout success sync failed', [
                'tenant_id' => $tenant->id,
                'session_id' => $sessionId,
                'error' => $e->getMessage(),
            ]);
        }

        if ($tenant->hasPortalAccess()) {
            return redirect()
                ->route('app.dashboard')
                ->with('success', 'Subscription activated successfully.');
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

        abort_unless($tenant !== null, 404, 'Tenant not found.');

        $platformSetting = PlatformSetting::current();
        abort_if(! $platformSetting->hasStripeCredentials(), 422, 'Stripe is not configured by Platform Admin.');
        abort_if(blank($tenant->stripe_customer_id), 422, 'Stripe customer not found for this tenant yet.');

        $stripe = $this->stripeClient((string) $platformSetting->stripe_secret_key);

        $session = $stripe->billingPortal->sessions->create([
            'customer' => $tenant->stripe_customer_id,
            'return_url' => url('/app/billing'),
        ]);

        return redirect()->away($session->url);
    }

    private function stripeClient(string $secretKey): StripeClient
    {
        if (! class_exists(StripeClient::class)) {
            Log::error('Stripe SDK is not installed on this server.');

            abort(503, 'Stripe billing is temporarily unavailable. Please run composer install for stripe/stripe-php on this server.');
        }

        return new StripeClient($secretKey);
    }

    private function isStripePriceId(string $value): bool
    {
        return preg_match('/^price_[A-Za-z0-9_]+$/', $value) === 1;
    }

    private function syncTenantFromCheckoutSession(Tenant $tenant, object $session): void
    {
        $subscription = $this->objectValue($session, 'subscription');
        $metadata = $this->arrayValue($this->objectValue($session, 'metadata'));
        $customerId = $this->stripeIdentifier($this->objectValue($session, 'customer'));
        $subscriptionId = $this->stripeIdentifier($this->objectValue($subscription, 'id'));
        $subscriptionStatus = $this->stringOrNull($this->objectValue($subscription, 'status'));
        $currentPeriodEnd = $this->intOrNull($this->objectValue($subscription, 'current_period_end'));
        $paymentStatus = $this->stringOrNull($this->objectValue($session, 'payment_status'));
        $checkoutStatus = $this->stringOrNull($this->objectValue($session, 'status'));
        $planId = $this->intOrNull($metadata['plan_id'] ?? null);

        $updates = [
            'stripe_customer_id' => $customerId ?? $tenant->stripe_customer_id,
            'stripe_subscription_id' => $subscriptionId ?? $tenant->stripe_subscription_id,
            'subscription_current_period_end' => $currentPeriodEnd !== null ? now()->setTimestamp($currentPeriodEnd) : $tenant->subscription_current_period_end,
        ];

        if ($planId !== null) {
            $plan = Plan::query()->find($planId);

            if ($plan !== null) {
                $updates['plan_id'] = $plan->id;
                $updates['plan'] = $plan->slug;
            }
        }

        $activationStatuses = ['active', 'trialing'];
        $shouldActivate = in_array($subscriptionStatus, $activationStatuses, true)
            || $paymentStatus === 'paid'
            || $checkoutStatus === 'complete';

        $updates['subscription_status'] = $subscriptionStatus
            ?? ($shouldActivate ? 'active' : $tenant->subscription_status);

        if ($shouldActivate) {
            $updates['status'] = 'active';
        }

        $tenant->update($updates);
    }

    private function objectValue(mixed $value, string $property): mixed
    {
        if (is_array($value)) {
            return $value[$property] ?? null;
        }

        if (! is_object($value)) {
            return null;
        }

        if (property_exists($value, $property) || isset($value->{$property})) {
            return $value->{$property};
        }

        if ($value instanceof \ArrayAccess && $value->offsetExists($property)) {
            return $value[$property];
        }

        return null;
    }

    /**
     * @return array<string, mixed>
     */
    private function arrayValue(mixed $value): array
    {
        if (is_object($value) && $value instanceof \JsonSerializable) {
            $value = json_decode(json_encode($value, JSON_THROW_ON_ERROR), true, 512, JSON_THROW_ON_ERROR);
        }

        return is_array($value) ? $value : [];
    }

    private function stringOrNull(mixed $value): ?string
    {
        return is_string($value) && $value !== '' ? $value : null;
    }

    private function intOrNull(mixed $value): ?int
    {
        if (is_int($value)) {
            return $value;
        }

        return is_string($value) && $value !== '' && is_numeric($value)
            ? (int) $value
            : null;
    }

    private function stripeIdentifier(mixed $value): ?string
    {
        if (is_string($value) && $value !== '') {
            return $value;
        }

        $identifier = $this->objectValue($value, 'id');

        if (is_string($identifier) && $identifier !== '') {
            return $identifier;
        }

        return null;
    }
}
