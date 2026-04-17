<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class LineWebhookTest extends TestCase
{
    use RefreshDatabase;

    public function test_line_webhook_accepts_follow_event_with_valid_signature(): void
    {
        Http::fake(['https://api.line.me/*' => Http::response(['ok' => true], 200)]);

        $tenant = Tenant::query()->create([
            'name' => 'Dorm A',
            'line_channel_secret' => 'tenant-a-secret',
            'line_channel_access_token' => 'tenant-a-token',
        ]);

        $payload = [
            'events' => [
                [
                    'type' => 'follow',
                    'replyToken' => 'reply-token-1',
                    'source' => [
                        'userId' => 'U-demo-user',
                    ],
                ],
            ],
        ];

        $response = $this->callLineWebhook($payload, (string) $tenant->line_channel_secret);

        $response->assertOk();
        $response->assertJson(['ok' => true]);

        $this->assertDatabaseHas('notification_logs', [
            'tenant_id' => $tenant->id,
            'channel' => 'line',
            'event' => 'follow',
        ]);

        $this->assertDatabaseHas('line_webhook_logs', [
            'tenant_id' => $tenant->id,
            'event_type' => 'follow',
            'line_user_id' => 'U-demo-user',
        ]);

        $this->assertDatabaseHas('line_messages', [
            'tenant_id' => $tenant->id,
            'direction' => 'outbound',
            'message_type' => 'template',
        ]);
    }

    public function test_line_webhook_rejects_invalid_signature(): void
    {
        Tenant::query()->create([
            'name' => 'Dorm A',
            'line_channel_secret' => 'tenant-a-secret',
        ]);

        $payload = [
            'events' => [
                [
                    'type' => 'follow',
                    'source' => [
                        'userId' => 'U-demo-user',
                    ],
                ],
            ],
        ];

        $response = $this->call(
            'POST',
            '/api/line/webhook',
            [],
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_X_LINE_SIGNATURE' => 'invalid-signature',
            ],
            json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
        );

        $response->assertStatus(401);
        $response->assertJson(['ok' => false]);
    }

    public function test_line_webhook_links_customer_using_token_for_matching_tenant(): void
    {
        Http::fake(['https://api.line.me/*' => Http::response(['ok' => true], 200)]);

        $tenantA = Tenant::query()->create([
            'name' => 'Dorm A',
            'line_channel_secret' => 'tenant-a-secret',
            'line_channel_access_token' => 'tenant-a-token',
        ]);

        $tenantB = Tenant::query()->create([
            'name' => 'Dorm B',
            'line_channel_secret' => 'tenant-b-secret',
            'line_channel_access_token' => 'tenant-b-token',
        ]);

        Customer::query()->create([
            'tenant_id' => $tenantA->id,
            'name' => 'Resident A',
            'phone' => '0811111111',
        ]);

        $customerB = Customer::query()->create([
            'tenant_id' => $tenantB->id,
            'name' => 'Resident B',
            'phone' => '0822222222',
        ]);

        DB::table('customer_line_links')->insert([
            'tenant_id' => $tenantB->id,
            'customer_id' => $customerB->id,
            'link_token' => 'ABC12345',
            'expired_at' => now()->addHour(),
            'used_at' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $payload = [
            'events' => [
                [
                    'type' => 'message',
                    'replyToken' => 'reply-token-2',
                    'source' => [
                        'userId' => 'U-linked-user',
                    ],
                    'message' => [
                        'type' => 'text',
                        'text' => 'LINK ABC12345',
                    ],
                ],
            ],
        ];

        $response = $this->callLineWebhook($payload, (string) $tenantB->line_channel_secret);

        $response->assertOk();

        $this->assertDatabaseHas('customers', [
            'id' => $customerB->id,
            'line_user_id' => 'U-linked-user',
        ]);

        $this->assertDatabaseHas('customer_line_links', [
            'tenant_id' => $tenantB->id,
            'customer_id' => $customerB->id,
            'link_token' => 'ABC12345',
        ]);

        $this->assertDatabaseHas('notification_logs', [
            'tenant_id' => $tenantB->id,
            'event' => 'message',
            'status' => 'linked',
        ]);

        $this->assertDatabaseHas('line_messages', [
            'tenant_id' => $tenantB->id,
            'customer_id' => $customerB->id,
            'direction' => 'outbound',
            'message_type' => 'text',
        ]);
    }

    public function test_line_webhook_handles_postback_payment_command(): void
    {
        Http::fake(['https://api.line.me/*' => Http::response(['ok' => true], 200)]);

        $tenant = Tenant::query()->create([
            'name' => 'Dorm Postback',
            'line_channel_secret' => 'tenant-postback-secret',
            'line_channel_access_token' => 'tenant-postback-token',
        ]);

        $customer = Customer::query()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Resident Postback',
            'line_user_id' => 'U-postback-user',
        ]);

        $payload = [
            'events' => [
                [
                    'type' => 'postback',
                    'replyToken' => 'reply-token-postback',
                    'source' => [
                        'userId' => 'U-postback-user',
                    ],
                    'postback' => [
                        'data' => 'action=pay',
                    ],
                ],
            ],
        ];

        $response = $this->callLineWebhook($payload, (string) $tenant->line_channel_secret);

        $response->assertOk();

        $this->assertDatabaseHas('notification_logs', [
            'tenant_id' => $tenant->id,
            'event' => 'postback',
            'status' => 'replied',
            'message' => 'Resident requested payment link',
        ]);

        $this->assertDatabaseHas('line_webhook_logs', [
            'tenant_id' => $tenant->id,
            'event_type' => 'postback',
            'line_user_id' => 'U-postback-user',
        ]);

        $this->assertDatabaseHas('line_messages', [
            'tenant_id' => $tenant->id,
            'customer_id' => $customer->id,
            'direction' => 'inbound',
            'message_type' => 'postback',
        ]);
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
}
