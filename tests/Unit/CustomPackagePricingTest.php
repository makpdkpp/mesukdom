<?php

namespace Tests\Unit;

use App\Models\Plan;
use App\Models\Tenant;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CustomPackagePricingTest extends TestCase
{
    #[Test]
    public function custom_room_pricing_plan_computes_monthly_and_yearly_totals(): void
    {
        $plan = new Plan([
            'price_monthly' => 0,
            'limits' => [
                'pricing_mode' => 'per_room',
                'room_price_monthly' => 120,
                'slipok_enabled' => true,
                'slipok_addon_price_monthly' => 30,
                'slipok_rights_per_room' => 3,
            ],
        ]);

        $this->assertTrue($plan->usesCustomRoomPricing());
        $this->assertSame(10, $plan->minimumRoomCount());
        $this->assertSame(120.0, $plan->roomPriceMonthly());
        $this->assertSame(30.0, $plan->slipAddonPriceMonthly());
        $this->assertSame(3, $plan->slipAddonRightsPerRoom());
        $this->assertSame(1200.0, $plan->computedMonthlyPriceFor(5));
        $this->assertSame(1500.0, $plan->computedMonthlyPriceFor(5, true));
        $this->assertSame(18000.0, $plan->computedBillingPriceFor('subscription_annual', 5, true));
    }

    #[Test]
    public function tenant_effective_limits_use_custom_package_overrides(): void
    {
        $plan = new Plan([
            'limits' => [
                'pricing_mode' => 'per_room',
                'slipok_enabled' => true,
            ],
        ]);

        $tenant = new Tenant([
            'subscribed_room_limit' => 12,
            'subscribed_slipok_enabled' => true,
            'subscribed_slipok_monthly_limit' => 36,
        ]);
        $tenant->setRelation('subscriptionPlan', $plan);

        $this->assertSame(12, $tenant->effectiveRoomsLimit());
        $this->assertTrue($tenant->slipOkAddonEnabled());
        $this->assertSame(36, $tenant->effectiveSlipOkMonthlyLimit());
    }

    #[Test]
    public function tenant_effective_limits_fall_back_to_fixed_plan_limits(): void
    {
        $plan = new Plan([
            'limits' => [
                'rooms' => 45,
                'slipok_enabled' => true,
                'slipok_monthly_limit' => 120,
            ],
        ]);

        $tenant = new Tenant([
            'subscribed_room_limit' => 12,
            'subscribed_slipok_enabled' => false,
            'subscribed_slipok_monthly_limit' => 0,
        ]);
        $tenant->setRelation('subscriptionPlan', $plan);

        $this->assertSame(45, $tenant->effectiveRoomsLimit());
        $this->assertTrue($tenant->slipOkAddonEnabled());
        $this->assertSame(120, $tenant->effectiveSlipOkMonthlyLimit());
    }
}