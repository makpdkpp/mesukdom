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
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

final class PaymentSlipRecheckTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_recheck_failed_slipok_payment_and_auto_approve_when_verified(): void
    {
        Storage::fake('local');

        Http::fake([
            'https://connect.slip2go.com/api/verify-slip/qr-base64/info' => Http::response([
                'code' => '200000',
                'message' => 'Slip found.',
                'data' => [
                    'amount' => 5200,
                    'referenceId' => 'recheck-ok-001',
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
            'limits' => ['rooms' => 80, 'staff' => 3, 'slipok_enabled' => true, 'slipok_monthly_limit' => 10],
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $tenant = Tenant::query()->create([
            'name' => 'MesukDom Demo Residence',
            'domain' => 'mesukdom-demo.local',
            'plan_id' => $plan->id,
            'plan' => $plan->slug,
            'status' => 'active',
            'promptpay_number' => '0812345678',
        ]);

        $user = User::factory()->create([
            'tenant_id' => $tenant->id,
            'role' => 'owner',
            'email_verified_at' => now(),
        ]);

        $setting = PlatformSetting::current();
        $setting->slipok_enabled = true;
        $setting->slipok_api_url = 'https://connect.slip2go.com/api/verify-slip/qr-base64/info';
        $setting->slipok_api_secret = 'platform-secret';
        $setting->slipok_secret_header_name = 'Authorization';
        $setting->slipok_timeout_seconds = 10;
        $setting->save();

        $room = Room::query()->create([
            'tenant_id' => $tenant->id,
            'room_number' => 'R-101',
            'floor' => 1,
            'room_type' => 'Standard',
            'price' => 5200,
            'status' => 'occupied',
        ]);

        $customer = Customer::query()->create([
            'tenant_id' => $tenant->id,
            'room_id' => $room->id,
            'name' => 'Resident Recheck',
        ]);

        $contract = Contract::query()->create([
            'tenant_id' => $tenant->id,
            'customer_id' => $customer->id,
            'room_id' => $room->id,
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'deposit' => 5000,
            'monthly_rent' => 5200,
            'status' => 'active',
        ]);

        $invoice = Invoice::query()->create([
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

        $slipPath = 'slips/'.$tenant->id.'/failed-slip.jpg';
        Storage::disk('local')->put($slipPath, 'dummy-image-content');

        $payment = Payment::query()->create([
            'tenant_id' => $tenant->id,
            'invoice_id' => $invoice->id,
            'amount' => 5200,
            'payment_date' => now()->toDateString(),
            'method' => 'slip',
            'status' => 'pending',
            'slip_path' => $slipPath,
            'verification_provider' => 'slipok',
            'verification_status' => 'failed',
            'verification_note' => 'Initial verification failed.',
        ]);

        $response = $this->actingAs($user)
            ->withSession(['tenant_id' => $tenant->id])
            ->patch(route('app.payments.recheck-slip', $payment->id));

        $response->assertRedirect();
        $response->assertSessionHas('status', fn (string $message): bool => str_contains($message, 'SlipOK recheck completed'));

        $payment->refresh();
        $invoice->refresh();

        $this->assertSame('verified', $payment->verification_status);
        $this->assertSame('approved', $payment->status);
        $this->assertNotNull($payment->receipt_no);
        $this->assertSame('paid', $invoice->status);
    }

    public function test_owner_cannot_recheck_when_payment_is_not_failed(): void
    {
        Storage::fake('local');

        $tenant = Tenant::query()->create([
            'name' => 'MesukDom Demo Residence',
            'domain' => 'mesukdom-demo.local',
            'plan' => 'trial',
            'status' => 'active',
        ]);

        $user = User::factory()->create([
            'tenant_id' => $tenant->id,
            'role' => 'owner',
            'email_verified_at' => now(),
        ]);

        $room = Room::query()->create([
            'tenant_id' => $tenant->id,
            'room_number' => 'R-102',
            'floor' => 1,
            'room_type' => 'Standard',
            'price' => 5200,
            'status' => 'occupied',
        ]);

        $customer = Customer::query()->create([
            'tenant_id' => $tenant->id,
            'room_id' => $room->id,
            'name' => 'Resident No Recheck',
        ]);

        $contract = Contract::query()->create([
            'tenant_id' => $tenant->id,
            'customer_id' => $customer->id,
            'room_id' => $room->id,
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'deposit' => 5000,
            'monthly_rent' => 5200,
            'status' => 'active',
        ]);

        $invoice = Invoice::query()->create([
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

        $slipPath = 'slips/'.$tenant->id.'/already-verified.jpg';
        Storage::disk('local')->put($slipPath, 'dummy-image-content');

        $payment = Payment::query()->create([
            'tenant_id' => $tenant->id,
            'invoice_id' => $invoice->id,
            'amount' => 5200,
            'payment_date' => now()->toDateString(),
            'method' => 'slip',
            'status' => 'pending',
            'slip_path' => $slipPath,
            'verification_provider' => 'slipok',
            'verification_status' => 'verified',
        ]);

        $response = $this->actingAs($user)
            ->withSession(['tenant_id' => $tenant->id])
            ->patch(route('app.payments.recheck-slip', $payment->id));

        $response->assertRedirect();
        $response->assertSessionHas('error', 'Only failed SlipOK payments can be rechecked.');

        $payment->refresh();
        $this->assertSame('verified', $payment->verification_status);
        $this->assertSame('pending', $payment->status);
    }
}
