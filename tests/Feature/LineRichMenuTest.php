<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\LineMessage;
use App\Models\Room;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class LineRichMenuTest extends TestCase
{
    use RefreshDatabase;

    public function test_repair_postback_returns_signed_repair_form_link(): void
    {
        Http::fake(['https://api.line.me/*' => Http::response(['ok' => true], 200)]);

        $tenant = Tenant::query()->create([
            'name' => 'Repair Dorm',
            'line_channel_secret' => 'repair-secret',
            'line_channel_access_token' => 'repair-token',
        ]);

        $room = Room::withoutGlobalScopes()->create([
            'tenant_id' => $tenant->id,
            'room_number' => 'A-101',
            'floor' => 1,
            'room_type' => 'Standard',
            'price' => 4000,
            'status' => 'occupied',
        ]);

        Customer::query()->create([
            'tenant_id' => $tenant->id,
            'room_id' => $room->id,
            'name' => 'Repair Resident',
            'line_user_id' => 'U-repair-user',
        ]);

        $response = $this->callLineWebhook([
            'events' => [[
                'type' => 'postback',
                'replyToken' => 'reply-token-repair',
                'source' => ['userId' => 'U-repair-user'],
                'postback' => ['data' => 'action=repair'],
            ]],
        ], $tenant->line_channel_secret);

        $response->assertOk();

        $lineMessage = LineMessage::query()
            ->where('tenant_id', $tenant->id)
            ->where('direction', 'outbound')
            ->latest('id')
            ->firstOrFail();

        $message = (string) data_get($lineMessage->payload, 'message');

        $this->assertStringContainsString('แจ้งซ่อมผ่านฟอร์มนี้ได้ทันที', $message);
        $this->assertStringContainsString('/resident/line/repair/', $message);
    }

    public function test_contact_postback_returns_owner_contact_details(): void
    {
        Http::fake(['https://api.line.me/*' => Http::response(['ok' => true], 200)]);

        $tenant = Tenant::query()->create([
            'name' => 'Contact Dorm',
            'line_channel_secret' => 'contact-secret',
            'line_channel_access_token' => 'contact-token',
            'support_contact_name' => 'Somchai',
            'support_contact_phone' => '0812345678',
            'support_line_id' => 'owner-line',
        ]);

        Customer::query()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Contact Resident',
            'line_user_id' => 'U-contact-user',
        ]);

        $response = $this->callLineWebhook([
            'events' => [[
                'type' => 'postback',
                'replyToken' => 'reply-token-contact',
                'source' => ['userId' => 'U-contact-user'],
                'postback' => ['data' => 'action=contact'],
             ]],
         ], $tenant->line_channel_secret);
 
         $response->assertOk();

        $lineMessage = LineMessage::query()
            ->where('tenant_id', $tenant->id)
            ->where('direction', 'outbound')
            ->latest('id')
            ->firstOrFail();

        $message = (string) data_get($lineMessage->payload, 'message');

        $this->assertStringContainsString('Somchai', $message);
        $this->assertStringContainsString('0812345678', $message);
        $this->assertStringContainsString('owner-line', $message);
    }

    public function test_signed_repair_request_form_can_store_request(): void
    {
        $tenant = Tenant::query()->create(['name' => 'Signed Repair Dorm', 'status' => 'active']);
        $room = Room::withoutGlobalScopes()->create([
            'tenant_id' => $tenant->id,
            'room_number' => 'B-201',
            'floor' => 2,
            'room_type' => 'Standard',
            'price' => 4200,
            'status' => 'occupied',
        ]);

        $customer = Customer::query()->create([
            'tenant_id' => $tenant->id,
            'room_id' => $room->id,
            'name' => 'Signed Resident',
        ]);

        $url = URL::temporarySignedRoute(
            'resident.line.repair.store',
            now()->addDay(),
            ['customer' => $customer->id]
        );

        $this->post($url, [
            'title' => 'แอร์ไม่เย็น',
            'description' => 'แอร์ห้อง B-201 เปิดแล้วไม่เย็นและมีเสียงดัง',
        ])->assertSessionHas('status');

        $this->assertDatabaseHas('repair_requests', [
            'tenant_id' => $tenant->id,
            'customer_id' => $customer->id,
            'room_id' => $room->id,
            'title' => 'แอร์ไม่เย็น',
            'status' => 'pending',
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
