<?php

namespace Tests\Feature;

use App\Models\Plan;
use App\Models\PlatformCostSetting;
use App\Models\PlatformSetting;
use App\Models\SaasInvoice;
use App\Models\SlipVerificationUsage;
use App\Models\Tenant;
use App\Models\User;
use App\Services\StripePackagePricingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_from_tenant_dashboard(): void
    {
        $response = $this->get('/app/dashboard');

        $response->assertRedirect('/login');
    }

    public function test_guest_is_redirected_from_admin_dashboard(): void
    {
        $response = $this->get('/admin');

        $response->assertRedirect('/login');
    }

    public function test_owner_cannot_access_admin_dashboard(): void
    {
        $tenant = Tenant::create([
            'name' => 'Owner Dorm',
            'domain' => 'owner.local',
            'plan' => 'trial',
            'status' => 'active',
        ]);

        $owner = User::factory()->create([
            'tenant_id' => $tenant->id,
            'role' => 'owner',
        ]);

        $response = $this->actingAs($owner)->get('/admin');

        $response->assertForbidden();
    }

    public function test_support_admin_can_access_admin_dashboard(): void
    {
        $admin = User::factory()->create([
            'role' => 'support_admin',
        ]);

        $response = $this->actingAs($admin)->get('/admin');

        $response->assertOk();
        $response->assertSeeText('Dashboard Admin');
        $response->assertSeeText('Total Tenants');
    }

    public function test_admin_dashboard_security_policy_allows_adminlte_cdn_assets(): void
    {
        $admin = User::factory()->create([
            'role' => 'super_admin',
        ]);

        $response = $this->actingAs($admin)->get('/admin');

        $response->assertOk();
        $response->assertSee('https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/css/adminlte.min.css', false);
        $response->assertSee('https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/js/adminlte.min.js', false);

        $csp = (string) $response->headers->get('Content-Security-Policy');

        $this->assertStringContainsString('script-src', $csp);
        $this->assertStringContainsString('style-src', $csp);
        $this->assertStringContainsString('font-src', $csp);
        $this->assertStringContainsString('https://cdn.jsdelivr.net', $csp);
    }

    public function test_collected_revenue_on_admin_dashboard_comes_from_paid_saas_invoices(): void
    {
        $admin = User::factory()->create([
            'role' => 'support_admin',
        ]);

        $paidPlan = Plan::query()->create([
            'name' => 'Pro',
            'slug' => 'pro',
            'price_monthly' => 1500,
            'description' => 'Pro',
            'limits' => [],
            'is_active' => true,
            'sort_order' => 1,
        ]);

        Tenant::query()->create([
            'name' => 'Active Paid Tenant',
            'domain' => 'active-paid.local',
            'plan_id' => $paidPlan->id,
            'plan' => $paidPlan->slug,
            'status' => 'active',
        ]);

        Tenant::query()->create([
            'name' => 'Suspended Paid Tenant',
            'domain' => 'suspended-paid.local',
            'plan_id' => $paidPlan->id,
            'plan' => $paidPlan->slug,
            'status' => 'suspended',
        ]);

        $response = $this->actingAs($admin)->get('/admin');

        $response->assertOk();
        $response->assertSeeText('Collected Revenue');
        $response->assertSeeText('0');
        $response->assertSeeText('Projected MRR');
        $response->assertSeeText('1,500.00');
    }

    public function test_support_admin_sidebar_shows_admin_menu_items(): void
    {
        $admin = User::factory()->create([
            'role' => 'support_admin',
        ]);

        $response = $this->actingAs($admin)->get('/admin');

        $response->assertOk();
        $response->assertSeeText('Dashboard Admin');
        $response->assertSeeText('System Monitor');
        $response->assertSeeText('API Monitor');
        $response->assertSeeText('Tenant');
        $response->assertSeeText('Package Management');
        $response->assertSeeText('Cost Settings');
        $response->assertSeeText('Platform Admin');
        $response->assertSeeText('USER MENU');
        $response->assertSeeText('Profile');
        $response->assertSeeText('Change Password');
        $response->assertDontSee('/app/rooms', false);
        $response->assertDontSee('/app/customers', false);
        $response->assertDontSee('/app/contracts', false);
        $response->assertDontSee('/app/invoices', false);
        $response->assertDontSee('/app/payments', false);
        $response->assertDontSee('/app/broadcasts', false);
        $response->assertDontSee('/app/settings', false);
    }

    public function test_kpi_cards_are_only_visible_on_admin_dashboard(): void
    {
        $admin = User::factory()->create([
            'role' => 'support_admin',
        ]);

        $dashboardResponse = $this->actingAs($admin)->get('/admin');

        $dashboardResponse->assertOk();
        $dashboardResponse->assertSeeText('Total Tenants');
        $dashboardResponse->assertSeeText('Active Users');
        $dashboardResponse->assertSeeText('Collected Revenue');
        $dashboardResponse->assertSeeText('SlipOK Calls This Month');
        $dashboardResponse->assertSeeText('Net Revenue');
        $dashboardResponse->assertSeeText('Gross Margin');
        $dashboardResponse->assertSeeText('Cost Breakdown');
        $dashboardResponse->assertSeeText('Collected Revenue by Plan');

        $platformResponse = $this->actingAs($admin)->get('/admin/platform');

        $platformResponse->assertOk();
        $platformResponse->assertSeeText('Global SlipOK Settings');
        $platformResponse->assertSeeText('Stripe Subscription Settings');
        $platformResponse->assertDontSeeText('Total Tenants');
        $platformResponse->assertDontSeeText('Active Users');
        $platformResponse->assertDontSeeText('Collected Revenue');
        $platformResponse->assertDontSeeText('SlipOK Calls This Month');
    }

    public function test_admin_business_dashboard_uses_cost_settings_for_margin(): void
    {
        $admin = User::factory()->create([
            'role' => 'support_admin',
        ]);

        $plan = Plan::query()->create([
            'name' => 'Pro',
            'slug' => 'pro',
            'price_monthly' => 1500,
            'description' => 'Pro package',
            'limits' => [],
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $tenant = Tenant::query()->create([
            'name' => 'Margin Tenant',
            'domain' => 'margin.local',
            'plan_id' => $plan->id,
            'plan' => $plan->slug,
            'status' => 'active',
        ]);

        SaasInvoice::query()->create([
            'tenant_id' => $tenant->id,
            'plan_id' => $plan->id,
            'stripe_invoice_id' => 'in_margin_001',
            'status' => 'paid',
            'currency' => 'thb',
            'amount_due' => 100000,
            'amount_paid' => 100000,
            'amount_remaining' => 0,
            'paid_at' => now(),
        ]);

        foreach (range(1, 3) as $index) {
            SlipVerificationUsage::query()->create([
                'tenant_id' => $tenant->id,
                'plan_id' => $plan->id,
                'provider' => 'slipok',
                'usage_month' => now()->format('Y-m'),
                'status' => 'verified',
            ]);
        }

        PlatformCostSetting::query()->create([
            'provider' => 'slipok',
            'cost_type' => 'per_unit',
            'unit_cost' => 2,
            'currency' => 'THB',
            'effective_from' => now()->startOfMonth(),
            'is_active' => true,
        ]);

        PlatformCostSetting::query()->create([
            'provider' => 'stripe',
            'cost_type' => 'percentage',
            'percentage_rate' => 3,
            'fixed_fee' => 10,
            'currency' => 'THB',
            'effective_from' => now()->startOfMonth(),
            'is_active' => true,
        ]);

        PlatformCostSetting::query()->create([
            'provider' => 'hosting',
            'cost_type' => 'fixed_monthly',
            'fixed_fee' => 500,
            'currency' => 'THB',
            'effective_from' => now()->startOfMonth(),
            'is_active' => true,
        ]);

        $response = $this->actingAs($admin)->get('/admin');

        $response->assertOk();
        $response->assertSeeText('Collected Revenue');
        $response->assertSeeText('1,000');
        $response->assertSeeText('Net Revenue');
        $response->assertSeeText('454');
        $response->assertSeeText('Gross Margin');
        $response->assertSeeText('45.4%');
        $response->assertSeeText('Total Costs');
        $response->assertSeeText('546.00');
        $response->assertSeeText('SlipOK Calls This Month');
        $response->assertSeeText('3');
        $response->assertSeeText('High-Cost Tenants');
        $response->assertSeeText('Margin Tenant');
    }

    public function test_support_admin_can_create_cost_setting(): void
    {
        $admin = User::factory()->create([
            'role' => 'support_admin',
        ]);

        $response = $this->actingAs($admin)->post('/admin/cost-settings', [
            'provider' => 'slipok',
            'cost_type' => 'per_unit',
            'unit_cost' => 1.25,
            'percentage_rate' => 0,
            'fixed_fee' => 0,
            'included_quota' => 100,
            'overage_unit_cost' => 1.5,
            'currency' => 'THB',
            'effective_from' => now()->toDateString(),
            'is_active' => 1,
            'notes' => 'SlipOK test cost',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('platform_cost_settings', [
            'provider' => 'slipok',
            'cost_type' => 'per_unit',
            'currency' => 'THB',
            'included_quota' => 100,
        ]);
    }

    public function test_support_admin_can_access_cost_settings_guidance_and_presets(): void
    {
        $admin = User::factory()->create([
            'role' => 'support_admin',
        ]);

        $response = $this->actingAs($admin)->get('/admin/cost-settings');

        $response->assertOk();
        $response->assertSeeText('วิธีบันทึกต้นทุน');
        $response->assertSeeText('ค่าใช้จ่ายรายปีต้องแปลงเป็นรายเดือนก่อนบันทึกเสมอ');
        $response->assertSeeText('ชุดค่าตั้งต้นแนะนำ');
        $response->assertSeeText('Hosting รายปี 1,200 บาท');
        $response->assertSeeText('SlipOK API 1 บาท/ครั้ง');
        $response->assertSeeText('Stripe 0.28 บาท/รายการ');
        $response->assertSeeText('ใช้ค่าชุดนี้');
        $response->assertSeeText('บันทึก preset นี้ทันที');
    }

    public function test_owner_cannot_access_cost_settings(): void
    {
        $tenant = Tenant::query()->create([
            'name' => 'Owner Cost Dorm',
            'domain' => 'owner-cost.local',
            'plan' => 'trial',
            'status' => 'active',
        ]);

        $owner = User::factory()->create([
            'tenant_id' => $tenant->id,
            'role' => 'owner',
        ]);

        $response = $this->actingAs($owner)->get('/admin/cost-settings');

        $response->assertForbidden();
    }

    public function test_support_admin_can_access_package_management_page(): void
    {
        $admin = User::factory()->create([
            'role' => 'support_admin',
        ]);

        $response = $this->actingAs($admin)->get('/admin/packages');

        $response->assertOk();
        $response->assertSeeText('Create Package');
        $response->assertSeeText('Package Management');
    }

    public function test_support_admin_can_access_tenant_management_page(): void
    {
        $admin = User::factory()->create([
            'role' => 'support_admin',
        ]);

        $tenant = Tenant::query()->create([
            'name' => 'Tenant Menu Dorm',
            'domain' => 'tenant-menu.local',
            'plan' => 'trial',
            'status' => 'active',
        ]);

        $response = $this->actingAs($admin)->get('/admin/tenants');

        $response->assertOk();
        $response->assertSeeText('Tenant Management');
        $response->assertSeeText('Tenant Menu Dorm');
        $response->assertSeeText('Archive');
    }

    public function test_support_admin_can_filter_deleted_tenants(): void
    {
        $admin = User::factory()->create([
            'role' => 'support_admin',
        ]);

        $activeTenant = Tenant::query()->create([
            'name' => 'Active Filter Dorm',
            'domain' => 'active-filter.local',
            'plan' => 'trial',
            'status' => 'active',
        ]);

        $deletedTenant = Tenant::query()->create([
            'name' => 'Deleted Filter Dorm',
            'domain' => 'deleted-filter.local',
            'plan' => 'trial',
            'status' => 'active',
        ]);

        $deletedTenant->delete();

        $response = $this->actingAs($admin)->get('/admin/tenants?status=deleted&q=Deleted');

        $response->assertOk();
        $response->assertSeeText('Deleted Filter Dorm');
        $response->assertSeeText('deleted-filter.local');
        $response->assertDontSeeText('active-filter.local');
    }

    public function test_support_admin_tenant_management_is_paginated(): void
    {
        $admin = User::factory()->create([
            'role' => 'support_admin',
        ]);

        Tenant::query()->create([
            'name' => 'Aardvark Dorm',
            'domain' => 'aardvark.local',
            'plan' => 'trial',
            'status' => 'active',
        ]);

        foreach (range(1, 11) as $index) {
            Tenant::query()->create([
                'name' => sprintf('Paged Dorm %02d', $index),
                'domain' => sprintf('paged-%02d.local', $index),
                'plan' => 'trial',
                'status' => 'active',
            ]);
        }

        $response = $this->actingAs($admin)->get('/admin/tenants');

        $response->assertOk();
        $response->assertSeeText('Aardvark Dorm');
        $response->assertSee('page=2', false);
    }

    public function test_support_admin_can_create_package_from_package_management(): void
    {
        $admin = User::factory()->create([
            'role' => 'support_admin',
        ]);

        $response = $this->actingAs($admin)->post('/admin/packages', [
            'name' => 'Growth',
            'slug' => 'growth',
            'price_monthly' => 1999,
            'description' => 'Growth package',
            'is_active' => 1,
            'sort_order' => 2,
            'stripe_price_id' => 'price_test_growth',
            'rooms_limit' => 120,
            'recommended' => 1,
            'slipok_enabled' => 1,
            'slipok_monthly_limit' => 300,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('plans', [
            'name' => 'Growth',
            'slug' => 'growth',
            'stripe_price_id' => 'price_test_growth',
        ]);

        $plan = Plan::query()->where('slug', 'growth')->firstOrFail();
        $this->assertTrue($plan->supportsSlipOk());
        $this->assertSame(300, $plan->slipOkMonthlyLimit());
        $this->assertSame(120, $plan->roomsLimit());
        $this->assertTrue($plan->isRecommended());
    }

    public function test_support_admin_can_auto_create_stripe_price_id_when_creating_package(): void
    {
        $admin = User::factory()->create([
            'role' => 'support_admin',
        ]);

        PlatformSetting::current()->update([
            'stripe_enabled' => true,
            'stripe_mode' => 'test',
            'stripe_publishable_key' => 'pk_test_123',
            'stripe_secret_key' => 'sk_test_123',
            'stripe_webhook_secret' => 'whsec_123',
        ]);

        $service = $this->mock(StripePackagePricingService::class);
        $service->shouldReceive('createMonthlyCatalog')
            ->once()
            ->andReturn([
                'price_id' => 'price_test_auto_created',
                'product_id' => 'prod_test_auto_created',
            ]);

        $response = $this->actingAs($admin)->post('/admin/packages', [
            'name' => 'Auto Stripe Growth',
            'slug' => 'auto-stripe-growth',
            'price_monthly' => 1499,
            'description' => 'Auto stripe package',
            'is_active' => 1,
            'sort_order' => 2,
            'rooms_limit' => 80,
            'recommended' => 0,
            'slipok_enabled' => 0,
            'slipok_monthly_limit' => 0,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('plans', [
            'slug' => 'auto-stripe-growth',
            'stripe_price_id' => 'price_test_auto_created',
            'stripe_product_id' => 'prod_test_auto_created',
        ]);
    }

    public function test_support_admin_can_create_custom_room_package_without_stripe_price_id(): void
    {
        $admin = User::factory()->create([
            'role' => 'support_admin',
        ]);

        $response = $this->actingAs($admin)->post('/admin/packages', [
            'name' => 'Custom Room Flex',
            'slug' => 'custom-room-flex',
            'price_monthly' => 89,
            'custom_room_pricing' => 1,
            'room_price_monthly' => 89,
            'description' => 'Custom room package',
            'is_active' => 1,
            'sort_order' => 4,
            'recommended' => 1,
            'slipok_enabled' => 1,
            'slipok_addon_price_monthly' => 25,
        ]);

        $response->assertRedirect();

        $plan = Plan::query()->where('slug', 'custom-room-flex')->firstOrFail();

        $this->assertTrue($plan->usesCustomRoomPricing());
        $this->assertSame(89.0, $plan->roomPriceMonthly());
        $this->assertTrue($plan->supportsSlipOk());
        $this->assertSame(25.0, $plan->slipAddonPriceMonthly());
        $this->assertSame(3, $plan->slipAddonRightsPerRoom());
        $this->assertNull($plan->stripe_price_id);
    }

    public function test_support_admin_sees_clear_error_when_auto_create_stripe_price_is_unavailable(): void
    {
        $admin = User::factory()->create([
            'role' => 'support_admin',
        ]);

        $response = $this->from('/admin/packages')->actingAs($admin)->post('/admin/packages', [
            'name' => 'Needs Stripe Setup',
            'slug' => 'needs-stripe-setup',
            'price_monthly' => 999,
            'description' => 'Needs Stripe',
            'is_active' => 1,
            'sort_order' => 2,
            'rooms_limit' => 50,
            'recommended' => 0,
            'slipok_enabled' => 0,
            'slipok_monthly_limit' => 0,
        ]);

        $response->assertRedirect('/admin/packages');
        $response->assertSessionHasErrors(['stripe_price_id']);
        $this->assertDatabaseMissing('plans', [
            'slug' => 'needs-stripe-setup',
        ]);
    }

    public function test_support_admin_can_delete_package_from_package_management(): void
    {
        $admin = User::factory()->create([
            'role' => 'support_admin',
        ]);

        PlatformSetting::current()->update([
            'stripe_enabled' => true,
            'stripe_mode' => 'test',
            'stripe_publishable_key' => 'pk_test_123',
            'stripe_secret_key' => 'sk_test_123',
            'stripe_webhook_secret' => 'whsec_123',
        ]);

        $plan = Plan::query()->create([
            'name' => 'Delete Me',
            'slug' => 'delete-me',
            'price_monthly' => 799,
            'stripe_price_id' => 'price_test_delete_me',
            'stripe_product_id' => 'prod_test_delete_me',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $service = $this->mock(StripePackagePricingService::class);
        $service->shouldReceive('archiveCatalog')
            ->once()
            ->andReturnNull();

        $response = $this->from('/admin/packages')
            ->actingAs($admin)
            ->delete(route('admin.packages.destroy', $plan));

        $response->assertRedirect('/admin/packages');
        $this->assertDatabaseMissing('plans', [
            'id' => $plan->id,
        ]);
    }

    public function test_support_admin_cannot_delete_package_when_stripe_cleanup_is_unavailable(): void
    {
        $admin = User::factory()->create([
            'role' => 'support_admin',
        ]);

        $plan = Plan::query()->create([
            'name' => 'Delete Me Later',
            'slug' => 'delete-me-later',
            'price_monthly' => 799,
            'stripe_price_id' => 'price_test_delete_me_later',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $response = $this->from('/admin/packages')
            ->actingAs($admin)
            ->delete(route('admin.packages.destroy', $plan));

        $response->assertRedirect('/admin/packages');
        $response->assertSessionHasErrors(['stripe_price_id']);
        $this->assertDatabaseHas('plans', [
            'id' => $plan->id,
        ]);
    }

    public function test_support_admin_cannot_save_product_id_in_stripe_price_field(): void
    {
        $admin = User::factory()->create([
            'role' => 'support_admin',
        ]);

        $response = $this->from('/admin/packages')->actingAs($admin)->post('/admin/packages', [
            'name' => 'Broken Stripe Package',
            'slug' => 'broken-stripe-package',
            'price_monthly' => 999,
            'description' => 'Broken package',
            'is_active' => 1,
            'sort_order' => 3,
            'stripe_price_id' => 'prod_ULOuFwjNQpbvEc',
            'rooms_limit' => 20,
            'recommended' => 0,
            'slipok_enabled' => 0,
            'slipok_monthly_limit' => 0,
        ]);

        $response->assertRedirect('/admin/packages');
        $response->assertSessionHasErrors(['stripe_price_id']);
        $this->assertDatabaseMissing('plans', [
            'slug' => 'broken-stripe-package',
        ]);
    }

    public function test_support_admin_can_update_stripe_settings_from_platform_admin(): void
    {
        $admin = User::factory()->create([
            'role' => 'support_admin',
        ]);

        $response = $this->actingAs($admin)->post('/admin/stripe/settings', [
            'stripe_enabled' => 1,
            'stripe_mode' => 'test',
            'stripe_publishable_key' => 'pk_test_123',
            'stripe_secret_key' => 'sk_test_123',
            'stripe_webhook_secret' => 'whsec_123',
        ]);

        $response->assertRedirect();

        $setting = PlatformSetting::current();
        $this->assertTrue($setting->stripe_enabled);
        $this->assertSame('test', $setting->stripe_mode);
        $this->assertSame('pk_test_123', $setting->stripe_publishable_key);
        $this->assertSame('sk_test_123', $setting->stripe_secret_key);
        $this->assertSame('whsec_123', $setting->stripe_webhook_secret);
    }

    public function test_support_admin_can_access_system_monitor_page(): void
    {
        $admin = User::factory()->create([
            'role' => 'support_admin',
        ]);

        $dashboardResponse = $this->actingAs($admin)->get('/admin');

        $dashboardResponse->assertOk();
        $dashboardResponse->assertDontSeeText('Server Status');
        $dashboardResponse->assertDontSeeText('Queue Status');
        $dashboardResponse->assertDontSeeText('Notification Logs');
        $dashboardResponse->assertDontSeeText('Payment Logs');
        $dashboardResponse->assertDontSeeText('Channel');
        $dashboardResponse->assertDontSeeText('Receipt');

        $response = $this->actingAs($admin)->get('/admin/system-monitor');

        $response->assertOk();
        $response->assertSeeText('System Monitor');
        $response->assertSeeText('Server Status');
        $response->assertSeeText('Queue Status');
        $response->assertSeeText('Failed Jobs');
        $response->assertSeeText('API Usage');
        $response->assertSeeText('Notification Logs');
        $response->assertSeeText('Payment Logs');
        $response->assertSeeText('Channel');
        $response->assertSeeText('Receipt');
    }

    public function test_support_admin_can_access_api_monitor_page(): void
    {
        $admin = User::factory()->create([
            'role' => 'support_admin',
        ]);

        $response = $this->actingAs($admin)->get('/admin/api-monitor');

        $response->assertOk();
        $response->assertSeeText('API Monitoring Overview');
        $response->assertSeeText('Endpoint Metrics');
        $response->assertSeeText('Prometheus Ready');
    }

    public function test_support_admin_cannot_access_tenant_portal_routes(): void
    {
        $admin = User::factory()->create([
            'role' => 'support_admin',
        ]);

        $response = $this->actingAs($admin)->get('/app/dashboard');

        $response->assertForbidden();
    }

    public function test_super_admin_can_access_admin_profile_without_billing_menu(): void
    {
        $admin = User::factory()->create([
            'role' => 'super_admin',
        ]);

        $response = $this->actingAs($admin)->get('/admin/profile');

        $response->assertOk();
        $response->assertSeeText('Account Profile');
        $response->assertSeeText('Admin Console');
        $response->assertSeeText('Profile Information');
        $response->assertSeeText('Change Password');
        $response->assertDontSeeText('Billing');
    }

    public function test_unverified_owner_is_redirected_to_email_verification_before_tenant_dashboard(): void
    {
        $tenant = Tenant::create([
            'name' => 'Unverified Dorm',
            'domain' => 'unverified.local',
            'plan' => 'trial',
            'status' => 'active',
        ]);

        $owner = User::factory()->unverified()->create([
            'tenant_id' => $tenant->id,
            'role' => 'owner',
        ]);

        $response = $this->actingAs($owner)->get('/app/dashboard');

        $response->assertRedirect('/email/verify');
    }
}