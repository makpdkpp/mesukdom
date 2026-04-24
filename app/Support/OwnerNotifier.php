<?php

declare(strict_types=1);

namespace App\Support;

use App\Jobs\SendLineMessageJob;
use App\Models\NotificationLog;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Carbon;

final class OwnerNotifier
{
    /**
     * Push a LINE notification to all owners of a tenant for a given event,
     * after checking the cascading preference and channel selection.
     *
     * @param  array<string, mixed>  $payload
     * @return int Number of jobs dispatched.
     */
    public static function pushLineToOwners(
        Tenant $tenant,
        string $event,
        string $message,
        array $payload = [],
        ?int $customerId = null,
        ?string $idempotencyKey = null,
    ): int {
        if (! OwnerNotificationPreferences::enabled($tenant, $event)) {
            return 0;
        }

        $channels = OwnerNotificationPreferences::channels($tenant);

        if (! in_array('line', $channels, true)) {
            return 0;
        }

        if ($idempotencyKey !== null && self::alreadySentToday($tenant->id, $event, $idempotencyKey)) {
            return 0;
        }

        $owners = User::query()
            ->where('tenant_id', $tenant->id)
            ->where('role', 'owner')
            ->whereNotNull('line_user_id')
            ->get(['id', 'line_user_id']);

        $count = 0;
        foreach ($owners->chunk(50) as $chunkIndex => $chunk) {
            foreach ($chunk as $owner) {
                SendLineMessageJob::dispatch(
                    $tenant->id,
                    'owner_'.$event,
                    $owner->line_user_id,
                    $message,
                    'user:'.$owner->id,
                    $customerId,
                    array_merge($payload, ['idempotency_key' => $idempotencyKey]),
                )->delay(now()->addSeconds($chunkIndex * 2));
                $count++;
            }
        }

        return $count;
    }

    private static function alreadySentToday(int $tenantId, string $event, string $idempotencyKey): bool
    {
        return NotificationLog::query()
            ->where('tenant_id', $tenantId)
            ->where('event', 'owner_'.$event)
            ->whereDate('created_at', Carbon::today()->toDateString())
            ->where('payload->idempotency_key', $idempotencyKey)
            ->exists();
    }
}
