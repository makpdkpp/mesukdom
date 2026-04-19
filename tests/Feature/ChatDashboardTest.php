<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\LineMessage;
use App\Models\Room;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

final class ChatDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_view_chat_dashboard(): void
    {
        $tenant = Tenant::query()->create([
            'name' => 'Chat Dorm',
            'domain' => 'chat.local',
            'plan' => 'trial',
            'status' => 'active',
        ]);

        $room = Room::query()->create([
            'tenant_id' => $tenant->id,
            'building' => 'A',
            'room_number' => 'A-101',
            'floor' => 1,
            'room_type' => 'Standard',
            'price' => 4500,
            'status' => 'occupied',
        ]);

        $customer = Customer::query()->create([
            'tenant_id' => $tenant->id,
            'room_id' => $room->id,
            'name' => 'Chat Resident',
            'line_user_id' => 'U-chat-001',
        ]);

        LineMessage::query()->create([
            'tenant_id' => $tenant->id,
            'customer_id' => $customer->id,
            'direction' => 'inbound',
            'message_type' => 'text',
            'payload' => ['message' => 'ขอแจ้งซ่อมครับ'],
            'sent_at' => now(),
        ]);

        $user = User::factory()->create([
            'tenant_id' => $tenant->id,
            'role' => 'owner',
            'email_verified_at' => now(),
        ]);

        $this->actingAs($user)
            ->withSession(['tenant_id' => $tenant->id])
            ->get(route('app.chat'))
            ->assertOk()
            ->assertSee('Send LINE Chat')
            ->assertSee('Recent Chat Timeline')
            ->assertSee('Chat Resident')
            ->assertSee('ขอแจ้งซ่อมครับ');
    }

    public function test_owner_can_send_message_to_linked_resident(): void
    {
        Http::fake(['https://api.line.me/*' => Http::response(['ok' => true], 200)]);

        $tenant = Tenant::query()->create([
            'name' => 'Chat Send Dorm',
            'domain' => 'chat-send.local',
            'plan' => 'trial',
            'status' => 'active',
            'line_channel_access_token' => 'tenant-chat-token',
        ]);

        $room = Room::query()->create([
            'tenant_id' => $tenant->id,
            'building' => 'B',
            'room_number' => 'B-202',
            'floor' => 2,
            'room_type' => 'Standard',
            'price' => 4600,
            'status' => 'occupied',
        ]);

        $customer = Customer::query()->create([
            'tenant_id' => $tenant->id,
            'room_id' => $room->id,
            'name' => 'Send Resident',
            'line_user_id' => 'U-chat-send',
        ]);

        $user = User::factory()->create([
            'tenant_id' => $tenant->id,
            'role' => 'owner',
            'email_verified_at' => now(),
        ]);

        $this->actingAs($user)
            ->withSession(['tenant_id' => $tenant->id])
            ->post(route('app.chat.store'), [
                'customer_id' => $customer->id,
                'message' => 'ทดสอบส่งแชทจากหน้า app',
            ])
            ->assertRedirect(route('app.chat', ['customer_id' => $customer->id]));

        $this->assertDatabaseHas('line_messages', [
            'tenant_id' => $tenant->id,
            'customer_id' => $customer->id,
            'direction' => 'outbound',
        ]);
    }
}
