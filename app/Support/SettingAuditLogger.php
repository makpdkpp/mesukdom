<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\NotificationLog;
use App\Models\User;

final class SettingAuditLogger
{
    /**
     * @param array<string,mixed> $before
     * @param array<string,mixed> $after
     */
    public static function log(string $scope, ?int $tenantId, ?User $actor, array $before, array $after): void
    {
        $diff = [];

        foreach ($after as $key => $value) {
            $beforeValue = $before[$key] ?? null;

            if ($beforeValue !== $value) {
                $diff[$key] = ['before' => $beforeValue, 'after' => $value];
            }
        }

        if ($diff === []) {
            return;
        }

        NotificationLog::query()->create([
            'tenant_id' => $tenantId,
            'channel' => 'audit',
            'event' => 'setting_changed',
            'target' => $scope,
            'message' => 'Settings updated: '.$scope,
            'status' => 'changed',
            'payload' => [
                'scope' => $scope,
                'actor_id' => $actor?->id,
                'diff' => $diff,
            ],
        ]);
    }
}