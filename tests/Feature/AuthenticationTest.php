<?php

namespace Tests\Feature;

use App\Models\Room;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_screen_can_be_rendered(): void
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
    }

    public function test_users_can_authenticate_using_the_login_screen(): void
    {
        $user = User::factory()->create();

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect('/app/dashboard');
    }

    public function test_users_can_not_authenticate_with_invalid_password(): void
    {
        $user = User::factory()->create();

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
        ]);

        $this->assertGuest();
    }

    public function test_super_admin_users_are_redirected_to_admin_dashboard_after_login(): void
    {
        $user = User::factory()->create([
            'role' => 'super_admin',
            'email_verified_at' => now(),
        ]);

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect('/admin');
    }

    public function test_super_admin_users_do_not_follow_tenant_intended_url_after_login(): void
    {
        $user = User::factory()->create([
            'role' => 'super_admin',
            'email_verified_at' => now(),
        ]);

        $response = $this->withSession(['url.intended' => '/app/dashboard'])
            ->post('/login', [
                'email' => $user->email,
                'password' => 'password',
            ]);

        $this->assertAuthenticated();
        $response->assertRedirect('/admin');
    }

    public function test_owner_users_are_redirected_to_tenant_dashboard_after_login(): void
    {
        $tenant = Tenant::create([
            'name' => 'Redirect Dorm',
            'domain' => 'redirect.local',
            'plan' => 'trial',
            'status' => 'active',
        ]);

        $user = User::factory()->create([
            'tenant_id' => $tenant->id,
            'role' => 'owner',
            'email_verified_at' => now(),
        ]);

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect('/app/dashboard');
    }

    public function test_owner_users_do_not_follow_admin_intended_url_after_login(): void
    {
        $tenant = Tenant::create([
            'name' => 'Redirect Dorm',
            'domain' => 'redirect-owner.local',
            'plan' => 'trial',
            'status' => 'active',
        ]);

        $user = User::factory()->create([
            'tenant_id' => $tenant->id,
            'role' => 'owner',
            'email_verified_at' => now(),
        ]);

        $response = $this->withSession(['url.intended' => '/admin'])
            ->post('/login', [
                'email' => $user->email,
                'password' => 'password',
            ]);

        $this->assertAuthenticated();
        $response->assertRedirect('/app/dashboard');
    }

    public function test_tenant_dashboard_uses_authenticated_users_tenant_context(): void
    {
        $tenant = Tenant::create([
            'name' => 'Context Dorm',
            'domain' => 'context.local',
            'plan' => 'trial',
            'status' => 'active',
        ]);

        $user = User::factory()->create([
            'tenant_id' => $tenant->id,
            'role' => 'owner',
            'email_verified_at' => now(),
        ]);

        $response = $this->actingAs($user)->get('/app/dashboard');

        $response->assertOk();
        $response->assertSeeText('Revenue Trend');
        $response->assertSessionHas('tenant_id', $tenant->id);
    }

    public function test_room_status_page_displays_room_cards_for_all_statuses_by_default(): void
    {
        $tenant = Tenant::create([
            'name' => 'Vacancy Dorm',
            'domain' => 'vacancy.local',
            'plan' => 'trial',
            'status' => 'active',
        ]);

        $user = User::factory()->create([
            'tenant_id' => $tenant->id,
            'role' => 'owner',
            'email_verified_at' => now(),
        ]);

        Room::create([
            'tenant_id' => $tenant->id,
            'room_number' => '101',
            'floor' => 1,
            'room_type' => 'Standard',
            'price' => 3500,
            'status' => 'vacant',
        ]);

        Room::create([
            'tenant_id' => $tenant->id,
            'room_number' => '102',
            'floor' => 1,
            'room_type' => 'Standard',
            'price' => 3500,
            'status' => 'occupied',
        ]);

        Room::create([
            'tenant_id' => $tenant->id,
            'room_number' => '103',
            'floor' => 1,
            'room_type' => 'Standard',
            'price' => 3500,
            'status' => 'maintenance',
        ]);

        $response = $this->actingAs($user)->get('/app/room-status');

        $response->assertOk();
        $response->assertSeeText('Dormitory Dashboard');
        $response->assertSeeText('Room Status for '.$tenant->name);
        $response->assertSeeText('ห้อง 101');
        $response->assertSeeText('ห้อง 102');
        $response->assertSeeText('ห้อง 103');
        $response->assertSeeText('ว่าง');
        $response->assertSeeText('ไม่ว่าง');
        $response->assertSeeText('กำลังปรับปรุง');
        $response->assertSeeText('ใช้ตัวกรองเพื่อสลับดูห้องทั้งหมด, ห้องว่าง, หรือห้องที่ไม่ว่าง');
    }

    public function test_room_status_page_can_filter_vacant_and_unavailable_rooms(): void
    {
        $tenant = Tenant::create([
            'name' => 'Filter Dorm',
            'domain' => 'filter.local',
            'plan' => 'trial',
            'status' => 'active',
        ]);

        $user = User::factory()->create([
            'tenant_id' => $tenant->id,
            'role' => 'owner',
            'email_verified_at' => now(),
        ]);

        Room::create([
            'tenant_id' => $tenant->id,
            'room_number' => '201',
            'floor' => 2,
            'room_type' => 'Standard',
            'price' => 4200,
            'status' => 'vacant',
        ]);

        Room::create([
            'tenant_id' => $tenant->id,
            'room_number' => '202',
            'floor' => 2,
            'room_type' => 'Standard',
            'price' => 4200,
            'status' => 'occupied',
        ]);

        Room::create([
            'tenant_id' => $tenant->id,
            'room_number' => '203',
            'floor' => 2,
            'room_type' => 'Standard',
            'price' => 4200,
            'status' => 'maintenance',
        ]);

        $vacantResponse = $this->actingAs($user)->get('/app/room-status?room_status=vacant');
        $vacantResponse->assertOk();
        $vacantResponse->assertSeeText('ห้อง 201');
        $vacantResponse->assertDontSeeText('ห้อง 202');
        $vacantResponse->assertDontSeeText('ห้อง 203');

        $unavailableResponse = $this->actingAs($user)->get('/app/room-status?room_status=unavailable');
        $unavailableResponse->assertOk();
        $unavailableResponse->assertDontSeeText('ห้อง 201');
        $unavailableResponse->assertSeeText('ห้อง 202');
        $unavailableResponse->assertSeeText('ห้อง 203');
    }

    public function test_app_sidebar_shows_room_status_menu_after_dashboard(): void
    {
        $tenant = Tenant::create([
            'name' => 'Menu Dorm',
            'domain' => 'menu.local',
            'plan' => 'trial',
            'status' => 'active',
        ]);

        $user = User::factory()->create([
            'tenant_id' => $tenant->id,
            'role' => 'owner',
            'email_verified_at' => now(),
        ]);

        $response = $this->actingAs($user)->get('/app/dashboard');

        $response->assertOk();
        $response->assertSee(route('app.room-status'), false);
        $response->assertSeeText('Room Status');
        $response->assertSee(route('app.buildings'), false);
        $response->assertSeeText('Building');
    }
}
