<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\PlatformSetting;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

final class WebhookRateLimitingTest extends TestCase
{
    use RefreshDatabase;

    public function test_line_webhook_is_rate_limited(): void
    {
        config()->set('services.line.webhook_rate_limit', 2);
        Queue::fake();

        $tenant = Tenant::query()->create([
            'name' => 'Rate Limit Dorm',
            'line_channel_secret' => 'rate-limit-secret',
            'line_channel_access_token' => 'rate-limit-token',
        ]);

        $payload = [
            'events' => [[
                'type' => 'follow',
                'replyToken' => 'reply-token-rate-limit',
                'source' => ['userId' => 'U-rate-limit'],
            ]],
        ];

        $this->callLineWebhook($payload, (string) $tenant->line_channel_secret)->assertOk();
        $this->callLineWebhook($payload, (string) $tenant->line_channel_secret)->assertOk();
        $this->callLineWebhook($payload, (string) $tenant->line_channel_secret)->assertStatus(429);
    }

    public function test_stripe_webhook_is_rate_limited(): void
    {
        config()->set('services.stripe.webhook_rate_limit', 1);

        PlatformSetting::current()->update([
            'stripe_enabled' => true,
            'stripe_webhook_secret' => 'whsec_rate_limit',
        ]);

        $payload = [
            'id' => 'evt_rate_limit_001',
            'object' => 'event',
            'type' => 'invoice.paid',
            'livemode' => false,
            'data' => [
                'object' => [
                    'id' => 'in_rate_limit_001',
                ],
            ],
        ];

        $this->callStripeWebhook($payload, 'whsec_rate_limit')->assertOk();
        $this->callStripeWebhook($payload, 'whsec_rate_limit')->assertStatus(429);
    }

    /**
     * @param array<string, mixed> $payload
     * @return TestResponse<\Symfony\Component\HttpFoundation\Response>
     */
    private function callLineWebhook(array $payload, string $secret)
    {
        $json = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        if ($json === false) {
            self::fail('Unable to encode LINE webhook payload to JSON.');
        }

        $signature = base64_encode(hash_hmac('sha256', $json, $secret, true));

        return $this->call(
            'POST',
            '/api/line/webhook',
            [],
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_X_LINE_SIGNATURE' => $signature,
            ],
            $json
        );
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
}