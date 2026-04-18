<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Jobs\ProcessStripeWebhookEventJob;
use App\Models\Plan;
use App\Models\PlatformSetting;
use App\Models\SaasInvoice;
use App\Models\StripeWebhookEvent;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

final class StripeWebhookProcessingTest extends TestCase
{
    use RefreshDatabase;

    public function test_stripe_webhook_dispatches_unprocessed_event(): void
    {
        PlatformSetting::current()->update([
            'stripe_enabled' => true,
            'stripe_webhook_secret' => 'whsec_test_secret',
        ]);

        $payload = [
            'id' => 'evt_new_001',
            'object' => 'event',
            'type' => 'invoice.paid',
            'livemode' => false,
            'data' => [
                'object' => [
                    'id' => 'in_new_001',
                ],
            ],
        ];

        $response = $this->callStripeWebhook($payload, 'whsec_test_secret');

        $response->assertOk()->assertJson(['ok' => true]);
        $this->assertDatabaseHas('stripe_webhook_events', [
            'event_id' => 'evt_new_001',
            'status' => 'processed',
        ]);
    }

    public function test_stripe_webhook_does_not_dispatch_processed_duplicate_event(): void
    {
        PlatformSetting::current()->update([
            'stripe_enabled' => true,
            'stripe_webhook_secret' => 'whsec_test_secret',
        ]);

        StripeWebhookEvent::query()->create([
            'event_id' => 'evt_processed_001',
            'event_type' => 'invoice.paid',
            'livemode' => false,
            'received_at' => now(),
            'payload' => [
                'id' => 'evt_processed_001',
                'type' => 'invoice.paid',
                'data' => ['object' => ['id' => 'in_processed_001']],
            ],
            'status' => 'processed',
        ]);

        $payload = [
            'id' => 'evt_processed_001',
            'object' => 'event',
            'type' => 'invoice.paid',
            'livemode' => false,
            'data' => [
                'object' => [
                    'id' => 'in_processed_001',
                ],
            ],
        ];

        $response = $this->callStripeWebhook($payload, 'whsec_test_secret');

        $response->assertOk()->assertJson(['ok' => true]);
        $this->assertSame(1, StripeWebhookEvent::query()->count());
    }

    public function test_checkout_session_completed_webhook_activates_tenant_immediately(): void
    {
        PlatformSetting::current()->update([
            'stripe_enabled' => true,
            'stripe_webhook_secret' => 'whsec_test_secret',
        ]);

        $plan = Plan::query()->create([
            'name' => 'Growth',
            'slug' => 'growth',
            'price_monthly' => 990,
        ]);

        $tenant = Tenant::query()->create([
            'name' => 'Immediate Stripe Dorm',
            'plan' => 'trial',
            'status' => 'pending_checkout',
            'subscription_status' => 'incomplete',
        ]);

        $payload = [
            'id' => 'evt_checkout_sync_001',
            'object' => 'event',
            'type' => 'checkout.session.completed',
            'livemode' => false,
            'data' => [
                'object' => [
                    'id' => 'cs_test_001',
                    'customer' => 'cus_sync_001',
                    'subscription' => 'sub_sync_001',
                    'payment_status' => 'paid',
                    'metadata' => [
                        'tenant_id' => (string) $tenant->id,
                        'plan_id' => (string) $plan->id,
                    ],
                ],
            ],
        ];

        $response = $this->callStripeWebhook($payload, 'whsec_test_secret');

        $response->assertOk()->assertJson(['ok' => true]);

        $tenant->refresh();

        $this->assertSame('active', $tenant->status);
        $this->assertSame('active', $tenant->subscription_status);
        $this->assertSame('cus_sync_001', $tenant->stripe_customer_id);
        $this->assertSame('sub_sync_001', $tenant->stripe_subscription_id);
        $this->assertSame($plan->id, $tenant->plan_id);
        $this->assertSame('growth', $tenant->plan);
        $this->assertDatabaseHas('stripe_webhook_events', [
            'event_id' => 'evt_checkout_sync_001',
            'status' => 'processed',
        ]);
    }

