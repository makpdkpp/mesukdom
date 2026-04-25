<?php

namespace Tests\Feature;

use ArrayObject;
use App\Http\Controllers\BillingController;
use App\Models\Building;
use App\Models\Plan;
use App\Models\PlatformSetting;
use App\Models\Room;
use App\Models\SaasInvoice;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Laravel\Fortify\Features;
use Laravel\Jetstream\Jetstream;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_screen_can_be_rendered(): void
    {
        if (! Features::enabled(Features::registration())) {
            $this->markTestSkipped('Registration support is not enabled.');
        }

        $response = $this->get('/register');

        $response->assertStatus(200);
    }

    public function test_registration_screen_cannot_be_rendered_if_support_is_disabled(): void
    {
        if (Features::enabled(Features::registration())) {
            $this->markTestSkipped('Registration support is enabled.');
        }

        $response = $this->get('/register');

        $response->assertStatus(404);
    }

    public function test_pricing_page_shows_custom_room_selector_for_custom_package(): void
    {
        Plan::query()->create([
            'name' => 'Custom Flex',
            'slug' => 'custom-flex',
            'price_monthly' => 120,
            'is_active' => true,
            'sort_order' => 1,
            'limits' => [
                'pricing_mode' => 'per_room',
                'room_price_monthly' => 120,
                'slipok_enabled' => true,
                'slipok_addon_price_monthly' => 25,
                'slipok_rights_per_room' => 3,
            ],
        ]);

        $response = $this->get('/pricing');

        $response->assertOk();
        $response->assertSee('name="room_count"', false);
        $response->assertSee('name="slipok_addon_enabled"', false);
        $response->assertSee('min="10"', false);
        $response->assertDontSeeText('1 room = 3 rights');
    }

    public function test_new_users_can_register(): void
    {
        if (! Features::enabled(Features::registration())) {
            $this->markTestSkipped('Registration support is not enabled.');
        }

        $plan = Plan::query()->create([
            'name' => 'Trial',
            'slug' => 'trial',
            'price_monthly' => 0,
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $response = $this->post('/register', [
            'name' => 'Test User',
            'tenant_name' => 'Test Dorm',
            'plan_id' => $plan->id,
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'terms' => Jetstream::hasTermsAndPrivacyPolicyFeature(),
        ]);

        $this->assertAuthenticated();
        $tenant = Tenant::query()->where('name', 'Test Dorm')->first();

        $this->assertNotNull($tenant);
        $this->assertSame($plan->id, $tenant->plan_id);
        $this->assertSame('trial', $tenant->plan);
        $this->assertSame('active', $tenant->status);
        $this->assertNotNull($tenant->trial_ends_at);
        $this->assertDatabaseHas('users', [
            'tenant_id' => $tenant->id,
            'email' => 'test@example.com',
            'role' => 'owner',
        ]);
        $this->assertTrue(User::query()->where('tenant_id', $tenant->id)->where('email', 'test@example.com')->exists());
        $response->assertRedirect('/app/dashboard');
    }

    public function test_paid_plan_registration_requires_checkout_before_dashboard_access(): void
    {
        if (! Features::enabled(Features::registration())) {
            $this->markTestSkipped('Registration support is not enabled.');
        }

        $plan = Plan::query()->create([
            'name' => 'Lite',
            'slug' => 'lite',
            'price_monthly' => 299,
            'is_active' => true,
            'sort_order' => 2,
            'stripe_price_id' => 'price_test_lite',
        ]);

        $registerResponse = $this->post('/register', [
            'name' => 'Lite Owner',
            'tenant_name' => 'Lite Dorm',
            'plan_id' => $plan->id,
            'email' => 'lite-owner@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'terms' => Jetstream::hasTermsAndPrivacyPolicyFeature(),
        ]);

        $this->assertAuthenticated();

        $tenant = Tenant::query()->where('name', 'Lite Dorm')->first();
        $this->assertNotNull($tenant);
        $this->assertSame($plan->id, $tenant->plan_id);
        $this->assertSame('pending_checkout', $tenant->status);
        $this->assertSame('incomplete', $tenant->subscription_status);
        $this->assertNull($tenant->trial_ends_at);
        $this->assertFalse($tenant->hasPortalAccess());

        $registerResponse->assertRedirect('/app/dashboard');

        $user = User::query()->where('email', 'lite-owner@example.com')->firstOrFail();
        $user->forceFill(['email_verified_at' => now()])->save();
        $verifiedUser = User::query()->whereKey($user->id)->firstOrFail();
        $this->actingAs($verifiedUser);

        $dashboardResponse = $this->get('/app/dashboard');
        $dashboardResponse->assertRedirect('/app/billing');
    }

    public function test_custom_room_package_registration_persists_selected_quota(): void
    {
        if (! Features::enabled(Features::registration())) {
            $this->markTestSkipped('Registration support is not enabled.');
        }

        $plan = Plan::query()->create([
            'name' => 'Custom Flex',
            'slug' => 'custom-flex',
            'price_monthly' => 120,
            'is_active' => true,
            'sort_order' => 1,
            'limits' => [
                'pricing_mode' => 'per_room',
                'room_price_monthly' => 120,
                'slipok_enabled' => true,
                'slipok_addon_price_monthly' => 25,
                'slipok_rights_per_room' => 3,
            ],
        ]);

        $response = $this->post('/register', [
            'name' => 'Custom Owner',
            'tenant_name' => 'Custom Register Dorm',
            'plan_id' => $plan->id,
            'room_count' => 10,
            'slipok_addon_enabled' => 1,
            'email' => 'custom-register@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'terms' => Jetstream::hasTermsAndPrivacyPolicyFeature(),
        ]);

        $this->assertAuthenticated();

        $tenant = Tenant::query()->where('name', 'Custom Register Dorm')->firstOrFail();

        $this->assertSame($plan->id, $tenant->plan_id);
        $this->assertSame('pending_checkout', $tenant->status);
        $this->assertSame(10, $tenant->subscribed_room_limit);
        $this->assertTrue($tenant->subscribed_slipok_enabled);
        $this->assertSame(30, $tenant->subscribed_slipok_monthly_limit);
        $response->assertRedirect('/app/dashboard');
    }

    public function test_custom_room_package_registration_rejects_room_count_below_minimum(): void
    {
        if (! Features::enabled(Features::registration())) {
            $this->markTestSkipped('Registration support is not enabled.');
        }

        $plan = Plan::query()->create([
            'name' => 'Custom Flex',
            'slug' => 'custom-flex',
            'price_monthly' => 120,
            'is_active' => true,
            'sort_order' => 1,
            'limits' => [
                'pricing_mode' => 'per_room',
                'room_price_monthly' => 120,
                'slipok_enabled' => true,
                'slipok_addon_price_monthly' => 25,
                'slipok_rights_per_room' => 3,
            ],
        ]);

        $response = $this->from('/register')->post('/register', [
            'name' => 'Custom Owner',
            'tenant_name' => 'Too Small Dorm',
            'plan_id' => $plan->id,
            'room_count' => 9,
            'slipok_addon_enabled' => 0,
            'email' => 'too-small@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'terms' => Jetstream::hasTermsAndPrivacyPolicyFeature(),
        ]);

        $response->assertRedirect('/register');
        $response->assertSessionHasErrors('room_count');
        $this->assertGuest();
        $this->assertDatabaseMissing('tenants', [
            'name' => 'Too Small Dorm',
        ]);
    }

    public function test_checkout_success_sync_activates_tenant_from_magic_property_objects(): void
    {
        $plan = Plan::query()->create([
            'name' => 'Basic',
            'slug' => 'basic',
            'price_monthly' => 499,
            'price_yearly' => 4990,
            'is_active' => true,
            'sort_order' => 1,
            'stripe_price_id' => 'price_test_basic',
            'stripe_yearly_price_id' => 'price_test_basic_yearly',
        ]);

        $tenant = Tenant::query()->create([
            'name' => 'Magic Stripe Dorm',
            'plan' => 'trial',
            'status' => 'pending_checkout',
            'subscription_status' => 'incomplete',
        ]);

        $magicObjectFactory = static fn (array $data): object => new class($data) implements \JsonSerializable {
            public function __construct(private array $data) {}

            public function __get(string $name): mixed
            {
                return $this->data[$name] ?? null;
            }

            public function __isset(string $name): bool
            {
                return array_key_exists($name, $this->data);
            }

            public function jsonSerialize(): array
            {
                return $this->data;
            }
        };

        $session = $magicObjectFactory([
            'customer' => $magicObjectFactory(['id' => 'cus_magic_001']),
            'subscription' => $magicObjectFactory([
                'id' => 'sub_magic_001',
                'status' => 'active',
                'current_period_end' => now()->addMonth()->timestamp,
            ]),
            'metadata' => $magicObjectFactory([
                'plan_id' => (string) $plan->id,
                'billing_option' => 'subscription_annual',
            ]),
            'payment_status' => 'paid',
            'status' => 'complete',
        ]);

        $controller = app(BillingController::class);
        $method = new \ReflectionMethod(BillingController::class, 'syncTenantFromCheckoutSession');
        $method->setAccessible(true);
        $method->invoke($controller, $tenant, $session);

        $tenant->refresh();

        $this->assertSame('active', $tenant->status);
        $this->assertSame('active', $tenant->subscription_status);
        $this->assertSame($plan->id, $tenant->plan_id);
        $this->assertSame('basic', $tenant->plan);
        $this->assertSame('cus_magic_001', $tenant->stripe_customer_id);
        $this->assertSame('sub_magic_001', $tenant->stripe_subscription_id);
        $this->assertNotNull($tenant->subscription_current_period_end);
        $this->assertSame('subscription_annual', $tenant->billing_option);
        $this->assertNull($tenant->access_expires_at);
    }

    public function test_checkout_success_sync_activates_prepaid_annual_access(): void
    {
        $plan = Plan::query()->create([
            'name' => 'Basic',
            'slug' => 'basic',
            'price_monthly' => 499,
            'price_prepaid_annual' => 4990,
            'is_active' => true,
            'sort_order' => 1,
            'stripe_prepaid_annual_price_id' => 'price_test_basic_prepaid',
        ]);

        $tenant = Tenant::query()->create([
            'name' => 'Prepaid Stripe Dorm',
            'plan' => 'trial',
            'status' => 'pending_checkout',
            'subscription_status' => 'incomplete',
        ]);

        $magicObjectFactory = static fn (array $data): object => new class($data) implements \JsonSerializable {
            public function __construct(private array $data) {}

            public function __get(string $name): mixed
            {
                return $this->data[$name] ?? null;
            }

            public function __isset(string $name): bool
            {
                return array_key_exists($name, $this->data);
            }

            public function jsonSerialize(): array
            {
                return $this->data;
            }
        };

        $session = $magicObjectFactory([
            'customer' => $magicObjectFactory(['id' => 'cus_prepaid_001']),
            'metadata' => $magicObjectFactory([
                'plan_id' => (string) $plan->id,
                'billing_option' => 'prepaid_annual',
            ]),
            'payment_status' => 'paid',
            'status' => 'complete',
        ]);

        $controller = app(BillingController::class);
        $method = new \ReflectionMethod(BillingController::class, 'syncTenantFromCheckoutSession');
        $method->setAccessible(true);
        $method->invoke($controller, $tenant, $session);

        $tenant->refresh();

        $this->assertSame('active', $tenant->status);
        $this->assertSame('prepaid', $tenant->subscription_status);
        $this->assertSame($plan->id, $tenant->plan_id);
        $this->assertSame('basic', $tenant->plan);
        $this->assertSame('cus_prepaid_001', $tenant->stripe_customer_id);
        $this->assertNull($tenant->stripe_subscription_id);
        $this->assertSame('prepaid_annual', $tenant->billing_option);
        $this->assertNotNull($tenant->access_expires_at);
        $this->assertTrue($tenant->hasPortalAccess());
        $this->assertTrue($tenant->access_expires_at->greaterThan(now()->addDays(364)));
    }

    public function test_checkout_success_sync_persists_custom_room_package_selection(): void
    {
        $plan = Plan::query()->create([
            'name' => 'Custom Flex',
            'slug' => 'custom-flex',
            'price_monthly' => 120,
            'is_active' => true,
            'sort_order' => 1,
            'limits' => [
                'pricing_mode' => 'per_room',
                'room_price_monthly' => 120,
                'slipok_enabled' => true,
                'slipok_addon_price_monthly' => 25,
                'slipok_rights_per_room' => 3,
            ],
        ]);

        $tenant = Tenant::query()->create([
            'name' => 'Custom Room Dorm',
            'plan' => 'trial',
            'status' => 'pending_checkout',
            'subscription_status' => 'incomplete',
        ]);

        $magicObjectFactory = static fn (array $data): object => new class($data) implements \JsonSerializable {
            public function __construct(private array $data) {}

            public function __get(string $name): mixed
            {
                return $this->data[$name] ?? null;
            }

            public function __isset(string $name): bool
            {
                return array_key_exists($name, $this->data);
            }

            public function jsonSerialize(): array
            {
                return $this->data;
            }
        };

        $session = $magicObjectFactory([
            'customer' => $magicObjectFactory(['id' => 'cus_custom_001']),
            'subscription' => $magicObjectFactory([
                'id' => 'sub_custom_001',
                'status' => 'active',
                'current_period_end' => now()->addMonth()->timestamp,
            ]),
            'metadata' => $magicObjectFactory([
                'plan_id' => (string) $plan->id,
                'billing_option' => 'subscription_annual',
                'room_count' => '8',
                'slipok_addon_enabled' => '1',
            ]),
            'payment_status' => 'paid',
            'status' => 'complete',
        ]);

        $controller = app(BillingController::class);
        $method = new \ReflectionMethod(BillingController::class, 'syncTenantFromCheckoutSession');
        $method->setAccessible(true);
        $method->invoke($controller, $tenant, $session);

        $tenant->refresh();

        $this->assertSame('active', $tenant->status);
        $this->assertSame('active', $tenant->subscription_status);
        $this->assertSame($plan->id, $tenant->plan_id);
        $this->assertSame(10, $tenant->subscribed_room_limit);
        $this->assertTrue($tenant->subscribed_slipok_enabled);
        $this->assertSame(30, $tenant->subscribed_slipok_monthly_limit);
    }

    public function test_billing_page_shows_subscription_summary_even_without_invoices(): void
    {
        $plan = Plan::query()->create([
            'name' => 'Basic',
            'slug' => 'basic',
            'price_monthly' => 499,
            'price_yearly' => 4990,
            'price_prepaid_annual' => 4690,
            'is_active' => true,
            'sort_order' => 1,
            'stripe_price_id' => 'price_test_basic',
            'stripe_yearly_price_id' => 'price_test_basic_yearly',
            'stripe_prepaid_annual_price_id' => 'price_test_basic_prepaid',
            'limits' => [
                'rooms' => 40,
            ],
        ]);

        $tenant = Tenant::query()->create([
            'name' => 'Billing Summary Dorm',
            'plan_id' => $plan->id,
            'plan' => 'basic',
            'status' => 'active',
            'subscription_status' => 'active',
            'billing_option' => 'subscription_annual',
            'stripe_customer_id' => 'cus_summary_001',
            'stripe_subscription_id' => 'sub_summary_001',
            'subscription_current_period_end' => now()->addMonth(),
        ]);

        $user = User::factory()->create([
            'tenant_id' => $tenant->id,
            'role' => 'owner',
            'email_verified_at' => now(),
        ]);

        $response = $this->actingAs($user)
            ->withSession(['tenant_id' => $tenant->id])
            ->get('/app/billing');

        $response->assertOk();
        $response->assertSeeText('แพ็กเกจปัจจุบัน');
        $response->assertSeeText('Basic');
        $response->assertSeeText('สถานะการใช้งาน');
        $response->assertSeeText('Active');
        $response->assertSeeText('Subscription annual');
        $response->assertSeeText('เปลี่ยนแพ็กเกจ');
        $response->assertSee('/app/billing/packages', false);
        $response->assertSeeText('Stripe Customer');
        $response->assertSeeText('cus_summary_001');
        $response->assertSeeText('Stripe Subscription');
        $response->assertSeeText('sub_summary_001');
        $response->assertSeeText('ประวัติใบแจ้งหนี้ SaaS');
        $response->assertSeeText('ใบแจ้งหนี้ที่เก็บไว้: 0');
        $response->assertSeeText('ยังไม่เคยซิงก์');
        $response->assertSee('data-target="#invoiceSyncSettingsModal"', false);
        $response->assertSeeText('ตั้งค่าการซิงก์ Stripe invoices');
        $response->assertSeeText('30 วัน');
        $response->assertSeeText('90 วัน');
        $response->assertSeeText('1 ปี');
        $response->assertSeeText('ทั้งหมด');
        $response->assertSee('name="since_date"', false);
        $response->assertSee('name="max_invoices"', false);
        $response->assertSeeText('ยังไม่มีประวัติใบแจ้งหนี้ SaaS');
    }

    public function test_billing_page_shows_custom_room_pricing_selector(): void
    {
        PlatformSetting::current()->update([
            'stripe_enabled' => true,
            'stripe_mode' => 'test',
            'stripe_publishable_key' => 'pk_test_custom_billing',
            'stripe_secret_key' => 'sk_test_custom_billing',
            'stripe_webhook_secret' => 'whsec_custom_billing',
        ]);

        $plan = Plan::query()->create([
            'name' => 'Custom Flex',
            'slug' => 'custom-flex',
            'price_monthly' => 120,
            'is_active' => true,
            'sort_order' => 1,
            'limits' => [
                'pricing_mode' => 'per_room',
                'room_price_monthly' => 120,
                'slipok_enabled' => true,
                'slipok_addon_price_monthly' => 25,
                'slipok_rights_per_room' => 3,
            ],
        ]);

        $tenant = Tenant::query()->create([
            'name' => 'Pending Custom Billing Dorm',
            'plan_id' => $plan->id,
            'plan' => 'custom-flex',
            'status' => 'pending_checkout',
            'subscription_status' => 'incomplete',
        ]);

        $user = User::factory()->create([
            'tenant_id' => $tenant->id,
            'role' => 'owner',
            'email_verified_at' => now(),
        ]);

        $response = $this->actingAs($user)
            ->withSession(['tenant_id' => $tenant->id])
            ->get('/app/billing/packages');

        $response->assertOk();
        $response->assertSeeText('แพ็กเกจกำหนดเอง');
        $response->assertSeeText('ลูกค้ากำหนดจำนวนห้องได้เอง');
        $response->assertSee('name="room_count"', false);
        $response->assertSee('min="10"', false);
        $response->assertSeeText('เปิดใช้ slip verification addon');
        $response->assertSeeText('ชำระหรือสมัครแบบรายปีเท่านั้น');
        $response->assertSeeText('สมาชิกรายปี');
        $response->assertSeeText('ชำระล่วงหน้ารายปี');
        $response->assertDontSeeText('1 room = 3 rights');
        $response->assertSeeText('ต่ออายุ / ชำระแพ็กเกจนี้');
    }

    public function test_billing_page_can_load_another_package_for_checkout_from_menu(): void
    {
        PlatformSetting::current()->update([
            'stripe_enabled' => true,
            'stripe_mode' => 'test',
            'stripe_publishable_key' => 'pk_test_package_switch',
            'stripe_secret_key' => 'sk_test_package_switch',
            'stripe_webhook_secret' => 'whsec_package_switch',
        ]);

        $basicPlan = Plan::query()->create([
            'name' => 'Basic',
            'slug' => 'basic',
            'price_monthly' => 499,
            'price_yearly' => 4990,
            'price_prepaid_annual' => 4690,
            'is_active' => true,
            'sort_order' => 1,
            'stripe_yearly_price_id' => 'price_test_basic_yearly',
            'stripe_prepaid_annual_price_id' => 'price_test_basic_prepaid',
        ]);

        $customPlan = Plan::query()->create([
            'name' => 'Custom Flex',
            'slug' => 'custom-flex',
            'price_monthly' => 120,
            'is_active' => true,
            'sort_order' => 2,
            'limits' => [
                'pricing_mode' => 'per_room',
                'room_price_monthly' => 120,
                'slipok_enabled' => true,
                'slipok_addon_price_monthly' => 25,
                'slipok_rights_per_room' => 3,
            ],
        ]);

        $tenant = Tenant::query()->create([
            'name' => 'Package Switch Dorm',
            'plan_id' => $basicPlan->id,
            'plan' => 'basic',
            'status' => 'active',
            'subscription_status' => 'active',
            'billing_option' => 'subscription_annual',
            'stripe_customer_id' => 'cus_switch_001',
            'stripe_subscription_id' => 'sub_switch_001',
            'subscription_current_period_end' => now()->addMonth(),
        ]);

        $user = User::factory()->create([
            'tenant_id' => $tenant->id,
            'role' => 'owner',
            'email_verified_at' => now(),
        ]);

        $response = $this->actingAs($user)
            ->withSession(['tenant_id' => $tenant->id])
            ->get('/app/billing');

        $response->assertOk();
        $response->assertSeeText('แพ็กเกจปัจจุบัน');
        $response->assertSeeText('Basic');
        $response->assertSeeText('เปลี่ยนแพ็กเกจ');

        $packagesResponse = $this->actingAs($user)
            ->withSession(['tenant_id' => $tenant->id])
            ->get('/app/billing/packages');

        $packagesResponse->assertOk();
        $packagesResponse->assertSeeText('เลือกแพ็กเกจ');
        $packagesResponse->assertSeeText('Basic');
        $packagesResponse->assertSeeText('Custom Flex');
        $packagesResponse->assertSeeText('ปัจจุบัน');
        $packagesResponse->assertSeeText('แพ็กเกจกำหนดเอง');
        $packagesResponse->assertSee('name="plan_id" value="'.$basicPlan->id.'"', false);
        $packagesResponse->assertSee('name="plan_id" value="'.$customPlan->id.'"', false);
        $packagesResponse->assertSee('name="room_count"', false);
        $packagesResponse->assertSee('min="10"', false);
        $packagesResponse->assertSeeText('เลือกแพ็กเกจนี้');
    }

    public function test_custom_room_package_enforces_selected_room_limit_when_creating_rooms(): void
    {
        $plan = Plan::query()->create([
            'name' => 'Custom Flex',
            'slug' => 'custom-flex',
            'price_monthly' => 120,
            'is_active' => true,
            'sort_order' => 1,
            'limits' => [
                'pricing_mode' => 'per_room',
                'room_price_monthly' => 120,
                'slipok_enabled' => true,
                'slipok_addon_price_monthly' => 25,
                'slipok_rights_per_room' => 3,
            ],
        ]);

        $tenant = Tenant::query()->create([
            'name' => 'Custom Limit Dorm',
            'plan_id' => $plan->id,
            'plan' => 'custom-flex',
            'status' => 'active',
            'subscription_status' => 'active',
            'subscribed_room_limit' => 2,
            'subscribed_slipok_enabled' => true,
            'subscribed_slipok_monthly_limit' => 6,
        ]);

        $building = Building::query()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Main',
            'floor_count' => 1,
            'room_types' => [
                ['name' => 'Standard', 'price' => 3500],
            ],
        ]);

        Room::query()->create([
            'tenant_id' => $tenant->id,
            'building_id' => $building->id,
            'building' => 'Main',
            'room_number' => '101',
            'floor' => 1,
            'room_type' => 'Standard',
            'price' => 3500,
            'status' => 'vacant',
        ]);

        Room::query()->create([
            'tenant_id' => $tenant->id,
            'building_id' => $building->id,
            'building' => 'Main',
            'room_number' => '102',
            'floor' => 1,
            'room_type' => 'Standard',
            'price' => 3500,
            'status' => 'vacant',
        ]);

        $user = User::factory()->create([
            'tenant_id' => $tenant->id,
            'role' => 'owner',
            'email_verified_at' => now(),
        ]);

        $response = $this->actingAs($user)
            ->withSession(['tenant_id' => $tenant->id])
            ->from('/app/rooms')
            ->post(route('app.rooms.store'), [
                'building_id' => $building->id,
                'room_number' => '103',
                'floor' => 1,
                'room_type' => 'Standard',
                'status' => 'vacant',
            ]);

        $response->assertRedirect('/app/rooms');
        $response->assertSessionHasErrors(['room_number']);
        $this->assertDatabaseMissing('rooms', [
            'tenant_id' => $tenant->id,
            'room_number' => '103',
        ]);
    }

    public function test_app_sidebar_shows_user_menu_with_profile_password_and_billing_links(): void
    {
        $plan = Plan::query()->create([
            'name' => 'Basic',
            'slug' => 'basic',
            'price_monthly' => 499,
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $tenant = Tenant::query()->create([
            'name' => 'Sidebar User Dorm',
            'plan_id' => $plan->id,
            'plan' => 'basic',
            'status' => 'active',
        ]);

        $user = User::factory()->create([
            'tenant_id' => $tenant->id,
            'role' => 'owner',
            'name' => 'Alexander Pierce',
            'email_verified_at' => now(),
        ]);

        $response = $this->actingAs($user)
            ->withSession(['tenant_id' => $tenant->id])
            ->get('/app/settings');

        $response->assertOk();
        $response->assertSeeText('Alexander Pierce');
        $response->assertSeeText('USER MENU');
        $response->assertSeeText('Profile');
        $response->assertSeeText('Change Password');
        $response->assertSeeText('Billing');
        $response->assertSee(route('profile.show'), false);
        $response->assertSee(route('app.billing'), false);
    }

    public function test_app_settings_sidebar_marks_settings_link_active(): void
    {
        $plan = Plan::query()->create([
            'name' => 'Basic',
            'slug' => 'basic',
            'price_monthly' => 499,
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $tenant = Tenant::query()->create([
            'name' => 'Active Settings Dorm',
            'plan_id' => $plan->id,
            'plan' => 'basic',
            'status' => 'active',
        ]);

        $user = User::factory()->create([
            'tenant_id' => $tenant->id,
            'role' => 'owner',
            'email_verified_at' => now(),
        ]);

        $response = $this->actingAs($user)
            ->withSession(['tenant_id' => $tenant->id])
            ->get('/app/settings');

        $response->assertOk();
        $response->assertSee('href="'.route('app.settings').'" class="nav-link active"', false);
    }

    public function test_app_billing_sidebar_marks_billing_link_active(): void
    {
        $plan = Plan::query()->create([
            'name' => 'Basic',
            'slug' => 'basic',
            'price_monthly' => 499,
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $tenant = Tenant::query()->create([
            'name' => 'Active Billing Dorm',
            'plan_id' => $plan->id,
            'plan' => 'basic',
            'status' => 'active',
            'subscription_status' => 'active',
        ]);

        $user = User::factory()->create([
            'tenant_id' => $tenant->id,
            'role' => 'owner',
            'email_verified_at' => now(),
        ]);

        $response = $this->actingAs($user)
            ->withSession(['tenant_id' => $tenant->id])
            ->get('/app/billing');

        $response->assertOk();
        $response->assertSee('href="'.route('app.billing').'" class="nav-link active"', false);
    }

    public function test_profile_page_uses_adminlte_shell_and_account_navigation(): void
    {
        $plan = Plan::query()->create([
            'name' => 'Basic',
            'slug' => 'basic',
            'price_monthly' => 499,
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $tenant = Tenant::query()->create([
            'name' => 'Profile Shell Dorm',
            'plan_id' => $plan->id,
            'plan' => 'basic',
            'status' => 'active',
        ]);

        $user = User::factory()->create([
            'tenant_id' => $tenant->id,
            'role' => 'owner',
            'name' => 'Profile Shell User',
            'email_verified_at' => now(),
        ]);

        $response = $this->actingAs($user)
            ->withSession(['tenant_id' => $tenant->id])
            ->get(route('profile.show'));

        $response->assertOk();
        $response->assertSeeText('MesukDorm');
        $response->assertSeeText('USER MENU');
        $response->assertSeeText('Account Security');
        $response->assertSeeText('Account Menu');
        $response->assertSee(route('app.dashboard'), false);
        $response->assertSee(route('app.billing'), false);
    }

    public function test_app_sidebar_brand_shows_current_tenant_name(): void
    {
        $plan = Plan::query()->create([
            'name' => 'Basic',
            'slug' => 'basic',
            'price_monthly' => 499,
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $tenant = Tenant::query()->create([
            'name' => 'Bunma Residence',
            'plan_id' => $plan->id,
            'plan' => 'basic',
            'status' => 'active',
        ]);

        $user = User::factory()->create([
            'tenant_id' => $tenant->id,
            'role' => 'owner',
            'email_verified_at' => now(),
        ]);

        $response = $this->actingAs($user)
            ->withSession(['tenant_id' => $tenant->id])
            ->get('/app/dashboard');

        $response->assertOk();
        $response->assertSee('<span class="brand-text font-weight-light">Bunma Residence</span>', false);
    }

    public function test_prepaid_annual_checkout_enables_invoice_creation(): void
    {
        PlatformSetting::current()->update([
            'stripe_enabled' => true,
            'stripe_mode' => 'test',
            'stripe_publishable_key' => 'pk_test_invoice_creation',
            'stripe_secret_key' => 'sk_test_invoice_creation',
            'stripe_webhook_secret' => 'whsec_invoice_creation',
        ]);

        $plan = Plan::query()->create([
            'name' => 'Basic',
            'slug' => 'basic',
            'price_monthly' => 499,
            'price_yearly' => 4990,
            'price_prepaid_annual' => 4690,
            'is_active' => true,
            'sort_order' => 1,
            'stripe_yearly_price_id' => 'price_test_basic_yearly',
            'stripe_prepaid_annual_price_id' => 'price_test_basic_prepaid',
        ]);

        $tenant = Tenant::query()->create([
            'name' => 'Invoice Creation Dorm',
            'plan_id' => $plan->id,
            'plan' => 'basic',
            'status' => 'active',
            'subscription_status' => 'active',
            'billing_option' => 'subscription_annual',
            'stripe_customer_id' => 'cus_invoice_creation_001',
        ]);

        $user = User::factory()->create([
            'tenant_id' => $tenant->id,
            'role' => 'owner',
            'email_verified_at' => now(),
        ]);

        $capturedPayloads = new ArrayObject();
        $this->app->instance('billing.stripe_client_factory', $this->fakeStripeClientFactory(
            capturedCheckoutPayloads: $capturedPayloads,
        ));

        $response = $this->actingAs($user)
            ->withSession(['tenant_id' => $tenant->id])
            ->post(route('app.billing.checkout'), [
                'plan_id' => $plan->id,
                'billing_option' => 'prepaid_annual',
            ]);

        $response->assertRedirect('https://stripe.example.test/checkout');
        $this->assertCount(1, $capturedPayloads);

        $payload = $capturedPayloads[0];

        $this->assertSame('payment', $payload['mode']);
        $this->assertSame('cus_invoice_creation_001', $payload['customer']);
        $this->assertSame((string) $tenant->id, $payload['metadata']['tenant_id']);
        $this->assertTrue($payload['invoice_creation']['enabled']);
        $this->assertSame((string) $tenant->id, $payload['invoice_creation']['invoice_data']['metadata']['tenant_id']);
        $this->assertSame((string) $plan->id, $payload['invoice_creation']['invoice_data']['metadata']['plan_id']);
        $this->assertArrayNotHasKey('subscription_data', $payload);
    }

    public function test_billing_page_can_backfill_stripe_invoices_with_limit_and_since_date(): void
    {
        PlatformSetting::current()->update([
            'stripe_enabled' => true,
            'stripe_mode' => 'test',
            'stripe_publishable_key' => 'pk_test_invoice_sync',
            'stripe_secret_key' => 'sk_test_invoice_sync',
            'stripe_webhook_secret' => 'whsec_invoice_sync',
        ]);

        $plan = Plan::query()->create([
            'name' => 'Basic',
            'slug' => 'basic',
            'price_monthly' => 499,
            'is_active' => true,
            'sort_order' => 1,
            'stripe_yearly_price_id' => 'price_test_basic_yearly',
            'stripe_prepaid_annual_price_id' => 'price_test_basic_prepaid',
        ]);

        $tenant = Tenant::query()->create([
            'name' => 'Invoice Sync Dorm',
            'plan_id' => $plan->id,
            'plan' => 'basic',
            'status' => 'active',
            'subscription_status' => 'active',
            'billing_option' => 'prepaid_annual',
            'stripe_customer_id' => 'cus_invoice_sync_001',
        ]);

        $user = User::factory()->create([
            'tenant_id' => $tenant->id,
            'role' => 'owner',
            'email_verified_at' => now(),
        ]);

        $capturedInvoiceQueryParams = new ArrayObject();

        $this->app->instance('billing.stripe_client_factory', $this->fakeStripeClientFactory(
            capturedInvoiceQueryParams: $capturedInvoiceQueryParams,
            invoicePages: [
                [
                    'data' => [
                        [
                            'id' => 'in_backfill_001',
                            'customer' => 'cus_invoice_sync_001',
                            'status' => 'paid',
                            'currency' => 'thb',
                            'amount_due' => 469000,
                            'amount_paid' => 469000,
                            'amount_remaining' => 0,
                            'period_start' => now()->startOfDay()->timestamp,
                            'period_end' => now()->addYear()->startOfDay()->timestamp,
                            'created' => now()->subDay()->timestamp,
                            'status_transitions' => [
                                'paid_at' => now()->subDay()->timestamp,
                            ],
                            'hosted_invoice_url' => 'https://stripe.example.test/invoices/in_backfill_001',
                            'invoice_pdf' => 'https://stripe.example.test/invoices/in_backfill_001.pdf',
                        ],
                        [
                            'id' => 'in_backfill_002',
                            'customer' => 'cus_invoice_sync_001',
                            'status' => 'open',
                            'currency' => 'thb',
                            'amount_due' => 499000,
                            'amount_paid' => 0,
                            'amount_remaining' => 499000,
                            'period_start' => now()->startOfDay()->timestamp,
                            'period_end' => now()->addYear()->startOfDay()->timestamp,
                            'created' => now()->subDays(2)->timestamp,
                            'status_transitions' => [
                                'paid_at' => null,
                            ],
                            'hosted_invoice_url' => 'https://stripe.example.test/invoices/in_backfill_002',
                            'invoice_pdf' => 'https://stripe.example.test/invoices/in_backfill_002.pdf',
                        ],
                    ],
                    'has_more' => true,
                ],
                [
                    'data' => [
                        [
                            'id' => 'in_backfill_003',
                            'customer' => 'cus_invoice_sync_001',
                            'status' => 'paid',
                            'currency' => 'thb',
                            'amount_due' => 399000,
                            'amount_paid' => 399000,
                            'amount_remaining' => 0,
                            'period_start' => now()->subYear()->startOfDay()->timestamp,
                            'period_end' => now()->subMonths(11)->startOfDay()->timestamp,
                            'created' => now()->subYear()->timestamp,
                            'status_transitions' => [
                                'paid_at' => now()->subYear()->timestamp,
                            ],
                            'hosted_invoice_url' => 'https://stripe.example.test/invoices/in_backfill_003',
                            'invoice_pdf' => 'https://stripe.example.test/invoices/in_backfill_003.pdf',
                        ],
                    ],
                    'has_more' => false,
                ],
            ],
        ));

        $sinceDate = now()->subWeek()->toDateString();

        $response = $this->actingAs($user)
            ->withSession(['tenant_id' => $tenant->id])
            ->post(route('app.billing.invoices.sync'), [
                'since_date' => $sinceDate,
                'max_invoices' => 2,
            ]);

        $response->assertRedirect(route('app.billing'));
        $response->assertSessionHas('success', 'Synced 2 Stripe invoice(s).');
        $this->assertCount(1, $capturedInvoiceQueryParams);
        $this->assertSame(2, $capturedInvoiceQueryParams[0]['limit']);
        $this->assertSame(now()->subWeek()->startOfDay()->timestamp, $capturedInvoiceQueryParams[0]['created']['gte']);
        $this->assertSame(1, SaasInvoice::query()->where('stripe_invoice_id', 'in_backfill_001')->count());
        $this->assertSame(1, SaasInvoice::query()->where('stripe_invoice_id', 'in_backfill_002')->count());
        $this->assertSame(0, SaasInvoice::query()->where('stripe_invoice_id', 'in_backfill_003')->count());

        $invoice = SaasInvoice::query()->where('stripe_invoice_id', 'in_backfill_001')->firstOrFail();
        $tenant->refresh();

        $this->assertSame($tenant->id, $invoice->tenant_id);
        $this->assertSame('paid', $invoice->status);
        $this->assertSame(469000, $invoice->amount_due);
        $this->assertSame('https://stripe.example.test/invoices/in_backfill_001', $invoice->hosted_invoice_url);
        $this->assertNotNull($tenant->stripe_invoice_last_synced_at);
        $this->assertSame(2, $tenant->stripe_invoice_last_sync_count);

        $page = $this->actingAs($user)
            ->withSession(['tenant_id' => $tenant->id])
            ->get('/app/billing');

        $page->assertOk();
        $page->assertSeeText('ประวัติใบแจ้งหนี้ SaaS');
        $page->assertSeeText('ใบแจ้งหนี้ที่เก็บไว้: 2');
        $page->assertSeeText('รอบล่าสุด 2 ใบ');
        $page->assertSee('data-target="#invoiceSyncSettingsModal"', false);
        $page->assertSeeText('30 วัน');
        $page->assertSeeText('90 วัน');
        $page->assertSeeText('1 ปี');
        $page->assertSeeText('ทั้งหมด');
    }

    public function test_billing_page_can_backfill_stripe_invoices_without_sync_metadata_columns(): void
    {
        PlatformSetting::current()->update([
            'stripe_enabled' => true,
            'stripe_mode' => 'test',
            'stripe_publishable_key' => 'pk_test_invoice_sync_legacy',
            'stripe_secret_key' => 'sk_test_invoice_sync_legacy',
            'stripe_webhook_secret' => 'whsec_invoice_sync_legacy',
        ]);

        $plan = Plan::query()->create([
            'name' => 'Basic',
            'slug' => 'basic',
            'price_monthly' => 499,
            'is_active' => true,
            'sort_order' => 1,
            'stripe_yearly_price_id' => 'price_test_basic_yearly',
            'stripe_prepaid_annual_price_id' => 'price_test_basic_prepaid',
        ]);

        $tenant = Tenant::query()->create([
            'name' => 'Legacy Invoice Sync Dorm',
            'plan_id' => $plan->id,
            'plan' => 'basic',
            'status' => 'active',
            'subscription_status' => 'active',
            'billing_option' => 'prepaid_annual',
            'stripe_customer_id' => 'cus_invoice_sync_legacy_001',
        ]);

        Schema::table('tenants', function ($table): void {
            $table->dropColumn([
                'stripe_invoice_last_synced_at',
                'stripe_invoice_last_sync_count',
            ]);
        });

        $user = User::factory()->create([
            'tenant_id' => $tenant->id,
            'role' => 'owner',
            'email_verified_at' => now(),
        ]);

        $this->app->instance('billing.stripe_client_factory', $this->fakeStripeClientFactory(
            invoicePages: [
                [
                    'data' => [
                        [
                            'id' => 'in_backfill_legacy_001',
                            'customer' => 'cus_invoice_sync_legacy_001',
                            'status' => 'paid',
                            'currency' => 'thb',
                            'amount_due' => 469000,
                            'amount_paid' => 469000,
                            'amount_remaining' => 0,
                            'period_start' => now()->startOfDay()->timestamp,
                            'period_end' => now()->addYear()->startOfDay()->timestamp,
                            'created' => now()->subDay()->timestamp,
                            'status_transitions' => [
                                'paid_at' => now()->subDay()->timestamp,
                            ],
                            'hosted_invoice_url' => 'https://stripe.example.test/invoices/in_backfill_legacy_001',
                            'invoice_pdf' => 'https://stripe.example.test/invoices/in_backfill_legacy_001.pdf',
                        ],
                    ],
                    'has_more' => false,
                ],
            ],
        ));

        $response = $this->actingAs($user)
            ->withSession(['tenant_id' => $tenant->id])
            ->post(route('app.billing.invoices.sync'), [
                'max_invoices' => 10,
            ]);

        $response->assertRedirect(route('app.billing'));
        $response->assertSessionHas('success', 'Synced 1 Stripe invoice(s).');
        $response->assertSessionHas('warning', 'Stripe invoices synced, but sync summary requires the latest billing migration.');
        $this->assertSame(1, SaasInvoice::query()->where('stripe_invoice_id', 'in_backfill_legacy_001')->count());

        $page = $this->actingAs($user)
            ->withSession(['tenant_id' => $tenant->id])
            ->get('/app/billing');

        $page->assertOk();
        $page->assertSeeText('ประวัติใบแจ้งหนี้ SaaS');
        $page->assertSeeText('ใบแจ้งหนี้ที่เก็บไว้: 1');
        $page->assertDontSeeText('ซิงก์ล่าสุด');
        $page->assertDontSeeText('ยังไม่เคยซิงก์');
    }

    private function fakeStripeClientFactory(ArrayObject $capturedCheckoutPayloads = new ArrayObject(), ArrayObject $capturedInvoiceQueryParams = new ArrayObject(), array $invoicePages = []): \Closure
    {
        return static function (string $secretKey) use ($capturedCheckoutPayloads, $capturedInvoiceQueryParams, $invoicePages): object {
            return new class($capturedCheckoutPayloads, $capturedInvoiceQueryParams, $invoicePages) {
                public object $customers;
                public object $checkout;
                public object $billingPortal;
                public object $invoices;

                public function __construct(private readonly ArrayObject $capturedCheckoutPayloads, private readonly ArrayObject $capturedInvoiceQueryParams, private readonly array $invoicePages)
                {
                    $this->customers = new class {
                        public function create(array $payload): object
                        {
                            return (object) [
                                'id' => 'cus_fake_generated_001',
                                'payload' => $payload,
                            ];
                        }
                    };

                    $this->checkout = new class($this->capturedCheckoutPayloads) {
                        public object $sessions;

                        public function __construct(ArrayObject $capturedCheckoutPayloads)
                        {
                            $this->sessions = new class($capturedCheckoutPayloads) {
                                public function __construct(private readonly ArrayObject $capturedCheckoutPayloads)
                                {
                                }

                                public function create(array $payload): object
                                {
                                    $this->capturedCheckoutPayloads[] = $payload;

                                    return (object) [
                                        'url' => 'https://stripe.example.test/checkout',
                                    ];
                                }

                                public function retrieve(string $sessionId, array $params = []): object
                                {
                                    return (object) [
                                        'id' => $sessionId,
                                        'status' => 'complete',
                                        'payment_status' => 'paid',
                                        'metadata' => $params,
                                    ];
                                }
                            };
                        }
                    };

                    $this->billingPortal = new class {
                        public object $sessions;

                        public function __construct()
                        {
                            $this->sessions = new class {
                                public function create(array $payload): object
                                {
                                    return (object) [
                                        'url' => 'https://stripe.example.test/portal',
                                        'payload' => $payload,
                                    ];
                                }
                            };
                        }
                    };

                    $this->invoices = new class($this->capturedInvoiceQueryParams, $this->invoicePages) {
                        private int $pageIndex = 0;

                        public function __construct(private readonly ArrayObject $capturedInvoiceQueryParams, private readonly array $invoicePages)
                        {
                        }

                        public function all(array $params): object
                        {
                            $this->capturedInvoiceQueryParams[] = $params;
                            $page = $this->invoicePages[$this->pageIndex] ?? ['data' => [], 'has_more' => false];
                            $this->pageIndex++;

                            return (object) [
                                'data' => $page['data'],
                                'has_more' => $page['has_more'],
                                'params' => $params,
                            ];
                        }
                    };
                }
            };
        };
    }
}
