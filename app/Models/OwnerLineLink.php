<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property ?int $tenant_id
 * @property int $user_id
 * @property string $scope
 * @property string $link_token
 * @property Carbon $expired_at
 * @property ?Carbon $used_at
 */
final class OwnerLineLink extends Model
{
    public const SCOPE_TENANT = 'tenant';
    public const SCOPE_PLATFORM = 'platform';

    protected $fillable = [
        'tenant_id',
        'user_id',
        'scope',
        'link_token',
        'expired_at',
        'used_at',
    ];

    protected function casts(): array
    {
        return [
            'expired_at' => 'datetime',
            'used_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Tenant, $this>
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isUsable(): bool
    {
        return $this->used_at === null && $this->expired_at->isFuture();
    }
}
