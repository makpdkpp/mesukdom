<?php

namespace Tests\Feature;

use App\Http\Controllers\BillingController;
use App\Models\Plan;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
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

    public function test_checkout_success_sync_activates_tenant_from_magic_property_objects(): void
    {
        $plan = Plan::query()->create([
            'name' => 'Basic',
            'slug' => 'basic',
            'price_monthly' => 499,
            'is_active' => true,
            'sort_order' => 1,
            'stripe_price_id' => 'price_test_basic',
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
    }

    public function test_billing_page_shows_subscription_summary_even_without_invoices(): void
    {
        $plan = Plan::query()->create([
            'name' => 'Basic',
            'slug' => 'basic',
            'price_monthly' => 499,
            'is_active' => true,
            'sort_order' => 1,
            'stripe_price_id' => 'price_test_basic',
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
        $response->assertSeeText('Current Package');
        $response->assertSeeText('Basic');
        $response->assertSeeText('Subscription Status');
        $response->assertSeeText('Active');
        $response->assertSeeText('Stripe Customer');
        $response->assertSeeText('cus_summary_001');
        $response->assertSeeText('Stripe Subscription');
        $response->assertSeeText('sub_summary_001');
        $response->assertSeeText('No SaaS invoices yet');
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
}
