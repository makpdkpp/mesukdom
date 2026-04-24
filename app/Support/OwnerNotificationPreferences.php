<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\PlatformSetting;
use App\Models\Tenant;

final class OwnerNotificationPreferences
{
    public const EVENTS = [
        'payment_received',
        'utility_reminder_day',
        'invoice_create_day',
        'invoice_send_day',
        'overdue_digest',
    ];

    /**
     * Cascade resolver: tenant override (if not null) > platform default > true.
     */
    public static function enabled(Tenant $tenant, string $event): bool
    {
        if (! in_array($event, self::EVENTS, true)) {
            return false;
        }

        $tenantColumn = 'notify_owner_'.$event;
        $defaultColumn = 'default_notify_owner_'.$event;

        $tenantValue = $tenant->getAttribute($tenantColumn);

        if ($tenantValue !== null) {
            return (bool) $tenantValue;
        }

        $platform = PlatformSetting::current();
        $defaultValue = $platform->getAttribute($defaultColumn);

        return $defaultValue === null ? true : (bool) $defaultValue;
    }

    /**
     * Cascade resolver for channels. Returns list like ['line'], ['email'], or ['line','email'].
     *
     * @return list<string>
     */
    public static function channels(Tenant $tenant): array
    {
        $tenantValue = $tenant->getAttribute('notify_owner_channels');

        if ($tenantValue === null || $tenantValue === '') {
            $platform = PlatformSetting::current();
            $tenantValue = (string) ($platform->getAttribute('default_notify_owner_channels') ?? 'line');
        }

        return self::normalize((string) $tenantValue);
    }

    /**
     * @return list<string>
     */
    private static function normalize(string $value): array
    {
        $value = strtolower(trim($value));

        if ($value === 'both') {
            return ['line', 'email'];
        }

        if (in_array($value, ['line', 'email'], true)) {
            return [$value];
        }

        return ['line'];
    }
}
