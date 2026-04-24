<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\OwnerLineLink;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;

final class OwnerLineLinkService
{
    private const TOKEN_ALPHABET = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
    private const TOKEN_LENGTH = 6;
    public const TTL_MINUTES = 30;

    /**
     * Issue a fresh owner-link token. Invalidates any unused active token for the same user+scope.
     */
    public function createForUser(User $owner, string $scope): OwnerLineLink
    {
        $scope = $this->normalizeScope($scope);

        return DB::transaction(function () use ($owner, $scope): OwnerLineLink {
            OwnerLineLink::query()
                ->where('user_id', $owner->id)
                ->where('scope', $scope)
                ->whereNull('used_at')
                ->update(['expired_at' => now()->subSecond()]);

            return OwnerLineLink::query()->create([
                'tenant_id' => $scope === OwnerLineLink::SCOPE_TENANT ? $owner->tenant_id : null,
                'user_id' => $owner->id,
                'scope' => $scope,
                'link_token' => $this->generateUniqueToken($scope),
                'expired_at' => now()->addMinutes(self::TTL_MINUTES),
            ]);
        });
    }

    public function findConsumable(string $scope, string $token, ?int $tenantId = null): ?OwnerLineLink
    {
        $scope = $this->normalizeScope($scope);
        $token = strtoupper(trim($token));

        if ($token === '') {
            return null;
        }

        $query = OwnerLineLink::query()
            ->where('scope', $scope)
            ->where('link_token', $token)
            ->whereNull('used_at')
            ->where('expired_at', '>', now());

        if ($tenantId !== null) {
            $query->where('tenant_id', $tenantId);
        }

        return $query->first();
    }

    /**
     * Consume a token + bind LINE user id on the user. Returns null on invalid/expired.
     */
    public function consume(string $scope, string $token, string $lineUserId, ?int $tenantId = null): ?OwnerLineLink
    {
        $scope = $this->normalizeScope($scope);
        $token = strtoupper(trim($token));

        if ($token === '' || $lineUserId === '') {
            return null;
        }

        if ($this->tooManyAttempts($scope, $token)) {
            return null;
        }

        return DB::transaction(function () use ($scope, $token, $lineUserId, $tenantId): ?OwnerLineLink {
            /** @var ?OwnerLineLink $link */
            $query = OwnerLineLink::query()
                ->where('scope', $scope)
                ->where('link_token', $token)
                ->whereNull('used_at')
                ->where('expired_at', '>', now());

            if ($tenantId !== null) {
                $query->where('tenant_id', $tenantId);
            }

            $link = $query
                ->lockForUpdate()
                ->first();

            if ($link === null) {
                return null;
            }

            /** @var ?User $owner */
            $owner = User::query()->lockForUpdate()->find($link->user_id);

            if ($owner === null) {
                return null;
            }

            $hash = hash('sha256', $lineUserId);

            if ($scope === OwnerLineLink::SCOPE_TENANT) {
                $owner->forceFill([
                    'line_user_id' => $lineUserId,
                    'line_user_id_hash' => $hash,
                    'line_linked_at' => now(),
                ])->save();
            } else {
                $owner->forceFill([
                    'platform_line_user_id' => $lineUserId,
                    'platform_line_user_id_hash' => $hash,
                    'platform_line_linked_at' => now(),
                ])->save();
            }

            $link->forceFill(['used_at' => now()])->save();

            return $link->fresh();
        });
    }

    public function unlink(User $owner, string $scope): void
    {
        $scope = $this->normalizeScope($scope);

        if ($scope === OwnerLineLink::SCOPE_TENANT) {
            $owner->forceFill([
                'line_user_id' => null,
                'line_user_id_hash' => null,
                'line_linked_at' => null,
            ])->save();
        } else {
            $owner->forceFill([
                'platform_line_user_id' => null,
                'platform_line_user_id_hash' => null,
                'platform_line_linked_at' => null,
            ])->save();
        }
    }

    public function findActiveTokenFor(User $owner, string $scope): ?OwnerLineLink
    {
        $scope = $this->normalizeScope($scope);

        return OwnerLineLink::query()
            ->where('user_id', $owner->id)
            ->where('scope', $scope)
            ->whereNull('used_at')
            ->where('expired_at', '>', now())
            ->latest('id')
            ->first();
    }

    public static function normalizeInboundToken(string $text): ?string
    {
        // Accept: "OWNER:ABC123", "OWNER ABC123", "ผูกเจ้าของ ABC123"
        if (! preg_match('/^(?:owner|admin|ผูกเจ้าของ|ผูกแอดมิน)\s*[:\-]?\s*([A-Z0-9]{'.self::TOKEN_LENGTH.',})$/iu', trim($text), $m)) {
            return null;
        }

        return strtoupper((string) $m[1]);
    }

    private function normalizeScope(string $scope): string
    {
        return $scope === OwnerLineLink::SCOPE_PLATFORM
            ? OwnerLineLink::SCOPE_PLATFORM
            : OwnerLineLink::SCOPE_TENANT;
    }

    private function generateUniqueToken(string $scope): string
    {
        do {
            $token = '';
            for ($i = 0; $i < self::TOKEN_LENGTH; $i++) {
                $token .= self::TOKEN_ALPHABET[random_int(0, strlen(self::TOKEN_ALPHABET) - 1)];
            }
        } while (OwnerLineLink::query()
            ->where('scope', $scope)
            ->where('link_token', $token)
            ->whereNull('used_at')
            ->exists());

        return $token;
    }

    private function tooManyAttempts(string $scope, string $token): bool
    {
        $cacheKey = sprintf('owner-link-attempts:%s:%s', $scope, $token);

        if (RateLimiter::tooManyAttempts($cacheKey, 5)) {
            return true;
        }

        RateLimiter::hit($cacheKey, 60);

        return false;
    }
}
