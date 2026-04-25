<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Plan;
use App\Models\SlipVerificationUsage;
use App\Models\Tenant;
use App\Services\SlipOkService;
use App\Services\SlipQrDecoder;
use App\Services\SlipVerificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SlipVerificationServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_custom_package_slip_verification_uses_tenant_monthly_quota(): void
    {
        Storage::fake('local');
        Storage::disk('local')->put('slips/test-slip.jpg', 'fake-slip-image');
        Http::preventStrayRequests();

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
            'name' => 'Quota Dorm',
            'plan_id' => $plan->id,
            'plan' => $plan->slug,
            'status' => 'active',
            'subscribed_room_limit' => 2,
            'subscribed_slipok_enabled' => true,
            'subscribed_slipok_monthly_limit' => 2,
        ]);

        $invoice = Invoice::query()->create([
            'tenant_id' => $tenant->id,
            'total_amount' => 1200,
            'status' => 'sent',
            'due_date' => now()->addDays(7),
        ]);

        $payment = Payment::query()->create([
            'tenant_id' => $tenant->id,
            'invoice_id' => $invoice->id,
            'amount' => 1200,
            'payment_date' => now(),
            'method' => 'bank_transfer',
            'status' => 'pending',
            'slip_path' => 'slips/test-slip.jpg',
        ]);

        SlipVerificationUsage::query()->create([
            'tenant_id' => $tenant->id,
            'plan_id' => $plan->id,
            'payment_id' => $payment->id,
            'provider' => 'slipok',
            'usage_month' => now()->format('Y-m'),
            'status' => 'verified',
            'request_payload' => ['payload' => ['id' => 1]],
            'response_payload' => ['status' => 'verified'],
        ]);

        SlipVerificationUsage::query()->create([
            'tenant_id' => $tenant->id,
            'plan_id' => $plan->id,
            'payment_id' => $payment->id,
            'provider' => 'slipok',
            'usage_month' => now()->format('Y-m'),
            'status' => 'verified',
            'request_payload' => ['payload' => ['id' => 2]],
            'response_payload' => ['status' => 'verified'],
        ]);

        $service = new SlipVerificationService(new SlipQrDecoder(), new SlipOkService());

        $result = $service->verifyPayment($payment);

        $payment->refresh();

        $this->assertSame('skipped', $result['status']);
        $this->assertSame('Automatic slip verification monthly quota has been reached for this package.', $result['message']);
        $this->assertSame('skipped', $payment->verification_status);
        $this->assertSame('Automatic slip verification monthly quota has been reached for this package.', $payment->verification_note);
    }

    public function test_custom_package_slip_verification_respects_tenant_addon_toggle(): void
    {
        Storage::fake('local');
        Storage::disk('local')->put('slips/test-slip.jpg', 'fake-slip-image');
        Http::preventStrayRequests();

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
            'name' => 'Addon Disabled Dorm',
            'plan_id' => $plan->id,
            'plan' => $plan->slug,
            'status' => 'active',
            'subscribed_room_limit' => 2,
            'subscribed_slipok_enabled' => false,
            'subscribed_slipok_monthly_limit' => 0,
        ]);

        $invoice = Invoice::query()->create([
            'tenant_id' => $tenant->id,
            'total_amount' => 1200,
            'status' => 'sent',
            'due_date' => now()->addDays(7),
        ]);

        $payment = Payment::query()->create([
            'tenant_id' => $tenant->id,
            'invoice_id' => $invoice->id,
            'amount' => 1200,
            'payment_date' => now(),
            'method' => 'bank_transfer',
            'status' => 'pending',
            'slip_path' => 'slips/test-slip.jpg',
        ]);

        $service = new SlipVerificationService(new SlipQrDecoder(), new SlipOkService());

        $result = $service->verifyPayment($payment);

        $payment->refresh();

        $this->assertSame('skipped', $result['status']);
        $this->assertSame('Automatic slip verification is not included in the current package.', $result['message']);
        $this->assertSame('skipped', $payment->verification_status);
        $this->assertSame('Automatic slip verification is not included in the current package.', $payment->verification_note);
    }
}