<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class AdminApiMonitorTest extends TestCase
{
    use RefreshDatabase;

    public function test_support_admin_can_access_api_monitor_page(): void
    {
        $admin = User::factory()->create([
            'role' => 'support_admin',
        ]);

        $response = $this->actingAs($admin)->get('/admin/api-monitor');

        $response->assertOk();
        $response->assertSeeText('API Monitoring Overview');
        $response->assertSeeText('Request Trend');
        $response->assertSeeText('Monitored Endpoint Charts');
        $response->assertSeeText('Endpoint Metrics');
        $response->assertSeeText('Prometheus Ready');
        $response->assertSeeText('api.line.webhook');
        $response->assertSeeText('app.payments.recheck-slip');
        $response->assertSeeText('API Monitor');
    }

    public function test_owner_cannot_access_api_monitor_page(): void
    {
        $tenant = Tenant::query()->create([
            'name' => 'Owner Dorm',
            'domain' => 'owner-monitor.local',
            'plan' => 'trial',
            'status' => 'active',
        ]);

        $owner = User::factory()->create([
            'tenant_id' => $tenant->id,
            'role' => 'owner',
        ]);

        $response = $this->actingAs($owner)->get('/admin/api-monitor');

        $response->assertForbidden();
    }
}
