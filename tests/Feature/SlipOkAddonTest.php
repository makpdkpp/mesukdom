<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Contract;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Plan;
use App\Models\PlatformSetting;
use App\Models\Room;
use App\Models\SlipVerificationUsage;
use App\Models\Tenant;
use App\Models\User;
use App\Services\SlipQrDecoder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

final class SlipOkAddonTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_can_manage_global_slipok_settings_plan_quota_and_tenant_plan(): void
    {
        $trial = Plan::query()->create([
            'name' => 'Trial',
            'slug' => 'trial',
            'price_monthly' => 0,
            'limits' => ['rooms' => 20, 'staff' => 1, 'slipok_enabled' => false, 'slipok_monthly_limit' => 0],
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $pro = Plan::query()->create([
            'name' => 'Pro',
            'slug' => 'pro',
            'price_monthly' => 999,
            'limits' => ['rooms' => 300, 'staff' => 10, 'slipok_enabled' => true, 'slipok_monthly_limit' => 150],
            'is_active' => true,
            'sort_order' => 2,
        ]);

        $tenant = Tenant::query()->create([
            'name' => 'Addon Dorm',
            'domain' => 'addon.local',
            'plan_id' => $trial->id,
            'plan' => 'trial',
            'status' => 'active',
        ]);

        $admin = User::factory()->create([
            'tenant_id' => null,
            'role' => 'super_admin',
            'email_verified_at' => now(),
        ]);

        $this->actingAs($admin)
            ->post(route('admin.slipok.settings.update'), [
                'slipok_enabled' => '1',
                'slipok_api_url' => 'https://connect.slip2go.com/api/verify-slip/qr-base64/info',
                'slipok_api_secret' => 'super-secret-key',
                'slipok_secret_header_name' => 'X-API-SECRET',
                'slipok_timeout_seconds' => 12,
            ])
            ->assertRedirect();

        $setting = PlatformSetting::current();

        $this->assertTrue($setting->slipok_enabled);
        $this->assertSame('https://connect.slip2go.com/api/verify-slip/qr-base64/info', $setting->slipok_api_url);
        $this->assertSame('super-secret-key', $setting->slipok_api_secret);
        $this->assertSame('Authorization', $setting->slipok_secret_header_name);
        $this->assertSame(12, $setting->slipok_timeout_seconds);

        $stored = DB::table('platform_settings')->first();

        self::assertNotNull($stored);
        $this->assertNotSame('super-secret-key', $stored->slipok_api_secret);

        $this->actingAs($admin)
            ->patch(route('admin.plans.slipok.update', $trial), [
                'slipok_enabled' => '1',
                'slipok_monthly_limit' => 25,
            ])
            ->assertRedirect();

        $trial->refresh();
        $this->assertTrue($trial->supportsSlipOk());
        $this->assertSame(25, $trial->slipOkMonthlyLimit());

        $this->actingAs($admin)
            ->patch(route('admin.tenants.plan.update', $tenant), [
                'plan_id' => $pro->id,
            ])
            ->assertRedirect();

        $tenant->refresh();
        $this->assertSame($pro->id, $tenant->plan_id);
        $this->assertSame('pro', $tenant->plan);
    }

    public function test_resident_slip_upload_attempts_global_slipok_verification_when_plan_allows_it(): void
    {
        Storage::fake('local');
        Http::fake([
            'https://connect.slip2go.com/api/verify-slip/qr-base64/info' => Http::response([
                'code' => '200000',
                'message' => 'Slip found.',
                'data' => [
                    'amount' => 5200,
                    'referenceId' => '9bf992c1-bc5f-4b4e-b62b-a31ceff804fd-11557',
                    'receiver' => [
                        'account' => [
                            'proxy' => [
                                'type' => 'MSISDN',
                                'account' => '0812345678',
                            ],
                        ],
                    ],
                ],
            ], 200),
        ]);

        $plan = Plan::query()->create([
            'name' => 'Basic',
            'slug' => 'basic',
            'price_monthly' => 499,
            'limits' => ['rooms' => 80, 'staff' => 3, 'slipok_enabled' => true, 'slipok_monthly_limit' => 5],
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $tenant = Tenant::query()->create([
            'name' => 'Slip Verify Dorm',
            'domain' => 'slip-verify.local',
            'plan_id' => $plan->id,
            'plan' => $plan->slug,
            'status' => 'active',
            'promptpay_number' => '0812345678',
        ]);

        $setting = PlatformSetting::current();
        $setting->slipok_enabled = true;
        $setting->slipok_api_url = 'https://connect.slip2go.com/api/verify-slip/qr-base64/info';
        $setting->slipok_api_secret = 'platform-secret';
        $setting->slipok_secret_header_name = 'Authorization';
        $setting->slipok_timeout_seconds = 10;
        $setting->save();

        $room = Room::withoutGlobalScopes()->create([
            'tenant_id' => $tenant->id,
            'room_number' => 'SV-101',
            'floor' => 1,
            'room_type' => 'Standard',
            'price' => 5200,
            'status' => 'occupied',
        ]);

        $customer = Customer::withoutGlobalScopes()->create([
            'tenant_id' => $tenant->id,
            'room_id' => $room->id,
            'name' => 'Slip Resident',
        ]);

        $contract = Contract::withoutGlobalScopes()->create([
            'tenant_id' => $tenant->id,
            'customer_id' => $customer->id,
            'room_id' => $room->id,
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'deposit' => 5000,
            'monthly_rent' => 5200,
            'status' => 'active',
        ]);

        $invoice = Invoice::withoutGlobalScopes()->create([
            'tenant_id' => $tenant->id,
            'contract_id' => $contract->id,
            'customer_id' => $customer->id,
            'room_id' => $room->id,
            'total_amount' => 5200,
            'water_fee' => 0,
            'electricity_fee' => 0,
            'service_fee' => 0,
            'status' => 'sent',
            'due_date' => now()->addDays(7)->toDateString(),
        ]);

        $response = $this->post(route('resident.invoice.pay-slip', $invoice->public_id), [
            'amount' => 5200,
            'payment_date' => now()->toDateString(),
            'slip' => UploadedFile::fake()->image('slip.jpg', 400, 300),
        ]);

        $response->assertRedirect();

        $payment = Payment::withoutGlobalScopes()->where('invoice_id', $invoice->id)->firstOrFail();
        $invoice->refresh();

        $this->assertSame('approved', $payment->status);
        $this->assertNotNull($payment->receipt_no);
        $this->assertSame('verified', $payment->verification_status);
        $this->assertSame('slipok', $payment->verification_provider);
        $this->assertSame('Slip found.', $payment->verification_note);
        $this->assertNotNull($payment->verification_checked_at);
        $this->assertSame('paid', $invoice->status);
        $this->assertNotNull($payment->slip_path);
        $this->assertTrue(Storage::disk('local')->exists((string) $payment->slip_path));

        $this->assertDatabaseHas('slip_verification_usages', [
            'tenant_id' => $tenant->id,
            'plan_id' => $plan->id,
            'payment_id' => $payment->id,
            'provider' => 'slipok',
            'status' => 'verified',
            'usage_month' => now()->format('Y-m'),
        ]);

        Http::assertSentCount(1);
        Http::assertSent(function ($request): bool {
            $imageBase64 = data_get($request->data(), 'payload.imageBase64');

            return $request->url() === 'https://connect.slip2go.com/api/verify-slip/qr-base64/info'
                && $request->hasHeader('Authorization', 'Bearer platform-secret')
                && is_string($imageBase64)
                && str_starts_with($imageBase64, 'data:image/');
        });
    }

    public function test_resident_slip_upload_uses_next_global_receipt_number_when_another_tenant_already_has_one(): void
    {
        Storage::fake('local');
        Http::fake([
            'https://connect.slip2go.com/api/verify-slip/qr-base64/info' => Http::response([
                'code' => '200000',
                'message' => 'Slip found.',
                'data' => [
                    'amount' => 5200,
                    'referenceId' => '9bf992c1-bc5f-4b4e-b62b-a31ceff804fd-11557',
                    'receiver' => [
                        'account' => [
                            'proxy' => [
                                'type' => 'MSISDN',
                                'account' => '0812345678',
                            ],
                        ],
                    ],
                ],
            ], 200),
        ]);

        $plan = Plan::query()->create([
            'name' => 'Basic',
            'slug' => 'basic',
            'price_monthly' => 499,
            'limits' => ['rooms' => 80, 'staff' => 3, 'slipok_enabled' => true, 'slipok_monthly_limit' => 5],
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $existingTenant = Tenant::query()->create([
            'name' => 'Existing Receipt Dorm',
            'domain' => 'existing-receipt.local',
            'plan_id' => $plan->id,
            'plan' => $plan->slug,
            'status' => 'active',
        ]);

        $newTenant = Tenant::query()->create([
            'name' => 'Slip Verify Dorm',
            'domain' => 'slip-verify.local',
            'plan_id' => $plan->id,
            'plan' => $plan->slug,
            'status' => 'active',
            'promptpay_number' => '0812345678',
        ]);

        $setting = PlatformSetting::current();
        $setting->slipok_enabled = true;
        $setting->slipok_api_url = 'https://connect.slip2go.com/api/verify-slip/qr-base64/info';
        $setting->slipok_api_secret = 'platform-secret';
        $setting->slipok_secret_header_name = 'Authorization';
        $setting->slipok_timeout_seconds = 10;
        $setting->save();

        $existingRoom = Room::withoutGlobalScopes()->create([
            'tenant_id' => $existingTenant->id,
            'room_number' => 'ER-101',
            'floor' => 1,
            'room_type' => 'Standard',
            'price' => 4200,
            'status' => 'occupied',
        ]);

        $existingCustomer = Customer::withoutGlobalScopes()->create([
            'tenant_id' => $existingTenant->id,
            'room_id' => $existingRoom->id,
            'name' => 'Existing Resident',
        ]);

        $existingContract = Contract::withoutGlobalScopes()->create([
            'tenant_id' => $existingTenant->id,
            'customer_id' => $existingCustomer->id,
            'room_id' => $existingRoom->id,
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'deposit' => 5000,
            'monthly_rent' => 4200,
            'status' => 'active',
        ]);

        $existingInvoice = Invoice::withoutGlobalScopes()->create([
            'tenant_id' => $existingTenant->id,
            'contract_id' => $existingContract->id,
            'customer_id' => $existingCustomer->id,
            'room_id' => $existingRoom->id,
            'total_amount' => 4200,
            'water_fee' => 0,
            'electricity_fee' => 0,
            'service_fee' => 0,
            'status' => 'paid',
            'due_date' => now()->addDays(7)->toDateString(),
        ]);

        Payment::withoutGlobalScopes()->create([
            'tenant_id' => $existingTenant->id,
            'invoice_id' => $existingInvoice->id,
            'amount' => 4200,
            'payment_date' => now()->toDateString(),
            'method' => 'cash',
            'status' => 'approved',
            'notes' => 'Existing approved payment',
            'receipt_no' => sprintf('REC-%s-%04d', now()->year, 1),
        ]);

        $room = Room::withoutGlobalScopes()->create([
            'tenant_id' => $newTenant->id,
            'room_number' => 'SV-101',
            'floor' => 1,
            'room_type' => 'Standard',
            'price' => 5200,
            'status' => 'occupied',
        ]);

        $customer = Customer::withoutGlobalScopes()->create([
            'tenant_id' => $newTenant->id,
            'room_id' => $room->id,
            'name' => 'Slip Resident',
        ]);

        $contract = Contract::withoutGlobalScopes()->create([
            'tenant_id' => $newTenant->id,
            'customer_id' => $customer->id,
            'room_id' => $room->id,
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'deposit' => 5000,
            'monthly_rent' => 5200,
            'status' => 'active',
        ]);

        $invoice = Invoice::withoutGlobalScopes()->create([
            'tenant_id' => $newTenant->id,
            'contract_id' => $contract->id,
            'customer_id' => $customer->id,
            'room_id' => $room->id,
            'total_amount' => 5200,
            'water_fee' => 0,
            'electricity_fee' => 0,
            'service_fee' => 0,
            'status' => 'sent',
            'due_date' => now()->addDays(7)->toDateString(),
        ]);

        $response = $this->post(route('resident.invoice.pay-slip', $invoice->public_id), [
            'amount' => 5200,
            'payment_date' => now()->toDateString(),
            'slip' => UploadedFile::fake()->image('slip.jpg', 400, 300),
        ]);

        $response->assertRedirect();

        $payment = Payment::withoutGlobalScopes()->where('invoice_id', $invoice->id)->firstOrFail();

        $this->assertSame('approved', $payment->status);
        $this->assertSame(sprintf('REC-%s-%04d', now()->year, 2), $payment->receipt_no);
    }

    public function test_resident_resubmitting_slip_reuses_existing_pending_payment_record(): void
    {
        Storage::fake('local');
        Http::fake([
            'https://connect.slip2go.com/api/verify-slip/qr-base64/info' => Http::response([
                'code' => '200404',
                'message' => 'Slip not found.',
            ], 200),
        ]);

        $plan = Plan::query()->create([
            'name' => 'Basic',
            'slug' => 'basic',
            'price_monthly' => 499,
            'limits' => ['rooms' => 80, 'staff' => 3, 'slipok_enabled' => true, 'slipok_monthly_limit' => 5],
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $tenant = Tenant::query()->create([
            'name' => 'Slip Verify Dorm',
            'domain' => 'slip-verify.local',
            'plan_id' => $plan->id,
            'plan' => $plan->slug,
            'status' => 'active',
            'promptpay_number' => '0812345678',
        ]);

        $setting = PlatformSetting::current();
        $setting->slipok_enabled = true;
        $setting->slipok_api_url = 'https://connect.slip2go.com/api/verify-slip/qr-base64/info';
        $setting->slipok_api_secret = 'platform-secret';
        $setting->slipok_secret_header_name = 'Authorization';
        $setting->slipok_timeout_seconds = 10;
        $setting->save();

        $room = Room::withoutGlobalScopes()->create([
            'tenant_id' => $tenant->id,
            'room_number' => 'SV-103',
            'floor' => 1,
            'room_type' => 'Standard',
            'price' => 5200,
            'status' => 'occupied',
        ]);

        $customer = Customer::withoutGlobalScopes()->create([
            'tenant_id' => $tenant->id,
            'room_id' => $room->id,
            'name' => 'Slip Resident',
        ]);

        $contract = Contract::withoutGlobalScopes()->create([
            'tenant_id' => $tenant->id,
            'customer_id' => $customer->id,
            'room_id' => $room->id,
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'deposit' => 5000,
            'monthly_rent' => 5200,
            'status' => 'active',
        ]);

        $invoice = Invoice::withoutGlobalScopes()->create([
            'tenant_id' => $tenant->id,
            'contract_id' => $contract->id,
            'customer_id' => $customer->id,
            'room_id' => $room->id,
            'total_amount' => 5200,
            'water_fee' => 0,
            'electricity_fee' => 0,
            'service_fee' => 0,
            'status' => 'sent',
            'due_date' => now()->addDays(7)->toDateString(),
        ]);

        $this->post(route('resident.invoice.pay-slip', $invoice->public_id), [
            'amount' => 5200,
            'payment_date' => now()->toDateString(),
            'slip' => UploadedFile::fake()->image('first-slip.jpg', 400, 300),
        ])->assertRedirect();

        $firstPayment = Payment::withoutGlobalScopes()->where('invoice_id', $invoice->id)->firstOrFail();

        $this->post(route('resident.invoice.pay-slip', $invoice->public_id), [
            'amount' => 5200,
            'payment_date' => now()->toDateString(),
            'slip' => UploadedFile::fake()->image('second-slip.jpg', 400, 300),
        ])->assertRedirect();

        $payments = Payment::withoutGlobalScopes()->where('invoice_id', $invoice->id)->get();
        $updatedPayment = Payment::withoutGlobalScopes()->findOrFail($firstPayment->id);

        $this->assertCount(1, $payments);
        $this->assertSame($firstPayment->id, $updatedPayment->id);
        $this->assertSame('pending', $updatedPayment->status);
        $this->assertSame('Re-submitted by resident via portal', $updatedPayment->notes);
        $this->assertNotSame($firstPayment->slip_path, $updatedPayment->slip_path);
    }

    public function test_resident_slip_upload_fails_when_slipok_amount_does_not_match_invoice_amount(): void
    {
        Storage::fake('local');
        Http::fake([
            'https://connect.slip2go.com/api/verify-slip/qr-base64/info' => Http::response([
                'code' => '200000',
                'message' => 'Slip found.',
                'data' => [
                    'amount' => 10,
                    'referenceId' => '9bf992c1-bc5f-4b4e-b62b-a31ceff804fd-11557',
                    'receiver' => [
                        'account' => [
                            'proxy' => [
                                'type' => 'MSISDN',
                                'account' => '0812345678',
                            ],
                        ],
                    ],
                ],
            ], 200),
        ]);

        $plan = Plan::query()->create([
            'name' => 'Basic',
            'slug' => 'basic',
            'price_monthly' => 499,
            'limits' => ['rooms' => 80, 'staff' => 3, 'slipok_enabled' => true, 'slipok_monthly_limit' => 5],
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $tenant = Tenant::query()->create([
            'name' => 'Slip Verify Dorm',
            'domain' => 'slip-verify.local',
            'plan_id' => $plan->id,
            'plan' => $plan->slug,
            'status' => 'active',
            'promptpay_number' => '0812345678',
        ]);

        $setting = PlatformSetting::current();
        $setting->slipok_enabled = true;
        $setting->slipok_api_url = 'https://connect.slip2go.com/api/verify-slip/qr-base64/info';
        $setting->slipok_api_secret = 'platform-secret';
        $setting->slipok_secret_header_name = 'Authorization';
        $setting->slipok_timeout_seconds = 10;
        $setting->save();

        $room = Room::withoutGlobalScopes()->create([
            'tenant_id' => $tenant->id,
            'room_number' => 'SV-102',
            'floor' => 1,
            'room_type' => 'Standard',
            'price' => 5200,
            'status' => 'occupied',
        ]);

        $customer = Customer::withoutGlobalScopes()->create([
            'tenant_id' => $tenant->id,
            'room_id' => $room->id,
            'name' => 'Slip Resident',
        ]);

        $contract = Contract::withoutGlobalScopes()->create([
            'tenant_id' => $tenant->id,
            'customer_id' => $customer->id,
            'room_id' => $room->id,
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'deposit' => 5000,
            'monthly_rent' => 5200,
            'status' => 'active',
        ]);

        $invoice = Invoice::withoutGlobalScopes()->create([
            'tenant_id' => $tenant->id,
            'contract_id' => $contract->id,
            'customer_id' => $customer->id,
            'room_id' => $room->id,
            'total_amount' => 5200,
            'water_fee' => 0,
            'electricity_fee' => 0,
            'service_fee' => 0,
            'status' => 'sent',
            'due_date' => now()->addDays(7)->toDateString(),
        ]);

        $response = $this->post(route('resident.invoice.pay-slip', $invoice->public_id), [
            'amount' => 5200,
            'payment_date' => now()->toDateString(),
            'slip' => UploadedFile::fake()->image('slip.jpg', 400, 300),
        ]);

        $response->assertRedirect();

        $payment = Payment::withoutGlobalScopes()->where('invoice_id', $invoice->id)->firstOrFail();
        $invoice->refresh();

        $this->assertSame('pending', $payment->status);
        $this->assertSame('failed', $payment->verification_status);
        $this->assertStringContainsString('amount mismatch', strtolower((string) $payment->verification_note));
        $this->assertSame('sent', $invoice->status);

        $this->assertDatabaseHas('slip_verification_usages', [
            'tenant_id' => $tenant->id,
            'plan_id' => $plan->id,
            'payment_id' => $payment->id,
            'provider' => 'slipok',
            'status' => 'failed',
            'usage_month' => now()->format('Y-m'),
        ]);
    }

    public function test_resident_slip_upload_skips_slipok_when_monthly_quota_is_exhausted(): void
    {
        Storage::fake('local');
        Http::fake();

        $plan = Plan::query()->create([
            'name' => 'Starter',
            'slug' => 'starter',
            'price_monthly' => 299,
            'limits' => ['rooms' => 50, 'staff' => 2, 'slipok_enabled' => true, 'slipok_monthly_limit' => 1],
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $tenant = Tenant::query()->create([
            'name' => 'Quota Dorm',
            'domain' => 'quota.local',
            'plan_id' => $plan->id,
            'plan' => $plan->slug,
            'status' => 'active',
        ]);

        $setting = PlatformSetting::current();
        $setting->slipok_enabled = true;
        $setting->slipok_api_url = 'https://connect.slip2go.com/api/verify-slip/qr-base64/info';
        $setting->slipok_api_secret = 'platform-secret';
        $setting->slipok_secret_header_name = 'Authorization';
        $setting->slipok_timeout_seconds = 10;
        $setting->save();

        $room = Room::withoutGlobalScopes()->create([
            'tenant_id' => $tenant->id,
            'room_number' => 'QT-101',
            'floor' => 1,
            'room_type' => 'Standard',
            'price' => 4600,
            'status' => 'occupied',
        ]);

        $customer = Customer::withoutGlobalScopes()->create([
            'tenant_id' => $tenant->id,
            'room_id' => $room->id,
            'name' => 'Quota Resident',
        ]);

        $contract = Contract::withoutGlobalScopes()->create([
            'tenant_id' => $tenant->id,
            'customer_id' => $customer->id,
            'room_id' => $room->id,
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'deposit' => 5000,
            'monthly_rent' => 4600,
            'status' => 'active',
        ]);

        $invoice = Invoice::withoutGlobalScopes()->create([
            'tenant_id' => $tenant->id,
            'contract_id' => $contract->id,
            'customer_id' => $customer->id,
            'room_id' => $room->id,
            'total_amount' => 4600,
            'water_fee' => 0,
            'electricity_fee' => 0,
            'service_fee' => 0,
            'status' => 'sent',
            'due_date' => now()->addDays(7)->toDateString(),
        ]);

        SlipVerificationUsage::withoutGlobalScopes()->create([
            'tenant_id' => $tenant->id,
            'plan_id' => $plan->id,
            'provider' => 'slipok',
            'usage_month' => now()->format('Y-m'),
            'status' => 'verified',
        ]);

        $response = $this->post(route('resident.invoice.pay-slip', $invoice->public_id), [
            'amount' => 4600,
            'payment_date' => now()->toDateString(),
            'slip' => UploadedFile::fake()->image('slip.jpg', 400, 300),
        ]);

        $response->assertRedirect();

        $payment = Payment::withoutGlobalScopes()->where('invoice_id', $invoice->id)->firstOrFail();

        $this->assertSame('skipped', $payment->verification_status);
        $this->assertStringContainsString('monthly quota', (string) $payment->verification_note);

        Http::assertNothingSent();
    }

    public function test_resident_slip_upload_fails_when_receiver_promptpay_does_not_match_tenant(): void
    {
        Storage::fake('local');
        Http::fake([
            'https://connect.slip2go.com/api/verify-slip/qr-base64/info' => Http::response([
                'code' => '200000',
                'message' => 'Slip found.',
                'data' => [
                    'amount' => 5200,
                    'referenceId' => 'receiver-mismatch-001',
                    'receiver' => [
                        'account' => [
                            'proxy' => [
                                'type' => 'MSISDN',
                                'account' => '0899999999',
                            ],
                        ],
                    ],
                ],
            ], 200),
        ]);

        $plan = Plan::query()->create([
            'name' => 'Basic',
            'slug' => 'basic',
            'price_monthly' => 499,
            'limits' => ['rooms' => 80, 'staff' => 3, 'slipok_enabled' => true, 'slipok_monthly_limit' => 5],
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $tenant = Tenant::query()->create([
            'name' => 'Slip Verify Dorm',
            'domain' => 'slip-verify.local',
            'plan_id' => $plan->id,
            'plan' => $plan->slug,
            'status' => 'active',
            'promptpay_number' => '0812345678',
        ]);

        $setting = PlatformSetting::current();
        $setting->slipok_enabled = true;
        $setting->slipok_api_url = 'https://connect.slip2go.com/api/verify-slip/qr-base64/info';
        $setting->slipok_api_secret = 'platform-secret';
        $setting->slipok_secret_header_name = 'Authorization';
        $setting->slipok_timeout_seconds = 10;
        $setting->save();

        $room = Room::withoutGlobalScopes()->create([
            'tenant_id' => $tenant->id,
            'room_number' => 'SV-104',
            'floor' => 1,
            'room_type' => 'Standard',
            'price' => 5200,
            'status' => 'occupied',
        ]);

        $customer = Customer::withoutGlobalScopes()->create([
            'tenant_id' => $tenant->id,
            'room_id' => $room->id,
            'name' => 'Slip Resident',
        ]);

        $contract = Contract::withoutGlobalScopes()->create([
            'tenant_id' => $tenant->id,
            'customer_id' => $customer->id,
            'room_id' => $room->id,
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'deposit' => 5000,
            'monthly_rent' => 5200,
            'status' => 'active',
        ]);

        $invoice = Invoice::withoutGlobalScopes()->create([
            'tenant_id' => $tenant->id,
            'contract_id' => $contract->id,
            'customer_id' => $customer->id,
            'room_id' => $room->id,
            'total_amount' => 5200,
            'water_fee' => 0,
            'electricity_fee' => 0,
            'service_fee' => 0,
            'status' => 'sent',
            'due_date' => now()->addDays(7)->toDateString(),
        ]);

        $response = $this->post(route('resident.invoice.pay-slip', $invoice->public_id), [
            'amount' => 5200,
            'payment_date' => now()->toDateString(),
            'slip' => UploadedFile::fake()->image('slip.jpg', 400, 300),
        ]);

        $response->assertRedirect();

        $payment = Payment::withoutGlobalScopes()->where('invoice_id', $invoice->id)->firstOrFail();

        $this->assertSame('failed', $payment->verification_status);
        $this->assertStringContainsString('receiver mismatch', strtolower((string) $payment->verification_note));
        $this->assertSame('pending', $payment->status);
    }

    public function test_resident_slip_upload_fails_when_reference_has_already_been_used_by_another_payment(): void
    {
        Storage::fake('local');
        Http::fake([
            'https://connect.slip2go.com/api/verify-slip/qr-base64/info' => Http::response([
                'code' => '200000',
                'message' => 'Slip found.',
                'data' => [
                    'amount' => 5200,
                    'referenceId' => 'duplicate-reference-001',
                    'transRef' => 'duplicate-trans-001',
                    'receiver' => [
                        'account' => [
                            'proxy' => [
                                'type' => 'MSISDN',
                                'account' => '0812345678',
                            ],
                        ],
                    ],
                ],
            ], 200),
        ]);

        $plan = Plan::query()->create([
            'name' => 'Basic',
            'slug' => 'basic',
            'price_monthly' => 499,
            'limits' => ['rooms' => 80, 'staff' => 3, 'slipok_enabled' => true, 'slipok_monthly_limit' => 5],
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $tenant = Tenant::query()->create([
            'name' => 'Slip Verify Dorm',
            'domain' => 'slip-verify.local',
            'plan_id' => $plan->id,
            'plan' => $plan->slug,
            'status' => 'active',
            'promptpay_number' => '0812345678',
        ]);

        $setting = PlatformSetting::current();
        $setting->slipok_enabled = true;
        $setting->slipok_api_url = 'https://connect.slip2go.com/api/verify-slip/qr-base64/info';
        $setting->slipok_api_secret = 'platform-secret';
        $setting->slipok_secret_header_name = 'Authorization';
        $setting->slipok_timeout_seconds = 10;
        $setting->save();

        $roomA = Room::withoutGlobalScopes()->create([
            'tenant_id' => $tenant->id,
            'room_number' => 'SV-105',
            'floor' => 1,
            'room_type' => 'Standard',
            'price' => 5200,
            'status' => 'occupied',
        ]);

        $customerA = Customer::withoutGlobalScopes()->create([
            'tenant_id' => $tenant->id,
            'room_id' => $roomA->id,
            'name' => 'Resident A',
        ]);

        $contractA = Contract::withoutGlobalScopes()->create([
            'tenant_id' => $tenant->id,
            'customer_id' => $customerA->id,
            'room_id' => $roomA->id,
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'deposit' => 5000,
            'monthly_rent' => 5200,
            'status' => 'active',
        ]);

        $invoiceA = Invoice::withoutGlobalScopes()->create([
            'tenant_id' => $tenant->id,
            'contract_id' => $contractA->id,
            'customer_id' => $customerA->id,
            'room_id' => $roomA->id,
            'total_amount' => 5200,
            'water_fee' => 0,
            'electricity_fee' => 0,
            'service_fee' => 0,
            'status' => 'paid',
            'due_date' => now()->addDays(7)->toDateString(),
        ]);

        Payment::withoutGlobalScopes()->create([
            'tenant_id' => $tenant->id,
            'invoice_id' => $invoiceA->id,
            'amount' => 5200,
            'payment_date' => now()->toDateString(),
            'method' => 'slip',
            'status' => 'approved',
            'verification_provider' => 'slipok',
            'verification_status' => 'verified',
            'verification_payload' => [
                'data' => [
                    'referenceId' => 'duplicate-reference-001',
                    'transRef' => 'duplicate-trans-001',
                ],
            ],
            'notes' => 'Existing slip payment',
            'receipt_no' => sprintf('REC-%s-%04d', now()->year, 1),
        ]);

        $roomB = Room::withoutGlobalScopes()->create([
            'tenant_id' => $tenant->id,
            'room_number' => 'SV-106',
            'floor' => 1,
            'room_type' => 'Standard',
            'price' => 5200,
            'status' => 'occupied',
        ]);

        $customerB = Customer::withoutGlobalScopes()->create([
            'tenant_id' => $tenant->id,
            'room_id' => $roomB->id,
            'name' => 'Resident B',
        ]);

        $contractB = Contract::withoutGlobalScopes()->create([
            'tenant_id' => $tenant->id,
            'customer_id' => $customerB->id,
            'room_id' => $roomB->id,
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'deposit' => 5000,
            'monthly_rent' => 5200,
            'status' => 'active',
        ]);

        $invoiceB = Invoice::withoutGlobalScopes()->create([
            'tenant_id' => $tenant->id,
            'contract_id' => $contractB->id,
            'customer_id' => $customerB->id,
            'room_id' => $roomB->id,
            'total_amount' => 5200,
            'water_fee' => 0,
            'electricity_fee' => 0,
            'service_fee' => 0,
            'status' => 'sent',
            'due_date' => now()->addDays(7)->toDateString(),
        ]);

        $response = $this->post(route('resident.invoice.pay-slip', $invoiceB->public_id), [
            'amount' => 5200,
            'payment_date' => now()->toDateString(),
            'slip' => UploadedFile::fake()->image('slip.jpg', 400, 300),
        ]);

        $response->assertRedirect();

        $payment = Payment::withoutGlobalScopes()->where('invoice_id', $invoiceB->id)->firstOrFail();

        $this->assertSame('failed', $payment->verification_status);
        $this->assertStringContainsString('duplicate slip reference', strtolower((string) $payment->verification_note));
        $this->assertSame('pending', $payment->status);
    }
}