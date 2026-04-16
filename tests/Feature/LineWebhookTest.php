<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
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

        $response = $this->callLineWebhook($payload, $tenant->line_channel_secret);

        $response->assertOk();
        $response->assertJson(['ok' => true]);

        $this->assertDatabaseHas('notification_logs', [
            'tenant_id' => $tenant->id,
            'channel' => 'line',
            'event' => 'follow',
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

        $response = $this->callLineWebhook($payload, $tenantB->line_channel_secret);

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
    }

    private function callLineWebhook(array $payload, string $secret)
    {
        $json = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
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
