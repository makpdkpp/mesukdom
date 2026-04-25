<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Plan;
use App\Models\SaasInvoice;
use App\Models\PlatformSetting;
use App\Models\Tenant;
use App\Services\StripeInvoiceSyncService;
use App\Support\TenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Stripe\Exception\ApiErrorException;
use Stripe\StripeClient;

final class BillingController extends Controller
{
    public function __construct(private readonly StripeInvoiceSyncService $stripeInvoiceSyncService)
    {
    }

    public function index(Request $request): View
    {
        $tenant = app(TenantContext::class)->tenant();
        $tenant?->loadMissing('subscriptionPlan');
        $platformSetting = PlatformSetting::current();
        $stripeReady = $platformSetting->hasStripeCredentials();
        $invoiceSyncMetadataAvailable = $this->supportsTenantInvoiceSyncMetadata();
        $invoiceCount = $tenant
            ? SaasInvoice::query()->withoutGlobalScopes()->where('tenant_id', $tenant->id)->count()
            : 0;
        $currentPlan = $tenant?->resolvedPlan();

        if ($currentPlan === null && $tenant?->plan_id) {
            $currentPlan = Plan::query()->find($tenant->plan_id);
        }

        $packagePlans = Plan::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        if ($currentPlan !== null && ! $packagePlans->contains('id', $currentPlan->id)) {
            $packagePlans->prepend($currentPlan);
        }

        $selectedPlanId = (int) old('plan_id', (string) $request->query('plan_id', $currentPlan?->id ?? '0'));
        $selectedPlan = $selectedPlanId > 0
            ? $packagePlans->firstWhere('id', $selectedPlanId)
            : null;

        if ($selectedPlan === null) {
            $selectedPlan = $currentPlan ?? $packagePlans->first();
        }

        $packageCheckoutConfigs = $packagePlans
            ->mapWithKeys(fn (Plan $plan): array => [
                (string) $plan->id => $this->packageCheckoutConfig($plan, $stripeReady),
            ])
            ->all();

        return view('dashboard.billing', [
            'tenant' => $tenant,
            'currentPlan' => $currentPlan,
            'selectedPlan' => $selectedPlan,
            'packagePlans' => $packagePlans,
            'packageCheckoutConfigs' => $packageCheckoutConfigs,
            'billingPortalAvailable' => $tenant !== null
                && $stripeReady
                && filled($tenant->stripe_customer_id)
                && filled($tenant->stripe_subscription_id)
                && in_array($tenant->billing_option, ['subscription_annual', 'subscription_yearly'], true),
            'invoiceSyncAvailable' => $tenant !== null && $stripeReady && filled($tenant->stripe_customer_id),
            'invoiceSyncMetadataAvailable' => $invoiceSyncMetadataAvailable,
            'invoiceCount' => $invoiceCount,
            'invoiceSyncDefaultLimit' => (int) old('max_invoices', '250'),
            'invoiceSyncDefaultSinceDate' => (string) old('since_date', ''),
            'availableBillingOptions' => $selectedPlan ? $this->availableBillingOptionsForPlan($selectedPlan, $stripeReady) : [],
            'invoices' => $tenant
                ? SaasInvoice::query()->withoutGlobalScopes()->where('tenant_id', $tenant->id)->latest('issued_at')->take(50)->get()
                : collect(),
        ]);
    }

    public function packages(): View
    {
        $tenant = app(TenantContext::class)->tenant();
        $tenant?->loadMissing('subscriptionPlan');
        $platformSetting = PlatformSetting::current();
        $stripeReady = $platformSetting->hasStripeCredentials();
        $currentPlan = $tenant?->resolvedPlan();

        if ($currentPlan === null && $tenant?->plan_id) {
            $currentPlan = Plan::query()->find($tenant->plan_id);
        }

        $packagePlans = Plan::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        if ($currentPlan !== null && ! $packagePlans->contains('id', $currentPlan->id)) {
            $packagePlans->prepend($currentPlan);
        }

        $packageCheckoutConfigs = $packagePlans
            ->mapWithKeys(fn (Plan $plan): array => [
                (string) $plan->id => $this->packageCheckoutConfig($plan, $stripeReady),
            ])
            ->all();

        return view('dashboard.billing-packages', [
            'tenant' => $tenant,
            'currentPlan' => $currentPlan,
            'packagePlans' => $packagePlans,
            'packageCheckoutConfigs' => $packageCheckoutConfigs,
            'stripeReady' => $stripeReady,
        ]);
    }

