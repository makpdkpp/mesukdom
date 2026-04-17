<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\CustomerLineLink;
use App\Models\Room;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

final class ResidentLineLinkPortalTest extends TestCase
{
    use RefreshDatabase;

    public function test_signed_link_page_binds_customer_using_line_user_and_token(): void
    {
        $tenant = Tenant::query()->create([
            'name' => 'Portal Dorm',
            'status' => 'active',
        ]);

        $room = Room::withoutGlobalScopes()->create([
            'tenant_id' => $tenant->id,
            'room_number' => 'P-101',
            'floor' => 1,
            'room_type' => 'Standard',
            'price' => 4500,
            'status' => 'occupied',
        ]);

        $customer = Customer::query()->create([
            'tenant_id' => $tenant->id,
            'room_id' => $room->id,
            'name' => 'Portal Resident',
        ]);

        CustomerLineLink::query()->create([
            'tenant_id' => $tenant->id,
            'customer_id' => $customer->id,
            'link_token' => 'ABC123',
            'expired_at' => now()->addHour(),
        ]);

        $url = URL::temporarySignedRoute(
            'resident.line.link.store',
            now()->addHour(),
            ['tenant' => $tenant->id, 'line_user_id' => 'U-portal-user']
        );

        $this->post($url, ['link_token' => 'ABC123'])
            ->assertOk()
            ->assertSeeText('เชื่อม LINE สำเร็จแล้ว')
            ->assertSeeText('Portal Resident');

        $this->assertDatabaseHas('customers', [
            'id' => $customer->id,
            'line_user_id' => 'U-portal-user',
        ]);

        $this->assertDatabaseHas('customer_line_links', [
            'tenant_id' => $tenant->id,
            'customer_id' => $customer->id,
            'link_token' => 'ABC123',
        ]);
    }
}
