<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\RepairRequest;
use App\Models\Room;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class RepairRequestsDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_view_repairs_dashboard(): void
    {
        $tenant = Tenant::query()->create([
            'name' => 'Repair Dashboard Dorm',
            'domain' => 'repair-dashboard.local',
            'plan' => 'trial',
            'status' => 'active',
        ]);

        $room = Room::withoutGlobalScopes()->create([
            'tenant_id' => $tenant->id,
            'room_number' => 'A-101',
            'floor' => 1,
            'room_type' => 'Standard',
            'price' => 4500,
            'status' => 'occupied',
        ]);

        $customer = Customer::withoutGlobalScopes()->create([
            'tenant_id' => $tenant->id,
            'room_id' => $room->id,
            'name' => 'Repair Resident',
        ]);

        RepairRequest::query()->create([
            'tenant_id' => $tenant->id,
            'customer_id' => $customer->id,
            'room_id' => $room->id,
            'source' => 'line_rich_menu',
            'title' => 'แอร์ไม่เย็น',
            'description' => 'แอร์ตัดบ่อยและมีน้ำหยด',
            'status' => 'pending',
            'submitted_at' => now(),
        ]);

        $user = User::factory()->create([
            'tenant_id' => $tenant->id,
            'role' => 'owner',
            'email_verified_at' => now(),
        ]);

        $this->actingAs($user)
            ->withSession(['tenant_id' => $tenant->id])
            ->get(route('app.repairs'))
            ->assertOk()
            ->assertSee('Repair Requests')
            ->assertSee('Repair Resident')
            ->assertSee('แอร์ไม่เย็น');
    }

    public function test_owner_can_update_repair_request_status(): void
    {
        $tenant = Tenant::query()->create([
            'name' => 'Repair Update Dorm',
            'domain' => 'repair-update.local',
            'plan' => 'trial',
            'status' => 'active',
        ]);

        $room = Room::withoutGlobalScopes()->create([
            'tenant_id' => $tenant->id,
            'room_number' => 'B-202',
            'floor' => 2,
            'room_type' => 'Standard',
            'price' => 4700,
            'status' => 'occupied',
        ]);

        $customer = Customer::withoutGlobalScopes()->create([
            'tenant_id' => $tenant->id,
            'room_id' => $room->id,
            'name' => 'Update Resident',
        ]);

        $repair = RepairRequest::query()->create([
            'tenant_id' => $tenant->id,
            'customer_id' => $customer->id,
            'room_id' => $room->id,
            'source' => 'line_rich_menu',
            'title' => 'ท่อน้ำรั่ว',
            'description' => 'มีน้ำซึมใต้ซิงก์',
            'status' => 'pending',
            'submitted_at' => now(),
        ]);

        $user = User::factory()->create([
            'tenant_id' => $tenant->id,
            'role' => 'owner',
            'email_verified_at' => now(),
        ]);

        $this->actingAs($user)
            ->withSession(['tenant_id' => $tenant->id])
            ->patch(route('app.repairs.update', $repair), [
                'status' => 'in_progress',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('repair_requests', [
            'id' => $repair->id,
            'status' => 'in_progress',
        ]);
    }
}