    public function test_process_stripe_webhook_event_job_marks_event_processed_and_updates_tenant(): void
    {
        $plan = Plan::query()->create([
            'name' => 'Growth',
            'slug' => 'growth',
            'price_monthly' => 990,
            'limits' => [
                'rooms' => 100,
            ],
        ]);

        $tenant = Tenant::query()->create([
            'name' => 'Stripe Dorm',
            'plan' => 'trial',
            'status' => 'pending_checkout',
            'subscription_status' => 'incomplete',
        ]);

        $eventRow = StripeWebhookEvent::query()->create([
            'event_id' => 'evt_checkout_001',
            'event_type' => 'checkout.session.completed',
            'livemode' => false,
            'received_at' => now(),
            'payload' => [
                'id' => 'evt_checkout_001',
                'type' => 'checkout.session.completed',
                'data' => [
                    'object' => [
                        'subscription' => 'sub_001',
                        'customer' => 'cus_001',
                        'metadata' => [
                            'tenant_id' => (string) $tenant->id,
                            'plan_id' => (string) $plan->id,
                        ],
                    ],
                ],
            ],
            'status' => 'received',
        ]);

        $this->runStripeJob($eventRow->id);

        $tenant->refresh();
        $eventRow->refresh();

        $this->assertSame('cus_001', $tenant->stripe_customer_id);
        $this->assertSame('sub_001', $tenant->stripe_subscription_id);
        $this->assertSame($plan->id, $tenant->plan_id);
        $this->assertSame('growth', $tenant->getAttribute('plan'));
        $this->assertSame('active', $tenant->status);
        $this->assertSame('processed', $eventRow->status);
        $this->assertNull($eventRow->last_error);
    }

    public function test_process_stripe_webhook_event_job_is_replay_safe_for_processed_events(): void
    {
        $tenant = Tenant::query()->create([
            'name' => 'Replay Dorm',
            'plan' => 'trial',
            'status' => 'active',
            'stripe_customer_id' => 'cus_existing',
        ]);

        $eventRow = StripeWebhookEvent::query()->create([
            'event_id' => 'evt_invoice_001',
            'event_type' => 'invoice.paid',
            'livemode' => false,
            'received_at' => now(),
            'payload' => [
                'id' => 'evt_invoice_001',
                'type' => 'invoice.paid',
                'data' => [
                    'object' => [
                        'id' => 'in_001',
                        'customer' => 'cus_existing',
                        'subscription' => 'sub_existing',
                        'status' => 'paid',
                        'currency' => 'thb',
                        'amount_due' => 1000,
                        'amount_paid' => 1000,
                        'amount_remaining' => 0,
                    ],
                ],
            ],
            'status' => 'received',
        ]);

        $this->runStripeJob($eventRow->id);
        $this->runStripeJob($eventRow->id);

        $eventRow->refresh();
        $tenant->refresh();

        $this->assertSame('processed', $eventRow->status);
        $this->assertSame('active', $tenant->subscription_status);
        $this->assertSame(1, SaasInvoice::query()->where('stripe_invoice_id', 'in_001')->count());
    }

    public function test_stripe_customer_subscription_updated_activates_pending_checkout_tenant(): void
    {
        $plan = Plan::query()->create([
            'name' => 'Lite',
            'slug' => 'lite',
            'price_monthly' => 299,
            'limits' => [
                'rooms' => 50,
            ],
        ]);

        $tenant = Tenant::query()->create([
            'name' => 'Pending Dorm',
            'plan_id' => $plan->id,
            'plan' => 'lite',
            'status' => 'pending_checkout',
            'subscription_status' => 'incomplete',
            'stripe_customer_id' => 'cus_pending',
        ]);

        $eventRow = StripeWebhookEvent::query()->create([
            'event_id' => 'evt_sub_001',
            'event_type' => 'customer.subscription.updated',
            'livemode' => false,
            'received_at' => now(),
            'payload' => [
                'id' => 'evt_sub_001',
                'type' => 'customer.subscription.updated',
                'data' => [
                    'object' => [
                        'id' => 'sub_pending_001',
                        'customer' => 'cus_pending',
                        'status' => 'active',
                        'current_period_end' => now()->addMonth()->timestamp,
                    ],
                ],
            ],
            'status' => 'received',
        ]);

        $this->runStripeJob($eventRow->id);

        $tenant->refresh();
        $eventRow->refresh();

        $this->assertSame('lite', $tenant->plan);
        $this->assertSame('active', $tenant->status);
        $this->assertSame('active', $tenant->subscription_status);
        $this->assertSame('sub_pending_001', $tenant->stripe_subscription_id);
        $this->assertSame('processed', $eventRow->status);
    }

    /**
     * @param array<string, mixed> $payload
     * @return TestResponse<\Symfony\Component\HttpFoundation\Response>
     */
    private function callStripeWebhook(array $payload, string $secret)
    {
        $json = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        if ($json === false) {
            self::fail('Unable to encode Stripe webhook payload to JSON.');
        }

        $timestamp = time();
        $signature = hash_hmac('sha256', $timestamp . '.' . $json, $secret);

        return $this->call(
            'POST',
            '/api/stripe/webhook',
            [],
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_STRIPE_SIGNATURE' => 't=' . $timestamp . ',v1=' . $signature,
            ],
            $json
        );
    }

    private function runStripeJob(int $eventRowId): void
    {
        $job = new ProcessStripeWebhookEventJob($eventRowId);

        app()->call([$job, 'handle']);
    }
}