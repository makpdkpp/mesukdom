<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @use HasFactory<\Illuminate\Database\Eloquent\Factories\Factory<static>>
 */
final class PlatformCostSetting extends Model
{
    use HasFactory;

    public const TYPE_PER_UNIT = 'per_unit';
    public const TYPE_PERCENTAGE = 'percentage';
    public const TYPE_FIXED_MONTHLY = 'fixed_monthly';
    public const TYPE_HYBRID = 'hybrid';

    public const PROVIDER_SLIPOK = 'slipok';
    public const PROVIDER_STRIPE = 'stripe';
    public const PROVIDER_LINE = 'line';
    public const PROVIDER_EMAIL = 'email';
    public const PROVIDER_HOSTING = 'hosting';
    public const PROVIDER_STORAGE = 'storage';
    public const PROVIDER_SUPPORT_OPS = 'support_ops';

    /** @var list<string> */
    public const COST_TYPES = [
        self::TYPE_PER_UNIT,
        self::TYPE_PERCENTAGE,
        self::TYPE_FIXED_MONTHLY,
        self::TYPE_HYBRID,
    ];

    /** @var list<string> */
    public const PROVIDERS = [
        self::PROVIDER_SLIPOK,
        self::PROVIDER_STRIPE,
        self::PROVIDER_LINE,
        self::PROVIDER_EMAIL,
        self::PROVIDER_HOSTING,
        self::PROVIDER_STORAGE,
        self::PROVIDER_SUPPORT_OPS,
    ];

    protected $fillable = [
        'provider',
        'cost_type',
        'unit_cost',
        'percentage_rate',
        'fixed_fee',
        'included_quota',
        'overage_unit_cost',
        'currency',
        'effective_from',
        'effective_to',
        'is_active',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'unit_cost' => 'decimal:4',
            'percentage_rate' => 'decimal:4',
            'fixed_fee' => 'decimal:4',
            'included_quota' => 'integer',
            'overage_unit_cost' => 'decimal:4',
            'effective_from' => 'date',
            'effective_to' => 'date',
            'is_active' => 'boolean',
        ];
    }

    /**
     * @param Builder<PlatformCostSetting> $query
     * @return Builder<PlatformCostSetting>
     */
    public function scopeActiveForDate(Builder $query, \DateTimeInterface $date): Builder
    {
        return $query
            ->where('is_active', true)
            ->whereDate('effective_from', '<=', $date)
            ->where(function (Builder $query) use ($date): void {
                $query->whereNull('effective_to')->orWhereDate('effective_to', '>=', $date);
            });
    }

    public function providerLabel(): string
    {
        return match ($this->provider) {
            self::PROVIDER_SLIPOK => 'SlipOK API',
            self::PROVIDER_STRIPE => 'Stripe Fees',
            self::PROVIDER_LINE => 'LINE Messaging',
            self::PROVIDER_EMAIL => 'Email Delivery',
            self::PROVIDER_HOSTING => 'Hosting',
            self::PROVIDER_STORAGE => 'Storage & Backup',
            self::PROVIDER_SUPPORT_OPS => 'Support Ops',
            default => str((string) $this->provider)->headline()->toString(),
        };
    }
}