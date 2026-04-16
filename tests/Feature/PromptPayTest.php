<?php

namespace Tests\Feature;

use App\Models\Contract;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Room;
use App\Models\Tenant;
use App\Models\User;
use App\Services\PromptPayService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PromptPayTest extends TestCase
{
    use RefreshDatabase;

    // ─────────────────────────────────────────────────────────────────────────
    // PromptPayService unit tests
    // ─────────────────────────────────────────────────────────────────────────

    public function test_promptpay_payload_starts_with_emv_header(): void
    {
        $service = new PromptPayService();
        $payload = $service->buildPayload('0812345678');

        // Must start with "000201" (Payload Format Indicator = 01)
        $this->assertStringStartsWith('000201', $payload);
    }

    public function test_promptpay_payload_ends_with_4_char_crc(): void
    {
        $service  = new PromptPayService();
        $payload  = $service->buildPayload('0812345678', 1000.00);

        // Last 4 chars = CRC hex
        $crcPart = substr($payload, -4);
        $this->assertMatchesRegularExpression('/^[0-9A-F]{4}$/', $crcPart);
    }

    public function test_promptpay_payload_contains_normalised_phone(): void
    {
        $service = new PromptPayService();
        $payload = $service->buildPayload('0812345678');

        // Phone 0812345678 → 0066812345678
        $this->assertStringContainsString('0066812345678', $payload);
    }

    public function test_promptpay_payload_contains_amount_when_provided(): void
    {
        $service = new PromptPayService();
        $payload = $service->buildPayload('0812345678', 5000.00);

        $this->assertStringContainsString('5000.00', $payload);
    }

    public function test_promptpay_payload_omits_amount_when_null(): void
    {
        $service = new PromptPayService();
        $payload = $service->buildPayload('0812345678');

        // Tag 54 = amount; should not be present
        $this->assertStringNotContainsString('54', substr($payload, 0, 60));
    }

    public function test_generate_svg_returns_svg_content(): void
    {
        $service = new PromptPayService();
        $svg = $service->generateSvg('0812345678', 500.00);

        $this->assertStringContainsString('<svg', $svg);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Settings endpoint
    // ─────────────────────────────────────────────────────────────────────────

    public function test_owner_can_save_promptpay_number(): void
    {
        $tenant = Tenant::create(['name' => 'QR Dorm', 'domain' => 'qr.local', 'plan' => 'trial', 'status' => 'active']);
        $user   = User::factory()->create(['tenant_id' => $tenant->id, 'role' => 'owner', 'email_verified_at' => now()]);

        $this->actingAs($user)
            ->post(route('app.settings.update'), ['promptpay_number' => '0812345678'])
            ->assertRedirect();

        $this->assertDatabaseHas('tenants', ['id' => $tenant->id, 'promptpay_number' => '0812345678']);
    }

    public function test_owner_can_clear_promptpay_number(): void
    {
        $tenant = Tenant::create(['name' => 'QR Clear', 'domain' => 'qrclear.local', 'plan' => 'trial', 'status' => 'active', 'promptpay_number' => '0812345678']);
        $user   = User::factory()->create(['tenant_id' => $tenant->id, 'role' => 'owner', 'email_verified_at' => now()]);

        $this->actingAs($user)
            ->post(route('app.settings.update'), ['promptpay_number' => ''])
            ->assertRedirect();

        $this->assertDatabaseHas('tenants', ['id' => $tenant->id, 'promptpay_number' => null]);
    }

    public function test_settings_rejects_invalid_promptpay_number(): void
    {
        $tenant = Tenant::create(['name' => 'Invalid QR', 'domain' => 'invalidqr.local', 'plan' => 'trial', 'status' => 'active']);
        $user   = User::factory()->create(['tenant_id' => $tenant->id, 'role' => 'owner', 'email_verified_at' => now()]);

        $this->actingAs($user)
            ->post(route('app.settings.update'), ['promptpay_number' => 'ABC-INVALID'])
            ->assertSessionHasErrors('promptpay_number');
    }

    // ─────────────────────────────────────────────────────────────────────────
    // PromptPay QR route (owner portal)
    // ─────────────────────────────────────────────────────────────────────────

    public function test_promptpay_qr_route_returns_svg_for_configured_tenant(): void
    {
        $tenant   = Tenant::create(['name' => 'QR Route Dorm', 'domain' => 'qrroute.local', 'plan' => 'trial', 'status' => 'active', 'promptpay_number' => '0812345678']);
        $user     = User::factory()->create(['tenant_id' => $tenant->id, 'role' => 'owner', 'email_verified_at' => now()]);
        $room     = Room::withoutGlobalScopes()->create(['tenant_id' => $tenant->id, 'room_number' => 'QR-101', 'floor' => 1, 'room_type' => 'Standard', 'price' => 5000, 'status' => 'occupied']);
        $customer = Customer::withoutGlobalScopes()->create(['tenant_id' => $tenant->id, 'room_id' => $room->id, 'name' => 'QR Resident']);
        $contract = Contract::withoutGlobalScopes()->create(['tenant_id' => $tenant->id, 'customer_id' => $customer->id, 'room_id' => $room->id, 'start_date' => '2026-01-01', 'end_date' => '2026-12-31', 'deposit' => 5000, 'monthly_rent' => 5000, 'status' => 'active']);
        $invoice  = Invoice::withoutGlobalScopes()->create(['tenant_id' => $tenant->id, 'contract_id' => $contract->id, 'customer_id' => $customer->id, 'room_id' => $room->id, 'total_amount' => 5000, 'water_fee' => 0, 'electricity_fee' => 0, 'service_fee' => 0, 'status' => 'sent', 'due_date' => '2026-04-05']);

        $response = $this->actingAs($user)->get(route('app.invoices.promptpay-qr', $invoice));

        $response->assertOk();
        $response->assertHeader('Content-Type', 'image/svg+xml');
        $this->assertStringContainsString('<svg', $response->getContent());
    }

    public function test_promptpay_qr_route_returns_404_when_not_configured(): void
    {
        $tenant   = Tenant::create(['name' => 'No QR Dorm', 'domain' => 'noqr.local', 'plan' => 'trial', 'status' => 'active']);
        $user     = User::factory()->create(['tenant_id' => $tenant->id, 'role' => 'owner', 'email_verified_at' => now()]);
        $room     = Room::withoutGlobalScopes()->create(['tenant_id' => $tenant->id, 'room_number' => 'NQ-101', 'floor' => 1, 'room_type' => 'Standard', 'price' => 5000, 'status' => 'occupied']);
        $customer = Customer::withoutGlobalScopes()->create(['tenant_id' => $tenant->id, 'room_id' => $room->id, 'name' => 'No QR Resident']);
        $contract = Contract::withoutGlobalScopes()->create(['tenant_id' => $tenant->id, 'customer_id' => $customer->id, 'room_id' => $room->id, 'start_date' => '2026-01-01', 'end_date' => '2026-12-31', 'deposit' => 5000, 'monthly_rent' => 5000, 'status' => 'active']);
        $invoice  = Invoice::withoutGlobalScopes()->create(['tenant_id' => $tenant->id, 'contract_id' => $contract->id, 'customer_id' => $customer->id, 'room_id' => $room->id, 'total_amount' => 5000, 'water_fee' => 0, 'electricity_fee' => 0, 'service_fee' => 0, 'status' => 'sent', 'due_date' => '2026-04-05']);

        $this->actingAs($user)->get(route('app.invoices.promptpay-qr', $invoice))->assertNotFound();
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Resident invoice portal shows QR
    // ─────────────────────────────────────────────────────────────────────────

    public function test_resident_invoice_shows_promptpay_qr_when_configured(): void
    {
        $tenant   = Tenant::create(['name' => 'Res QR Dorm', 'domain' => 'resqr.local', 'plan' => 'trial', 'status' => 'active', 'promptpay_number' => '0812345678']);
        $room     = Room::withoutGlobalScopes()->create(['tenant_id' => $tenant->id, 'room_number' => 'RQ-101', 'floor' => 1, 'room_type' => 'Standard', 'price' => 5000, 'status' => 'occupied']);
        $customer = Customer::withoutGlobalScopes()->create(['tenant_id' => $tenant->id, 'room_id' => $room->id, 'name' => 'Res QR Resident']);
        $contract = Contract::withoutGlobalScopes()->create(['tenant_id' => $tenant->id, 'customer_id' => $customer->id, 'room_id' => $room->id, 'start_date' => '2026-01-01', 'end_date' => '2026-12-31', 'deposit' => 5000, 'monthly_rent' => 5000, 'status' => 'active']);
        $invoice  = Invoice::withoutGlobalScopes()->create(['tenant_id' => $tenant->id, 'contract_id' => $contract->id, 'customer_id' => $customer->id, 'room_id' => $room->id, 'total_amount' => 5000, 'water_fee' => 0, 'electricity_fee' => 0, 'service_fee' => 0, 'status' => 'sent', 'due_date' => '2026-04-30', 'public_id' => \Illuminate\Support\Str::uuid()]);

        $url      = \Illuminate\Support\Facades\URL::signedRoute('resident.invoice', $invoice->public_id);
        $response = $this->get($url);

        $response->assertOk();
        $response->assertSeeText('PromptPay QR Code');
        $response->assertSee('data:image/svg+xml;base64,', false);
    }

    public function test_resident_invoice_hides_promptpay_qr_when_not_configured(): void
    {
        $tenant   = Tenant::create(['name' => 'No Res QR', 'domain' => 'noresqr.local', 'plan' => 'trial', 'status' => 'active']);
        $room     = Room::withoutGlobalScopes()->create(['tenant_id' => $tenant->id, 'room_number' => 'NRQ-101', 'floor' => 1, 'room_type' => 'Standard', 'price' => 5000, 'status' => 'occupied']);
        $customer = Customer::withoutGlobalScopes()->create(['tenant_id' => $tenant->id, 'room_id' => $room->id, 'name' => 'No QR Resident 2']);
        $contract = Contract::withoutGlobalScopes()->create(['tenant_id' => $tenant->id, 'customer_id' => $customer->id, 'room_id' => $room->id, 'start_date' => '2026-01-01', 'end_date' => '2026-12-31', 'deposit' => 5000, 'monthly_rent' => 5000, 'status' => 'active']);
        $invoice  = Invoice::withoutGlobalScopes()->create(['tenant_id' => $tenant->id, 'contract_id' => $contract->id, 'customer_id' => $customer->id, 'room_id' => $room->id, 'total_amount' => 5000, 'water_fee' => 0, 'electricity_fee' => 0, 'service_fee' => 0, 'status' => 'sent', 'due_date' => '2026-04-30', 'public_id' => \Illuminate\Support\Str::uuid()]);

        $url      = \Illuminate\Support\Facades\URL::signedRoute('resident.invoice', $invoice->public_id);
        $response = $this->get($url);

        $response->assertOk();
        $response->assertDontSeeText('PromptPay QR Code');
    }
}
