<?php

declare(strict_types=1);

namespace App\Services\Business;

use App\Models\PlatformCostSetting;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

final class PlatformCostCalculator
{
    /**
     * @var Collection<int, PlatformCostSetting>|null
     */
    private ?Collection $settings = null;

    /**
     * @return Collection<int, PlatformCostSetting>
     */
    public function activeSettings(?Carbon $date = null): Collection
    {
        if ($this->settings !== null) {
            return $this->settings;
        }

        $date ??= now();

        return $this->settings = PlatformCostSetting::query()
            ->activeForDate($date)
            ->orderBy('provider')
            ->orderByDesc('effective_from')
            ->get();
    }

    /**
     * @return array{provider: string, label: string, amount: float, unit_count: int, transaction_count: int}
     */
    public function providerCost(string $provider, int $unitCount = 0, float $amount = 0.0, int $transactionCount = 0, ?Carbon $date = null): array
    {
        $settings = $this->activeSettings($date)->where('provider', $provider);
        $total = 0.0;
        $label = $this->providerLabel($provider);

        foreach ($settings as $setting) {
            $label = $setting->providerLabel();
            $total += $this->settingCost($setting, $unitCount, $amount, $transactionCount);
        }

        return [
            'provider' => $provider,
            'label' => $label,
            'amount' => round($total, 2),
            'unit_count' => $unitCount,
            'transaction_count' => $transactionCount,
        ];
    }

    public function fixedMonthlyCosts(?Carbon $date = null): float
    {
        return round($this->activeSettings($date)
            ->filter(fn (PlatformCostSetting $setting): bool => $setting->cost_type === PlatformCostSetting::TYPE_FIXED_MONTHLY)
            ->sum(fn (PlatformCostSetting $setting): float => (float) $setting->fixed_fee), 2);
    }

    private function settingCost(PlatformCostSetting $setting, int $unitCount, float $amount, int $transactionCount): float
    {
        return match ($setting->cost_type) {
            PlatformCostSetting::TYPE_PER_UNIT => $this->unitCost($setting, $unitCount),
            PlatformCostSetting::TYPE_PERCENTAGE => ($amount * ((float) $setting->percentage_rate / 100)) + ((float) $setting->fixed_fee * $transactionCount),
            PlatformCostSetting::TYPE_FIXED_MONTHLY => (float) $setting->fixed_fee,
            PlatformCostSetting::TYPE_HYBRID => $this->unitCost($setting, $unitCount) + ($amount * ((float) $setting->percentage_rate / 100)) + ((float) $setting->fixed_fee * $transactionCount),
            default => 0.0,
        };
    }

    private function unitCost(PlatformCostSetting $setting, int $unitCount): float
    {
        if ($unitCount <= 0) {
            return 0.0;
        }

        $includedQuota = max(0, (int) $setting->included_quota);
        $billableUnits = $includedQuota > 0 ? max(0, $unitCount - $includedQuota) : $unitCount;
        $unitCost = (float) $setting->unit_cost;

        if ($includedQuota > 0 && (float) $setting->overage_unit_cost > 0) {
            $unitCost = (float) $setting->overage_unit_cost;
        }

        return $billableUnits * $unitCost;
    }

    private function providerLabel(string $provider): string
    {
        return match ($provider) {
            PlatformCostSetting::PROVIDER_SLIPOK => 'Slip verification API',
            PlatformCostSetting::PROVIDER_STRIPE => 'Stripe Fees',
            PlatformCostSetting::PROVIDER_LINE => 'LINE Messaging',
            PlatformCostSetting::PROVIDER_EMAIL => 'Email Delivery',
            PlatformCostSetting::PROVIDER_HOSTING => 'Hosting',
            PlatformCostSetting::PROVIDER_STORAGE => 'Storage & Backup',
            PlatformCostSetting::PROVIDER_SUPPORT_OPS => 'Support Ops',
            default => str($provider)->headline()->toString(),
        };
    }
}