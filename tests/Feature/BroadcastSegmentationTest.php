<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\BroadcastMessage;
use App\Models\NotificationLog;
use App\Models\Customer;
use App\Models\Room;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class BroadcastSegmentationTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_send_broadcast_to_all_linked_residents(): void
    {
        Http::fake(['https://api.line.me/*' => Http::response(['ok' => true], 200)]);

        [$tenant, $user] = $this->createTenantAndOwner();

        $roomA = Room::create([
            'tenant_id' => $tenant->id,
            'building' => 'A',
            'room_number' => 'A-101',
            'floor' => 1,
            'room_type' => 'Standard',
            'price' => 3500,
            'status' => 'occupied',
        ]);

        $roomB = Room::create([
            'tenant_id' => $tenant->id,
            'building' => 'B',
            'room_number' => 'B-201',
            'floor' => 2,
            'room_type' => 'Standard',
            'price' => 3600,
            'status' => 'occupied',
        ]);

        Customer::create(['tenant_id' => $tenant->id, 'room_id' => $roomA->id, 'name' => 'Resident A', 'line_user_id' => 'U-A']);
        Customer::create(['tenant_id' => $tenant->id, 'room_id' => $roomB->id, 'name' => 'Resident B', 'line_user_id' => 'U-B']);

        $this->actingAs($user)
            ->withSession(['tenant_id' => $tenant->id])
            ->post(route('app.broadcasts.store'), [
                'scope' => 'all',
                'message' => 'Water outage tonight',
            ])->assertRedirect();

        $this->assertDatabaseHas('broadcast_messages', [
            'tenant_id' => $tenant->id,
            'scope' => 'all',
            'recipient_count' => 2,
        ]);

        $this->assertSame(2, NotificationLog::query()->where('tenant_id', $tenant->id)->where('event', 'broadcast_sent')->count());
    }

    public function test_owner_can_target_specific_building_only(): void
    {
        Http::fake(['https://api.line.me/*' => Http::response(['ok' => true], 200)]);

        [$tenant, $user] = $this->createTenantAndOwner('Building Dorm');

        $roomA = Room::create([
            'tenant_id' => $tenant->id,
            'building' => 'Tower A',
            'room_number' => 'A-301',
            'floor' => 3,
            'room_type' => 'Standard',
            'price' => 3500,
            'status' => 'occupied',
        ]);

        $roomB = Room::create([
            'tenant_id' => $tenant->id,
            'building' => 'Tower B',
            'room_number' => 'B-301',
            'floor' => 3,
            'room_type' => 'Standard',
            'price' => 3500,
            'status' => 'occupied',
        ]);

        $customerA = Customer::create(['tenant_id' => $tenant->id, 'room_id' => $roomA->id, 'name' => 'Tower A Resident', 'line_user_id' => 'U-TA']);
        Customer::create(['tenant_id' => $tenant->id, 'room_id' => $roomB->id, 'name' => 'Tower B Resident', 'line_user_id' => 'U-TB']);

        $this->actingAs($user)
            ->withSession(['tenant_id' => $tenant->id])
            ->post(route('app.broadcasts.store'), [
                'scope' => 'building',
                'building' => 'Tower A',
                'message' => 'Tower A elevator maintenance',
            ])->assertRedirect();

        $broadcast = BroadcastMessage::query()->latest('id')->firstOrFail();

        $this->assertSame('building', $broadcast->scope);
        $this->assertSame('Tower A', $broadcast->target_building);
        $this->assertSame(1, $broadcast->recipient_count);
        $this->assertDatabaseHas('notification_logs', [
            'tenant_id' => $tenant->id,
            'event' => 'broadcast_sent',
            'target' => $customerA->name,
        ]);
    }

    public function test_owner_can_target_floor_and_single_room_segments(): void
    {
        Http::fake(['https://api.line.me/*' => Http::response(['ok' => true], 200)]);

        [$tenant, $user] = $this->createTenantAndOwner('Floor Dorm');

        $room1 = Room::create([
            'tenant_id' => $tenant->id,
            'building' => 'Main',
            'room_number' => 'M-201',
            'floor' => 2,
            'room_type' => 'Standard',
            'price' => 3500,
            'status' => 'occupied',
        ]);

        $room2 = Room::create([
            'tenant_id' => $tenant->id,
            'building' => 'Main',
            'room_number' => 'M-202',
            'floor' => 2,
            'room_type' => 'Standard',
            'price' => 3500,
            'status' => 'occupied',
        ]);

        $room3 = Room::create([
            'tenant_id' => $tenant->id,
            'building' => 'Main',
            'room_number' => 'M-301',
            'floor' => 3,
            'room_type' => 'Standard',
            'price' => 3500,
            'status' => 'occupied',
        ]);

        $customer1 = Customer::create(['tenant_id' => $tenant->id, 'room_id' => $room1->id, 'name' => 'Floor Resident 1', 'line_user_id' => 'U-F1']);
        $customer2 = Customer::create(['tenant_id' => $tenant->id, 'room_id' => $room2->id, 'name' => 'Floor Resident 2', 'line_user_id' => 'U-F2']);
        $customer3 = Customer::create(['tenant_id' => $tenant->id, 'room_id' => $room3->id, 'name' => 'Floor Resident 3', 'line_user_id' => 'U-F3']);

        $this->actingAs($user)
            ->withSession(['tenant_id' => $tenant->id])
            ->post(route('app.broadcasts.store'), [
                'scope' => 'floor',
                'building' => 'Main',
                'floor' => 2,
                'message' => 'Cleaning on floor 2',
            ])->assertRedirect();

        $this->assertDatabaseHas('broadcast_messages', [
            'tenant_id' => $tenant->id,
            'scope' => 'floor',
            'target_floor' => 2,
            'recipient_count' => 2,
        ]);

        $this->actingAs($user)
            ->withSession(['tenant_id' => $tenant->id])
            ->post(route('app.broadcasts.store'), [
                'scope' => 'room',
                'room_id' => $room3->id,
                'message' => 'Please contact office about meter reading',
            ])->assertRedirect();

        $this->assertDatabaseHas('broadcast_messages', [
            'tenant_id' => $tenant->id,
            'scope' => 'room',
            'room_id' => $room3->id,
            'recipient_count' => 1,
        ]);

        $logs = NotificationLog::query()
            ->where('tenant_id', $tenant->id)
            ->where('event', 'broadcast_sent')
            ->pluck('target')
            ->all();

        $this->assertContains($customer1->name, $logs);
        $this->assertContains($customer2->name, $logs);
        $this->assertContains($customer3->name, $logs);
    }

    public function test_owner_cannot_send_broadcast_without_line_token_configuration(): void
    {
        config(['services.line.channel_access_token' => null]);
        Http::fake(['https://api.line.me/*' => Http::response(['ok' => true], 200)]);

        [$tenant, $user] = $this->createTenantAndOwner('No Token Dorm', null);

        $room = Room::create([
            'tenant_id' => $tenant->id,
            'building' => 'A',
            'room_number' => 'A-101',
            'floor' => 1,
            'room_type' => 'Standard',
            'price' => 3500,
            'status' => 'occupied',
        ]);

        Customer::create([
            'tenant_id' => $tenant->id,
            'room_id' => $room->id,
            'name' => 'Resident A',
            'line_user_id' => 'U-A',
        ]);

        $this->actingAs($user)
            ->withSession(['tenant_id' => $tenant->id])
            ->post(route('app.broadcasts.store'), [
                'scope' => 'all',
                'message' => 'Water outage tonight',
            ])
            ->assertRedirect()
            ->assertSessionHas('error', 'LINE channel access token is not configured. Please update LINE settings before broadcasting.');

        $this->assertDatabaseCount('broadcast_messages', 0);
        $this->assertDatabaseCount('notification_logs', 0);
    }

    private function createTenantAndOwner(string $tenantName = 'Broadcast Dorm', ?string $lineToken = 'tenant-line-token'): array
    {
        $tenant = Tenant::create([
            'name' => $tenantName,
            'domain' => str()->slug($tenantName).'.local',
            'plan' => 'trial',
            'status' => 'active',
            'line_channel_access_token' => $lineToken,
        ]);

        $user = User::factory()->create([
            'tenant_id' => $tenant->id,
            'role' => 'owner',
            'email_verified_at' => now(),
        ]);

        return [$tenant, $user];
    }
}
