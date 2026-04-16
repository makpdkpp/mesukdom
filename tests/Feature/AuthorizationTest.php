<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthorizationTest extends TestCase
{
    use RefreshDatabase;

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
        $response->assertSeeText('System Monitoring & Notification Logs');
    }

    public function test_support_admin_cannot_access_tenant_portal_routes(): void
    {
        $admin = User::factory()->create([
            'role' => 'support_admin',
        ]);

        $response = $this->actingAs($admin)->get('/app/dashboard');

        $response->assertForbidden();
    }
}