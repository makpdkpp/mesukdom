<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;

class Plan extends Model
{
    private const CUSTOM_ROOM_PRICING_MODE = 'per_room';
    private const CUSTOM_MINIMUM_ROOM_COUNT = 10;

    protected $fillable = [
        'name',
        'slug',
        'price_monthly',
        'price_yearly',
        'price_prepaid_annual',
        'stripe_price_id',
        'stripe_product_id',
        'stripe_yearly_price_id',
        'stripe_prepaid_annual_price_id',
        'description',
        'limits',
        'is_active',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'price_monthly' => 'decimal:2',
            'price_yearly' => 'decimal:2',
            'price_prepaid_annual' => 'decimal:2',
            'limits' => 'array',
            'is_active' => 'boolean',
        ];
    }

    public function yearlyPriceAmount(): float
    {
        if ($this->price_yearly !== null) {
            return (float) $this->price_yearly;
        }

        return (float) $this->price_monthly * 12;
    }

    public function prepaidAnnualPriceAmount(): float
    {
        if ($this->price_prepaid_annual !== null) {
            return (float) $this->price_prepaid_annual;
        }

        return $this->yearlyPriceAmount();
    }

    public function stripeSubscriptionAnnualPriceId(): ?string
    {
        return $this->stripe_yearly_price_id ?: $this->stripe_price_id;
    }

    public function stripePrepaidAnnualPriceId(): ?string
    {
        return $this->stripe_prepaid_annual_price_id ?: null;
    }

    public function supportsSlipOk(): bool
    {
        return (bool) Arr::get($this->limitsArray(), 'slipok_enabled', false);
    }

    public function usesCustomRoomPricing(): bool
    {
        return Arr::get($this->limitsArray(), 'pricing_mode') === self::CUSTOM_ROOM_PRICING_MODE;
    }

    public function roomPriceMonthly(): float
    {
        $value = Arr::get($this->limitsArray(), 'room_price_monthly');

        if (is_numeric($value)) {
            return max(0, (float) $value);
        }

        return max(0, (float) $this->price_monthly);
    }

    public function minimumRoomCount(): int
    {
        return $this->usesCustomRoomPricing()
            ? self::CUSTOM_MINIMUM_ROOM_COUNT
            : 1;
    }

    public function normalizedRoomCount(int $roomCount): int
    {
        return max($this->minimumRoomCount(), $roomCount);
    }

    public function slipAddonPriceMonthly(): float
    {
        $value = Arr::get($this->limitsArray(), 'slipok_addon_price_monthly', 0);

        return max(0, is_numeric($value) ? (float) $value : 0);
    }

    public function slipAddonRightsPerRoom(): int
    {
        $value = Arr::get($this->limitsArray(), 'slipok_rights_per_room', 3);

        return max(0, is_numeric($value) ? (int) $value : 3);
    }

    public function computedMonthlyPriceFor(int $roomCount, bool $withSlipAddon = false): float
    {
        if (! $this->usesCustomRoomPricing()) {
            return (float) $this->price_monthly;
        }

        $normalizedRoomCount = $this->normalizedRoomCount($roomCount);
        $total = $this->roomPriceMonthly() * $normalizedRoomCount;

        if ($withSlipAddon && $this->supportsSlipOk()) {
            $total += $this->slipAddonPriceMonthly() * $normalizedRoomCount;
        }

        return $total;
    }

    public function computedBillingPriceFor(string $billingOption, int $roomCount = 1, bool $withSlipAddon = false): float
    {
        if (! $this->usesCustomRoomPricing()) {
            return match ($billingOption) {
                'prepaid_annual' => $this->prepaidAnnualPriceAmount(),
                'subscription_annual', 'subscription_yearly' => $this->yearlyPriceAmount(),
                default => (float) $this->price_monthly,
            };
        }

        $monthlyTotal = $this->computedMonthlyPriceFor($roomCount, $withSlipAddon);

        return match ($billingOption) {
            'prepaid_annual', 'subscription_annual', 'subscription_yearly' => $monthlyTotal * 12,
            default => $monthlyTotal,
        };
    }

    public function slipOkMonthlyLimit(): int
    {
        $value = Arr::get($this->limitsArray(), 'slipok_monthly_limit', 0);

        return max(0, is_numeric($value) ? (int) $value : 0);
    }

    public function roomsLimit(): int
    {
        $value = Arr::get($this->limitsArray(), 'rooms', 0);

        return max(0, is_numeric($value) ? (int) $value : 0);
    }

    public function isRecommended(): bool
    {
        return (bool) Arr::get($this->limitsArray(), 'recommended', false);
    }

    /**
     * @return array<string, string>
     */
    public function displayLimits(): array
    {
        $limits = $this->limitsArray();
        $display = [];

        if ($this->usesCustomRoomPricing()) {
            $display['Rooms'] = 'Minimum '.number_format($this->minimumRoomCount()).' rooms';
            $display['Room price'] = number_format($this->roomPriceMonthly(), 2).' THB / room / month';
            $display['Billing'] = 'Annual only';
        } elseif (array_key_exists('rooms', $limits)) {
            $display['Rooms'] = is_scalar($limits['rooms']) ? (string) $limits['rooms'] : '-';
        }

        if (array_key_exists('staff', $limits)) {
            $display['Staff'] = is_scalar($limits['staff']) ? (string) $limits['staff'] : '-';
        }

        if ($this->usesCustomRoomPricing()) {
            $display['SlipOK addon'] = $this->supportsSlipOk()
                ? number_format($this->slipAddonPriceMonthly(), 2).' THB / room / month'
                : 'Manual review only';
            $display['SlipOK rights'] = $this->supportsSlipOk()
                ? number_format($this->slipAddonRightsPerRoom()).' rights / room'
                : 'No automatic rights';
        } else {
            $display['SlipOK addon'] = $this->supportsSlipOk()
                ? ($this->slipOkMonthlyLimit() > 0
                    ? number_format($this->slipOkMonthlyLimit()).' verifications / month'
                    : 'Enabled')
                : 'Manual review only';
        }

        return $display;
    }

    /**
     * @return array<string, mixed>
     */
    private function limitsArray(): array
    {
        $limits = $this->getAttribute('limits');

        return is_array($limits) ? $limits : [];
    }
}
