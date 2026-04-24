<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Jobs\ProcessPlatformWebhookEventJob;
use App\Jobs\SendPlatformLineMessageJob;
use App\Models\OwnerLineLink;
use App\Models\PlatformSetting;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

final class PlatformLineFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_platform_webhook_rejects_invalid_signature(): void
    {
        PlatformSetting::current()->update([
            'platform_line_channel_secret' => 'platform-secret',
        ]);

        $response = $this->call(
            'POST',
            '/api/line/platform-webhook',
            [],
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_X_LINE_SIGNATURE' => 'bad-signature',
            ],
            json_encode(['events' => []], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        );

        $response->assertStatus(401);
    }

    public function test_platform_webhook_dispatches_job_with_valid_signature(): void
    {
        Queue::fake();

        PlatformSetting::current()->update([
            'platform_line_channel_secret' => 'platform-secret',
        ]);

        $payload = ['events' => [[
            'type' => 'message',
            'replyToken' => 'reply-platform',
            'source' => ['userId' => 'U-platform'],
            'message' => ['type' => 'text', 'text' => 'ADMIN:ABC123'],
        ]]];

        $json = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        self::assertNotFalse($json);
        $signature = base64_encode(hash_hmac('sha256', $json, 'platform-secret', true));

        $this->call(
            'POST',
            '/api/line/platform-webhook',
            [],
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_X_LINE_SIGNATURE' => $signature,
            ],
            $json,
        )->assertOk();

        Queue::assertPushed(ProcessPlatformWebhookEventJob::class);
    }

    public function test_admin_can_queue_platform_broadcast(): void
    {
        Queue::fake();

        $tenant = Tenant::factory()->create(['status' => 'active']);
        $admin = User::factory()->create([
            'role' => 'super_admin',
            'email_verified_at' => now(),
        ]);

        User::factory()->create([
            'tenant_id' => $tenant->id,
            'role' => 'owner',
            'platform_line_user_id' => 'U-platform-owner',
            'platform_line_user_id_hash' => hash('sha256', 'U-platform-owner'),
            'platform_line_linked_at' => now(),
        ]);

        PlatformSetting::current()->update([
            'platform_line_owner_broadcast_enabled' => true,
        ]);

        $this->actingAs($admin)
            ->post(route('admin.platform-line.broadcast'), [
                'message' => 'Platform notice',
                'recipient_filter' => 'all',
            ])
            ->assertRedirect();

        Queue::assertPushed(SendPlatformLineMessageJob::class, 1);
    }

    public function test_platform_owner_link_is_consumed(): void
    {
        PlatformSetting::current()->update([
            'platform_line_channel_secret' => 'platform-secret',
        ]);

        $admin = User::factory()->create([
            'role' => 'super_admin',
            'email_verified_at' => now(),
        ]);

        OwnerLineLink::query()->create([
            'tenant_id' => null,
            'user_id' => $admin->id,
            'scope' => OwnerLineLink::SCOPE_PLATFORM,
            'link_token' => 'ABC123',
            'expired_at' => now()->addMinutes(30),
        ]);

        $job = new ProcessPlatformWebhookEventJob([
            'type' => 'message',
            'replyToken' => 'reply-platform-consume',
            'source' => ['userId' => 'U-platform-linked'],
            'message' => ['type' => 'text', 'text' => 'ADMIN:ABC123'],
        ]);

        app()->call([$job, 'handle']);

        $admin->refresh();

        self::assertSame('U-platform-linked', $admin->platform_line_user_id);
        $this->assertDatabaseHas('notification_logs', [
            'channel' => 'line:platform',
            'status' => 'platform_linked',
        ]);
    }
}