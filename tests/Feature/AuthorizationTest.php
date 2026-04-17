<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_from_tenant_dashboard(): void
    {
        $response = $this->get('/app/dashboard');

        $response->assertRedirect('/login');
    }

    public function test_guest_is_redirected_from_admin_dashboard(): void
    {
        $response = $this->get('/admin');

        $response->assertRedirect('/login');
    }

    public function test_owner_cannot_access_admin_dashboard(): void
    {
        $tenant = Tenant::create([
            'name' => 'Owner Dorm',
            'domain' => 'owner.local',
            'plan' => 'trial',
            'status' => 'active',
        ]);

        $owner = User::factory()->create([
            'tenant_id' => $tenant->id,
            'role' => 'owner',
        ]);

        $response = $this->actingAs($owner)->get('/admin');

        $response->assertForbidden();
    }

    public function test_support_admin_can_access_admin_dashboard(): void
    {
        $admin = User::factory()->create([
            'role' => 'support_admin',
        ]);

        $response = $this->actingAs($admin)->get('/admin');

        $response->assertOk();
        $response->assertSeeText('Dashboard Admin');
        $response->assertSeeText('Total Tenants');
    }

    public function test_support_admin_sidebar_shows_only_admin_menu_items(): void
    {
        $admin = User::factory()->create([
            'role' => 'support_admin',
        ]);

        $response = $this->actingAs($admin)->get('/admin');

        $response->assertOk();
        $response->assertSeeText('Dashboard Admin');
        $response->assertSeeText('Platform Admin');
        $response->assertDontSeeText('Rooms');
        $response->assertDontSeeText('Residents');
        $response->assertDontSeeText('Contracts');
        $response->assertDontSeeText('Invoices');
        $response->assertDontSeeText('Payments');
        $response->assertDontSeeText('Broadcasts');
        $response->assertDontSeeText('Settings');
    }

    public function test_kpi_cards_are_only_visible_on_admin_dashboard(): void
    {
        $admin = User::factory()->create([
            'role' => 'support_admin',
        ]);

        $dashboardResponse = $this->actingAs($admin)->get('/admin');

        $dashboardResponse->assertOk();
        $dashboardResponse->assertSeeText('Total Tenants');
        $dashboardResponse->assertSeeText('Active Users');
        $dashboardResponse->assertSeeText('SaaS Revenue');
        $dashboardResponse->assertSeeText('SlipOK Calls This Month');

        $platformResponse = $this->actingAs($admin)->get('/admin/platform');

        $platformResponse->assertOk();
        $platformResponse->assertSeeText('Global SlipOK Settings');
        $platformResponse->assertDontSeeText('Total Tenants');
        $platformResponse->assertDontSeeText('Active Users');
        $platformResponse->assertDontSeeText('SaaS Revenue');
        $platformResponse->assertDontSeeText('SlipOK Calls This Month');
    }

    public function test_admin_dashboard_shows_required_system_monitoring_sections(): void
    {
        $admin = User::factory()->create([
            'role' => 'support_admin',
        ]);

        $response = $this->actingAs($admin)->get('/admin');

        $response->assertOk();
        $response->assertSeeText('Server Status');
        $response->assertSeeText('Queue Status');
        $response->assertSeeText('Failed Jobs');
        $response->assertSeeText('API Usage');
        $response->assertSeeText('Notification Logs');
        $response->assertSeeText('Payment Logs');
    }

    public function test_support_admin_cannot_access_tenant_portal_routes(): void
    {
        $admin = User::factory()->create([
            'role' => 'support_admin',
        ]);

        $response = $this->actingAs($admin)->get('/app/dashboard');

        $response->assertForbidden();
    }

    public function test_unverified_owner_is_redirected_to_email_verification_before_tenant_dashboard(): void
    {
        $tenant = Tenant::create([
            'name' => 'Unverified Dorm',
            'domain' => 'unverified.local',
            'plan' => 'trial',
            'status' => 'active',
        ]);

        $owner = User::factory()->unverified()->create([
            'tenant_id' => $tenant->id,
            'role' => 'owner',
        ]);

        $response = $this->actingAs($owner)->get('/app/dashboard');

        $response->assertRedirect('/email/verify');
    }
}