<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DormitoryFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_page_can_be_opened(): void
    {
        $response = $this->get('/app/dashboard');

        $response->assertOk();
        $response->assertSee('Dormitory Dashboard');
        $response->assertSee('Room Status');
    }

    public function test_rooms_page_only_shows_active_tenant_rooms(): void
    {
        $tenantA = \App\Models\Tenant::create([
            'name' => 'A Dorm',
            'domain' => 'a.local',
            'plan' => 'pro',
            'status' => 'active',
        ]);

        $tenantB = \App\Models\Tenant::create([
            'name' => 'B Dorm',
            'domain' => 'b.local',
            'plan' => 'basic',
            'status' => 'active',
        ]);

        \App\Models\Room::create([
            'tenant_id' => $tenantA->id,
            'room_number' => 'A-101',
            'floor' => 1,
            'room_type' => 'Standard',
            'price' => 3500,
            'status' => 'occupied',
        ]);

        \App\Models\Room::create([
            'tenant_id' => $tenantB->id,
            'room_number' => 'B-201',
            'floor' => 2,
            'room_type' => 'Deluxe',
            'price' => 4500,
            'status' => 'vacant',
        ]);

        $response = $this->withSession(['tenant_id' => $tenantA->id])->get('/app/rooms');

        $response->assertOk();
        $response->assertSee('A-101');
        $response->assertDontSee('B-201');
    }
}
