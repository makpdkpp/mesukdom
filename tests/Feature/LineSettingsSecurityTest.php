<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\PlatformSetting;
use App\Models\Tenant;
use App\Models\User;
use App\Services\Line\LineService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

final class LineSettingsSecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_settings_update_encrypts_line_credentials_and_persists_webhook_url(): void
    {
        $tenant = Tenant::query()->create([
            'name' => 'Secure Dorm',
            'domain' => 'secure.local',
            'plan' => 'trial',
            'status' => 'active',
        ]);

        $user = User::factory()->create([
            'tenant_id' => $tenant->id,
            'role' => 'owner',
            'email_verified_at' => now(),
        ]);

        $this->actingAs($user)
            ->withSession(['tenant_id' => $tenant->id])
            ->post(route('app.settings.update'), [
                'line_channel_id' => '2001001001',
                'line_basic_id' => 'secure-dorm',
                'line_channel_access_token' => 'tenant-secret-token',
                'line_channel_secret' => 'tenant-secret-value',
                'support_contact_name' => 'Owner Name',
            ])
            ->assertRedirect();

        $tenant->refresh();

        $this->assertSame('tenant-secret-token', $tenant->line_channel_access_token);
        $this->assertSame('tenant-secret-value', $tenant->line_channel_secret);
        $this->assertSame('secure-dorm', $tenant->line_basic_id);
        $this->assertSame('https://line.me/R/ti/p/@secure-dorm', $tenant->lineAddFriendUrl());
        $this->assertSame(route('api.line.webhook'), $tenant->line_webhook_url);

        $stored = DB::table('tenants')->where('id', $tenant->id)->first();

        self::assertNotNull($stored);

        $this->assertNotSame('tenant-secret-token', $stored->line_channel_access_token);
        $this->assertNotSame('tenant-secret-value', $stored->line_channel_secret);
    }

    public function test_owner_can_sync_line_rich_menu_and_store_rich_menu_id(): void
    {
        Http::fake([
            'https://api.line.me/v2/bot/richmenu' => Http::response(['richMenuId' => 'richmenu-123'], 200),
            'https://api-data.line.me/v2/bot/richmenu/*/content' => Http::response([], 200),
            'https://api.line.me/v2/bot/user/all/richmenu/*' => Http::response([], 200),
            'https://api.line.me/v2/bot/richmenu/*' => Http::response([], 200),
        ]);

        $tenant = Tenant::query()->create([
            'name' => 'Rich Dorm',
            'domain' => 'rich.local',
            'plan' => 'trial',
            'status' => 'active',
            'line_channel_access_token' => 'rich-token',
            'line_channel_secret' => 'rich-secret',
        ]);

        $user = User::factory()->create([
            'tenant_id' => $tenant->id,
            'role' => 'owner',
            'email_verified_at' => now(),
        ]);

        $this->actingAs($user)
            ->withSession(['tenant_id' => $tenant->id])
            ->post(route('app.settings.line-rich-menu.sync'))
            ->assertRedirect()
            ->assertSessionHas('status');

        $tenant->refresh();

        $this->assertSame('richmenu-123', $tenant->line_rich_menu_id);

        Http::assertSentCount(3);
    }

    public function test_owner_can_open_settings_with_legacy_plaintext_line_credentials_and_reencrypt_on_save(): void
    {
        DB::table('tenants')->insert([
            'name' => 'Legacy Dorm',
            'domain' => 'legacy.local',
            'plan' => 'trial',
            'status' => 'active',
            'line_channel_access_token' => 'legacy-plain-token',
            'line_channel_secret' => 'legacy-plain-secret',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $tenant = Tenant::query()->where('domain', 'legacy.local')->firstOrFail();

        $user = User::factory()->create([
            'tenant_id' => $tenant->id,
            'role' => 'owner',
            'email_verified_at' => now(),
        ]);

        $this->actingAs($user)
            ->withSession(['tenant_id' => $tenant->id])
            ->get(route('app.settings'))
            ->assertOk()
            ->assertSee('legacy-plain-token', false)
            ->assertSee('legacy-plain-secret', false);

        $this->actingAs($user)
            ->withSession(['tenant_id' => $tenant->id])
            ->post(route('app.settings.update'), [
                'line_channel_id' => 'legacy-channel',
                'line_channel_access_token' => 'legacy-plain-token',
                'line_channel_secret' => 'legacy-plain-secret',
            ])
            ->assertRedirect();

        $tenant->refresh();

        $this->assertSame('legacy-plain-token', $tenant->line_channel_access_token);
        $this->assertSame('legacy-plain-secret', $tenant->line_channel_secret);

        $stored = DB::table('tenants')->where('id', $tenant->id)->first();

        self::assertNotNull($stored);
        $this->assertNotSame('legacy-plain-token', $stored->line_channel_access_token);
        $this->assertNotSame('legacy-plain-secret', $stored->line_channel_secret);
    }

    public function test_owner_settings_update_preserves_existing_line_credentials_when_fields_are_omitted(): void
    {
        $tenant = Tenant::query()->create([
            'name' => 'Preserve Dorm',
            'domain' => 'preserve.local',
            'plan' => 'trial',
            'status' => 'active',
            'line_channel_access_token' => 'existing-tenant-token',
            'line_channel_secret' => 'existing-tenant-secret',
        ]);

        $user = User::factory()->create([
            'tenant_id' => $tenant->id,
            'role' => 'owner',
            'email_verified_at' => now(),
        ]);

        $this->actingAs($user)
            ->withSession(['tenant_id' => $tenant->id])
            ->post(route('app.settings.update'), [
                'support_contact_name' => 'Still Owner',
            ])
            ->assertRedirect();

        $tenant->refresh();

        $this->assertSame('existing-tenant-token', $tenant->line_channel_access_token);
        $this->assertSame('existing-tenant-secret', $tenant->line_channel_secret);
    }

    public function test_owner_link_expiry_is_displayed_in_application_timezone(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 4, 24, 14, 3, 0, 'Asia/Bangkok'));

        $tenant = Tenant::query()->create([
            'name' => 'Timezone Dorm',
            'domain' => 'timezone.local',
            'plan' => 'trial',
            'status' => 'active',
        ]);

        $user = User::factory()->create([
            'tenant_id' => $tenant->id,
            'role' => 'owner',
            'email_verified_at' => now(),
        ]);

        $response = $this->actingAs($user)
            ->withSession(['tenant_id' => $tenant->id])
            ->post(route('app.owner-line.link-token'));

        $response->assertRedirect();
        $response->assertSessionHas('owner_line_link', function (array $link): bool {
            return $link['expires_at'] === '24/04/2026 14:33';
        });
    }

    public function test_line_service_does_not_fallback_to_env_token_when_tenant_token_is_missing(): void
    {
        config()->set('services.line.channel_access_token', 'fallback-token');
        Http::fake();

        $tenant = Tenant::query()->create([
            'name' => 'No Token Dorm',
            'domain' => 'no-token.local',
            'plan' => 'trial',
            'status' => 'active',
        ]);

        $result = app(LineService::class)->pushText($tenant, 'U-target', 'hello');

        self::assertSame('skipped', $result['status']);
        Http::assertNothingSent();
    }

    public function test_owner_line_user_id_is_encrypted_at_rest(): void
    {
        $user = User::factory()->create([
            'role' => 'owner',
            'line_user_id' => 'U-secure-owner',
            'line_user_id_hash' => hash('sha256', 'U-secure-owner'),
            'line_linked_at' => now(),
        ]);

        $stored = DB::table('users')->where('id', $user->id)->first();
        self::assertNotNull($stored);

        self::assertNotSame('U-secure-owner', $stored->line_user_id);
        $user->refresh();
        self::assertSame('U-secure-owner', $user->line_user_id);
    }

    public function test_settings_update_creates_audit_log(): void
    {
        $tenant = Tenant::query()->create([
            'name' => 'Audit Dorm',
            'domain' => 'audit.local',
            'plan' => 'trial',
            'status' => 'active',
        ]);

        $user = User::factory()->create([
            'tenant_id' => $tenant->id,
            'role' => 'owner',
            'email_verified_at' => now(),
        ]);

        $this->actingAs($user)
            ->withSession(['tenant_id' => $tenant->id])
            ->post(route('app.settings.update'), [
                'support_contact_name' => 'Audit Name',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('notification_logs', [
            'tenant_id' => $tenant->id,
            'channel' => 'audit',
            'event' => 'setting_changed',
            'target' => 'tenant_settings',
        ]);
    }
}