    public function checkout(Request $request): RedirectResponse
    {
        $tenant = app(TenantContext::class)->tenant();

        abort_unless($tenant !== null, 404, 'Tenant not found.');

        $validated = $request->validate([
            'plan_id' => ['required', 'integer', 'exists:plans,id'],
            'billing_option' => ['required', 'string', 'in:prepaid_annual,subscription_yearly,subscription_annual'],
            'room_count' => ['nullable', 'integer', 'min:1', 'max:10000'],
            'slipok_addon_enabled' => ['nullable', 'boolean'],
        ]);

        $plan = Plan::query()->findOrFail((int) $validated['plan_id']);
        $billingOption = $this->normalizedBillingOption((string) $validated['billing_option']);
        $rawRoomCount = isset($validated['room_count']) ? (int) $validated['room_count'] : null;

        if ($plan->usesCustomRoomPricing() && $rawRoomCount !== null && $rawRoomCount < $plan->minimumRoomCount()) {
            throw ValidationException::withMessages([
                'room_count' => 'Custom package requires at least '.$plan->minimumRoomCount().' rooms.',
            ]);
        }

        $roomCount = $plan->usesCustomRoomPricing()
            ? $plan->normalizedRoomCount((int) ($validated['room_count'] ?? $plan->minimumRoomCount()))
            : null;
        $slipOkAddonEnabled = $plan->usesCustomRoomPricing()
            ? ((bool) ($validated['slipok_addon_enabled'] ?? false) && $plan->supportsSlipOk())
            : false;
        $stripePriceId = $plan->usesCustomRoomPricing()
            ? null
            : $this->stripePriceIdForOption($plan, $billingOption);

        if (! $plan->usesCustomRoomPricing()) {
            abort_if(blank($stripePriceId), 422, 'Selected billing option is not available for this package yet.');
            if (! $this->isStripePriceId((string) $stripePriceId)) {
                throw ValidationException::withMessages([
                    'plan_id' => 'Selected package is misconfigured. Stripe Price ID must start with price_. Please contact Platform Admin.',
                ]);
            }
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
        $checkoutMetadata = [
            'tenant_id' => (string) $tenant->id,
            'plan_id' => (string) $plan->id,
            'billing_option' => $billingOption,
            'room_count' => $plan->usesCustomRoomPricing() ? (string) ($roomCount ?? 1) : '',
            'slipok_addon_enabled' => $plan->usesCustomRoomPricing() && $slipOkAddonEnabled ? '1' : '0',
        ];

        try {
            $payload = [
                'mode' => $billingOption === 'prepaid_annual' ? 'payment' : 'subscription',
                'customer' => $customerId,
                'line_items' => $plan->usesCustomRoomPricing()
                    ? $this->customCheckoutLineItems($plan, $billingOption, $roomCount ?? 1, $slipOkAddonEnabled)
                    : [
                        [
                            'price' => $stripePriceId,
                            'quantity' => 1,
                        ],
                    ],
                'allow_promotion_codes' => true,
                'success_url' => $successUrl,
                'cancel_url' => $cancelUrl,
                'metadata' => $checkoutMetadata,
            ];

            if ($billingOption === 'prepaid_annual') {
                $payload['invoice_creation'] = [
                    'enabled' => true,
                    'invoice_data' => [
                        'metadata' => $checkoutMetadata,
                    ],
                ];
            } else {
                $payload['subscription_data'] = [
                    'metadata' => $checkoutMetadata,
                ];
            }

            $session = $stripe->checkout->sessions->create($payload);

        } catch (ApiErrorException $e) {
            Log::warning('Stripe checkout session creation failed', [
                'tenant_id' => $tenant->id,
                'plan_id' => $plan->id,
                'stripe_price_id' => $stripePriceId,
                'billing_option' => $billingOption,
                'error' => $e->getMessage(),
            ]);

            throw ValidationException::withMessages([
                'plan_id' => 'Unable to start Stripe checkout for this package. Please verify the Stripe billing configuration in Package Management.',
            ]);
        }

        return redirect()->away($session->url);
    }

    public function syncInvoices(): RedirectResponse
    {
        $tenant = app(TenantContext::class)->tenant();

        abort_unless($tenant !== null, 404, 'Tenant not found.');

        $platformSetting = PlatformSetting::current();
        abort_if(! $platformSetting->hasStripeCredentials(), 422, 'Stripe is not configured by Platform Admin.');

        if (blank($tenant->stripe_customer_id)) {
            return redirect()
                ->route('app.billing')
                ->with('warning', 'Stripe customer not found for this tenant yet.');
        }

        $validated = request()->validate([
            'since_date' => ['nullable', 'date'],
            'max_invoices' => ['nullable', 'integer', 'min:1', 'max:1000'],
        ]);

        $sinceDate = isset($validated['since_date']) && is_string($validated['since_date']) && $validated['since_date'] !== ''
            ? Carbon::parse($validated['since_date'])->startOfDay()
            : null;
        $maxInvoices = (int) ($validated['max_invoices'] ?? 250);

        $stripe = $this->stripeClient((string) $platformSetting->stripe_secret_key);
        $syncedInvoices = 0;
        $processedInvoices = 0;
        $startingAfter = null;
        $stopSync = false;

        try {
            do {
                $remainingInvoices = $maxInvoices - $processedInvoices;
                $params = [
                    'customer' => $tenant->stripe_customer_id,
                    'limit' => max(1, min(100, $remainingInvoices)),
                ];

                if ($sinceDate !== null) {
                    $params['created'] = [
                        'gte' => $sinceDate->timestamp,
                    ];
                }

                if ($startingAfter !== null) {
                    $params['starting_after'] = $startingAfter;
                }

                $invoicePage = $stripe->invoices->all($params);
                $invoiceData = $this->arrayValue($this->objectValue($invoicePage, 'data'));

                foreach ($invoiceData as $invoiceObject) {
                    $invoiceCreatedAt = $this->intOrNull(data_get($invoiceObject, 'created'));

                    if ($sinceDate !== null && $invoiceCreatedAt !== null && $invoiceCreatedAt < $sinceDate->timestamp) {
                        $stopSync = true;

                        break;
                    }

                    if ($processedInvoices >= $maxInvoices) {
                        $stopSync = true;

                        break;
                    }

                    if ($this->stripeInvoiceSyncService->upsertInvoiceArtifact($tenant, $invoiceObject)) {
                        $syncedInvoices++;
                    }

                    $processedInvoices++;
                }

                $hasMore = (bool) $this->objectValue($invoicePage, 'has_more');
                $lastInvoice = $invoiceData[array_key_last($invoiceData)] ?? null;
                $startingAfter = $this->stripeIdentifier($lastInvoice);
            } while ($hasMore && ! $stopSync && $startingAfter !== null && $processedInvoices < $maxInvoices);
        } catch (ApiErrorException $e) {
            Log::warning('Stripe invoice sync failed', [
                'tenant_id' => $tenant->id,
                'stripe_customer_id' => $tenant->stripe_customer_id,
                'error' => $e->getMessage(),
            ]);

            return redirect()
                ->route('app.billing')
                ->with('warning', 'Unable to sync Stripe invoices right now. Please try again later.');
        }

        $warningMessage = null;

        if ($this->supportsTenantInvoiceSyncMetadata()) {
            $tenant->update([
                'stripe_invoice_last_synced_at' => now(),
                'stripe_invoice_last_sync_count' => $syncedInvoices,
            ]);
        } else {
            Log::warning('Stripe invoice sync metadata columns are missing; skipping tenant sync summary update.', [
                'tenant_id' => $tenant->id,
            ]);

            $warningMessage = 'Stripe invoices synced, but sync summary requires the latest billing migration.';
        }

        $flashData = [
            'success' => $syncedInvoices > 0
                ? 'Synced '.$syncedInvoices.' Stripe invoice(s).'
                : 'No Stripe invoices were found to sync.',
        ];

        if ($warningMessage !== null) {
            $flashData['warning'] = $warningMessage;
        }

        return redirect()
            ->route('app.billing')
            ->with($flashData);
    }

    /**
     * @return array<string, mixed>
     */
    private function packageCheckoutConfig(Plan $plan, bool $stripeReady): array
    {
        $billingOptions = array_map(
            static fn (array $option): array => [
                'value' => (string) $option['value'],
                'label' => (string) $option['label'],
                'price' => (float) $option['price'],
            ],
            $this->availableBillingOptionsForPlan($plan, $stripeReady)
        );

        return [
            'id' => $plan->id,
            'name' => $plan->name,
            'isCustomRoomPricing' => $plan->usesCustomRoomPricing(),
            'supportsSlipOk' => $plan->supportsSlipOk(),
            'minimumRoomCount' => $plan->minimumRoomCount(),
            'roomPriceMonthly' => $plan->roomPriceMonthly(),
            'addonPriceMonthly' => $plan->slipAddonPriceMonthly(),
            'monthlyPrice' => (float) $plan->price_monthly,
            'priceDescription' => $plan->usesCustomRoomPricing()
                ? 'Starting at '.number_format($plan->roomPriceMonthly(), 2).' THB / room / month'
                : number_format((float) $plan->price_monthly, 2).' THB / month',
            'billingOptions' => $billingOptions,
        ];
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
                ->route('app.billing')
                ->with('success', $tenant->billing_option === 'prepaid_annual'
                    ? 'Prepaid annual access activated successfully.'
                    : 'Subscription activated successfully.');
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
        abort_if(! in_array($tenant->billing_option, ['subscription_annual', 'subscription_yearly'], true) || blank($tenant->stripe_subscription_id), 422, 'Billing portal is available only for recurring subscriptions.');

        $stripe = $this->stripeClient((string) $platformSetting->stripe_secret_key);

        $session = $stripe->billingPortal->sessions->create([
            'customer' => $tenant->stripe_customer_id,
            'return_url' => url('/app/billing'),
        ]);

        return redirect()->away($session->url);
    }

    private function stripeClient(string $secretKey): object
    {
        if (app()->bound('billing.stripe_client_factory')) {
            $factory = app('billing.stripe_client_factory');

            if (is_callable($factory)) {
                $client = $factory($secretKey);

                if (is_object($client)) {
                    return $client;
                }
            }
        }

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
        $billingOption = $this->normalizedBillingOption((string) ($metadata['billing_option'] ?? 'subscription_annual'));
        $selectedRoomCount = $this->intOrNull($metadata['room_count'] ?? null);
        $selectedSlipOkAddonEnabled = $this->boolOrFalse($metadata['slipok_addon_enabled'] ?? null);

        $updates = [
            'stripe_customer_id' => $customerId ?? $tenant->stripe_customer_id,
            'billing_option' => $billingOption,
        ];

        $plan = null;

        if ($planId !== null) {
            $plan = Plan::query()->find($planId);

            if ($plan !== null) {
                $updates['plan_id'] = $plan->id;
                $updates['plan'] = $plan->slug;

                if ($plan->usesCustomRoomPricing()) {
                    $roomCount = $plan->normalizedRoomCount($selectedRoomCount ?? (int) ($tenant->subscribed_room_limit ?? $plan->minimumRoomCount()));
                    $updates['subscribed_room_limit'] = $roomCount;
                    $updates['subscribed_slipok_enabled'] = $selectedSlipOkAddonEnabled;
                    $updates['subscribed_slipok_monthly_limit'] = $selectedSlipOkAddonEnabled
                        ? $roomCount * $plan->slipAddonRightsPerRoom()
                        : 0;
                } else {
                    $updates['subscribed_room_limit'] = null;
                    $updates['subscribed_slipok_enabled'] = false;
                    $updates['subscribed_slipok_monthly_limit'] = null;
                }
            }
        }

        if ($billingOption === 'prepaid_annual') {
            $shouldActivate = $paymentStatus === 'paid' || $checkoutStatus === 'complete';

            $updates['stripe_subscription_id'] = null;
            $updates['subscription_status'] = $shouldActivate ? 'prepaid' : 'incomplete';
            $updates['subscription_current_period_end'] = null;

            if ($shouldActivate) {
                $updates['status'] = 'active';
                $updates['access_expires_at'] = $this->prepaidAccessEndAt($tenant);
            }

            $tenant->update($updates);

            return;
        }

        $activationStatuses = ['active', 'trialing'];
        $shouldActivate = in_array($subscriptionStatus, $activationStatuses, true)
            || $paymentStatus === 'paid'
            || $checkoutStatus === 'complete';

        $updates['stripe_subscription_id'] = $subscriptionId ?? $tenant->stripe_subscription_id;
        $updates['subscription_current_period_end'] = $currentPeriodEnd !== null ? now()->setTimestamp($currentPeriodEnd) : $tenant->subscription_current_period_end;
        $updates['access_expires_at'] = null;

        $updates['subscription_status'] = $subscriptionStatus
            ?? ($shouldActivate ? 'active' : $tenant->subscription_status);

        if ($shouldActivate) {
            $updates['status'] = 'active';
        }

        $tenant->update($updates);
    }

    /**
     * @return array<int, array{value:string,label:string,price:float}>
     */
    private function availableBillingOptionsForPlan(Plan $plan, bool $stripeReady = true): array
    {
        $options = [];

        if ($plan->usesCustomRoomPricing()) {
            if (! $stripeReady) {
                return [];
            }

            return [
                [
                    'value' => 'prepaid_annual',
                    'label' => 'Prepaid annual',
                    'price' => $plan->computedBillingPriceFor('prepaid_annual'),
                ],
                [
                    'value' => 'subscription_annual',
                    'label' => 'Subscription annual',
                    'price' => $plan->computedBillingPriceFor('subscription_annual'),
                ],
            ];
        }

        if ($plan->stripePrepaidAnnualPriceId()) {
            $options[] = [
                'value' => 'prepaid_annual',
                'label' => 'Prepaid annual',
                'price' => $plan->prepaidAnnualPriceAmount(),
            ];
        }

        if ($plan->stripeSubscriptionAnnualPriceId()) {
            $options[] = [
                'value' => 'subscription_annual',
                'label' => 'Subscription annual',
                'price' => $plan->yearlyPriceAmount(),
            ];
        }

        return $options;
    }

    private function stripePriceIdForOption(Plan $plan, string $billingOption): ?string
    {
        return match ($billingOption) {
            'prepaid_annual' => $plan->stripePrepaidAnnualPriceId(),
            'subscription_annual', 'subscription_yearly' => $plan->stripeSubscriptionAnnualPriceId(),
            default => null,
        };
    }

    private function normalizedBillingOption(string $value): string
    {
        return match ($value) {
            'subscription_yearly' => 'subscription_annual',
            'prepaid_annual', 'subscription_annual' => $value,
            default => 'subscription_annual',
        };
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function customCheckoutLineItems(Plan $plan, string $billingOption, int $roomCount, bool $slipOkAddonEnabled): array
    {
        $multiplier = $this->billingPeriodMultiplier($billingOption);
        $lineItems = [
            [
                'price_data' => $this->customPriceData(
                    $plan,
                    $plan->roomPriceMonthly() * $multiplier,
                    $billingOption,
                    $plan->name.' Room License'
                ),
                'quantity' => $roomCount,
            ],
        ];

        if ($slipOkAddonEnabled && $plan->supportsSlipOk()) {
            $lineItems[] = [
                'price_data' => $this->customPriceData(
                    $plan,
                    $plan->slipAddonPriceMonthly() * $multiplier,
                    $billingOption,
                    $plan->name.' Slip verification addon'
                ),
                'quantity' => $roomCount,
            ];
        }

        return $lineItems;
    }

    /**
     * @return array<string, mixed>
     */
    private function customPriceData(Plan $plan, float $amount, string $billingOption, string $name): array
    {
        $priceData = [
            'currency' => 'thb',
            'unit_amount' => (int) round($amount * 100),
            'product_data' => [
                'name' => $name,
                'metadata' => [
                    'plan_id' => (string) $plan->id,
                    'plan_slug' => (string) $plan->slug,
                    'billing_option' => $billingOption,
                    'source' => 'custom_room_checkout',
                ],
            ],
        ];

        if ($billingOption !== 'prepaid_annual') {
            $priceData['recurring'] = [
                'interval' => 'year',
            ];
        }

        return $priceData;
    }

    private function billingPeriodMultiplier(string $billingOption): int
    {
        return match ($billingOption) {
            'prepaid_annual', 'subscription_annual', 'subscription_yearly' => 12,
            default => 1,
        };
    }

    private function supportsTenantInvoiceSyncMetadata(): bool
    {
        return Schema::hasColumns('tenants', [
            'stripe_invoice_last_synced_at',
            'stripe_invoice_last_sync_count',
        ]);
    }

    private function prepaidAccessEndAt(Tenant $tenant): \Illuminate\Support\Carbon
    {
        $base = now();

        if ($tenant->access_expires_at instanceof \Illuminate\Support\Carbon && $tenant->access_expires_at->isFuture()) {
            $base = $tenant->access_expires_at->copy();
        }

        if ($tenant->subscription_current_period_end instanceof \Illuminate\Support\Carbon && $tenant->subscription_current_period_end->isFuture() && $tenant->subscription_current_period_end->greaterThan($base)) {
            $base = $tenant->subscription_current_period_end->copy();
        }

        return $base->addYear();
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

    private function boolOrFalse(mixed $value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        if (is_int($value)) {
            return $value === 1;
        }

        if (is_string($value)) {
            return in_array(strtolower(trim($value)), ['1', 'true', 'yes', 'on'], true);
        }

        return false;
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
