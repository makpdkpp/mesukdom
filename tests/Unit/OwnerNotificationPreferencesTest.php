<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\PlatformSetting;
use App\Models\Tenant;
use App\Support\OwnerNotificationPreferences;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class OwnerNotificationPreferencesTest extends TestCase
{
    use RefreshDatabase;

    public function test_enabled_uses_platform_default_when_tenant_override_is_null(): void
    {
        PlatformSetting::current()->update([
            'default_notify_owner_payment_received' => true,
        ]);

        $tenant = Tenant::factory()->create([
            'notify_owner_payment_received' => null,
        ]);

        self::assertTrue(OwnerNotificationPreferences::enabled($tenant, 'payment_received'));
    }

    public function test_enabled_allows_tenant_override_to_disable_default_true(): void
    {
        PlatformSetting::current()->update([
            'default_notify_owner_payment_received' => true,
        ]);

        $tenant = Tenant::factory()->create([
            'notify_owner_payment_received' => false,
        ]);

        self::assertFalse(OwnerNotificationPreferences::enabled($tenant, 'payment_received'));
    }

    public function test_channels_fall_back_to_platform_default(): void
    {
        PlatformSetting::current()->update([
            'default_notify_owner_channels' => 'both',
        ]);

        $tenant = Tenant::factory()->create([
            'notify_owner_channels' => null,
        ]);

        self::assertSame(['line', 'email'], OwnerNotificationPreferences::channels($tenant));
    }
}