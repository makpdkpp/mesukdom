<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\LineMessage;
use App\Models\LineWebhookLog;
use App\Models\NotificationLog;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class LineActivityDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_view_line_activity_dashboard(): void
    {
        $tenant = Tenant::query()->create([
            'name' => 'Activity Dorm',
            'domain' => 'activity.local',
            'plan' => 'trial',
            'status' => 'active',
        ]);

        $customer = Customer::query()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Resident One',
            'line_user_id' => 'U-activity-001',
        ]);

        LineWebhookLog::query()->create([
            'tenant_id' => $tenant->id,
            'event_type' => 'postback',
            'line_user_id' => 'U-activity-001',
            'payload' => ['type' => 'postback', 'postback' => ['data' => 'action=invoice']],
        ]);

        LineMessage::query()->create([
            'tenant_id' => $tenant->id,
            'customer_id' => $customer->id,
            'direction' => 'outbound',
            'message_type' => 'text',
            'payload' => ['message' => 'บิลล่าสุด INV-0001'],
            'sent_at' => now(),
        ]);

        NotificationLog::query()->create([
            'tenant_id' => $tenant->id,
            'channel' => 'line',
            'event' => 'postback',
            'target' => 'U-activity-001',
            'message' => 'Resident requested latest invoice',
            'status' => 'replied',
            'payload' => ['command' => 'invoice'],
        ]);

        $user = User::factory()->create([
            'tenant_id' => $tenant->id,
            'role' => 'owner',
            'email_verified_at' => now(),
        ]);

        $this->actingAs($user)
            ->withSession(['tenant_id' => $tenant->id])
            ->get(route('app.line-activity'))
            ->assertOk()
            ->assertSee('Linked Residents')
            ->assertSee('Latest Webhook Events')
            ->assertSee('Recent LINE Messages')
            ->assertSee('LINE Delivery Log')
            ->assertSee('Resident requested latest invoice')
            ->assertSee('บิลล่าสุด INV-0001');
    }

    public function test_line_activity_dashboard_can_render_structured_message_payloads(): void
    {
        $tenant = Tenant::query()->create([
            'name' => 'Structured Activity Dorm',
            'domain' => 'structured-activity.local',
            'plan' => 'trial',
            'status' => 'active',
        ]);

        $user = User::factory()->create([
            'tenant_id' => $tenant->id,
            'role' => 'owner',
            'email_verified_at' => now(),
        ]);

        LineMessage::query()->create([
            'tenant_id' => $tenant->id,
            'direction' => 'outbound',
            'message_type' => 'template',
            'payload' => [
                'message' => [
                    'type' => 'template',
                    'altText' => 'ยืนยันห้องพัก',
                ],
            ],
            'sent_at' => now(),
        ]);

        $this->actingAs($user)
            ->withSession(['tenant_id' => $tenant->id])
            ->get(route('app.line-activity'))
            ->assertOk()
            ->assertSee('&quot;type&quot;:&quot;template&quot;', false)
            ->assertSee('&quot;altText&quot;:&quot;ยืนยันห้องพัก&quot;', false);
    }
}
