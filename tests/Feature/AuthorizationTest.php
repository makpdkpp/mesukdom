<?php

namespace Tests\Feature;

use App\Models\Plan;
use App\Models\PlatformSetting;
use App\Models\Tenant;
use App\Models\User;
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

    public function test_saas_revenue_on_admin_dashboard_comes_from_active_subscriptions(): void
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
        $response->assertSeeText('SaaS Revenue');
        $response->assertSeeText('1,500');
    }

    public function test_support_admin_sidebar_shows_admin_menu_items(): void
    {
        $admin = User::factory()->create([
            'role' => 'support_admin',
        ]);

        $response = $this->actingAs($admin)->get('/admin');

        $response->assertOk();
        $response->assertSeeText('Dashboard Admin');
        $response->assertSeeText('Tenant');
        $response->assertSeeText('Package Management');
        $response->assertSeeText('Platform Admin');
        $response->assertSeeText('USER MENU');
        $response->assertSeeText('Profile');
        $response->assertSeeText('Change Password');
        $response->assertDontSeeText('Rooms');
        $response->assertDontSeeText('Residents');
        $response->assertDontSeeText('Contracts');
        $response->assertDontSeeText('Invoices');
        $response->assertDontSeeText('Payments');
        $response->assertDontSeeText('Broadcasts');
        $response->assertDontSeeText('Settings');
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
        $dashboardResponse->assertSeeText('SaaS Revenue');
        $dashboardResponse->assertSeeText('SlipOK Calls This Month');

        $platformResponse = $this->actingAs($admin)->get('/admin/platform');

        $platformResponse->assertOk();
        $platformResponse->assertSeeText('Global SlipOK Settings');
        $platformResponse->assertSeeText('Stripe Subscription Settings');
        $platformResponse->assertDontSeeText('Total Tenants');
        $platformResponse->assertDontSeeText('Active Users');
        $platformResponse->assertDontSeeText('SaaS Revenue');
        $platformResponse->assertDontSeeText('SlipOK Calls This Month');
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

    public function test_admin_dashboard_shows_required_system_monitoring_sections(): void
    {
        $admin = User::factory()->create([
            'role' => 'support_admin',
        ]);

        $response = $this->actingAs($admin)->get('/admin');

        $response->assertOk();
        $response->assertSeeText('Server Status');
        $response->assertSeeText('Queue Status');
        $response->assertSeeText('Failed Jobs');
        $response->assertSeeText('API Usage');
        $response->assertSeeText('Notification Logs');
        $response->assertSeeText('Payment Logs');
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