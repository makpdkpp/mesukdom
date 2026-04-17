<?php

namespace Tests\Feature;

use App\Models\Contract;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Room;
use App\Models\Tenant;
use App\Models\User;
use App\Mail\PaymentNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DormitoryFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_page_can_be_opened(): void
    {
        $tenant = Tenant::create([
            'name' => 'Demo Dorm',
            'domain' => 'demo.local',
            'plan' => 'trial',
            'status' => 'active',
        ]);

        $user = User::factory()->create([
            'tenant_id' => $tenant->id,
            'role' => 'owner',
        ]);

        $response = $this->actingAs($user)
            ->withSession(['tenant_id' => $tenant->id])
            ->get('/app/dashboard');

        $response->assertOk();
        $response->assertSee('Dormitory Dashboard');
        $response->assertSee('Room Status');
    }

    public function test_rooms_page_only_shows_active_tenant_rooms(): void
    {
        $tenantA = Tenant::create([
            'name' => 'A Dorm',
            'domain' => 'a.local',
            'plan' => 'pro',
            'status' => 'active',
        ]);

        $tenantB = Tenant::create([
            'name' => 'B Dorm',
            'domain' => 'b.local',
            'plan' => 'basic',
            'status' => 'active',
        ]);

        $user = User::factory()->create([
            'tenant_id' => $tenantA->id,
            'role' => 'owner',
        ]);

        Room::create([
            'tenant_id' => $tenantA->id,
            'room_number' => 'A-101',
            'floor' => 1,
            'room_type' => 'Standard',
            'price' => 3500,
            'status' => 'occupied',
        ]);

        Room::create([
            'tenant_id' => $tenantB->id,
            'room_number' => 'B-201',
            'floor' => 2,
            'room_type' => 'Deluxe',
            'price' => 4500,
            'status' => 'vacant',
        ]);

        $response = $this->actingAs($user)
            ->withSession(['tenant_id' => $tenantA->id])
            ->get('/app/rooms');

        $response->assertOk();
        $response->assertSee('A-101');
        $response->assertDontSee('B-201');
    }

    public function test_owner_can_update_and_delete_their_room(): void
    {
        $tenant = Tenant::create([
            'name' => 'CRUD Dorm',
            'domain' => 'crud.local',
            'plan' => 'trial',
            'status' => 'active',
        ]);

        $user = User::factory()->create([
            'tenant_id' => $tenant->id,
            'role' => 'owner',
            'email_verified_at' => now(),
        ]);

        $room = Room::create([
            'tenant_id' => $tenant->id,
            'room_number' => 'C-101',
            'floor' => 1,
            'room_type' => 'Standard',
            'price' => 3200,
            'status' => 'vacant',
        ]);

        $updateResponse = $this->actingAs($user)
            ->withSession(['tenant_id' => $tenant->id])
            ->put('/app/rooms/'.$room->id, [
                'building' => 'North Wing',
                'room_number' => 'C-101A',
                'floor' => 2,
                'room_type' => 'Deluxe',
                'price' => 4200,
                'status' => 'maintenance',
            ]);

        $updateResponse->assertRedirect();
        $this->assertDatabaseHas('rooms', [
            'id' => $room->id,
            'tenant_id' => $tenant->id,
            'building' => 'North Wing',
            'room_number' => 'C-101A',
            'floor' => 2,
            'room_type' => 'Deluxe',
            'status' => 'maintenance',
        ]);

        $deleteResponse = $this->actingAs($user)
            ->withSession(['tenant_id' => $tenant->id])
            ->delete('/app/rooms/'.$room->id);

        $deleteResponse->assertRedirect();
        $this->assertDatabaseMissing('rooms', [
            'id' => $room->id,
        ]);
    }

    public function test_owner_cannot_update_room_from_another_tenant(): void
    {
        $tenantA = Tenant::create([
            'name' => 'Tenant A',
            'domain' => 'tenant-a.local',
            'plan' => 'trial',
            'status' => 'active',
        ]);

        $tenantB = Tenant::create([
            'name' => 'Tenant B',
            'domain' => 'tenant-b.local',
            'plan' => 'trial',
            'status' => 'active',
        ]);

        $user = User::factory()->create([
            'tenant_id' => $tenantA->id,
            'role' => 'owner',
            'email_verified_at' => now(),
        ]);

        $foreignRoom = Room::withoutGlobalScopes()->create([
            'tenant_id' => $tenantB->id,
            'room_number' => 'B-999',
            'floor' => 9,
            'room_type' => 'Suite',
            'price' => 9999,
            'status' => 'vacant',
        ]);

        $response = $this->actingAs($user)
            ->withSession(['tenant_id' => $tenantA->id])
            ->put('/app/rooms/'.$foreignRoom->id, [
                'building' => 'Hack Tower',
                'room_number' => 'HACKED',
                'floor' => 1,
                'room_type' => 'Standard',
                'price' => 1000,
                'status' => 'occupied',
            ]);

        $response->assertNotFound();
        $this->assertDatabaseHas('rooms', [
            'id' => $foreignRoom->id,
            'tenant_id' => $tenantB->id,
            'room_number' => 'B-999',
        ]);
    }

    public function test_owner_can_update_and_delete_their_customer(): void
    {
        $tenant = Tenant::create([
            'name' => 'Resident CRUD Dorm',
            'domain' => 'resident-crud.local',
            'plan' => 'trial',
            'status' => 'active',
        ]);

        $user = User::factory()->create([
            'tenant_id' => $tenant->id,
            'role' => 'owner',
            'email_verified_at' => now(),
        ]);

        $room = Room::create([
            'tenant_id' => $tenant->id,
            'room_number' => 'R-101',
            'floor' => 1,
            'room_type' => 'Standard',
            'price' => 3500,
            'status' => 'occupied',
        ]);

        $customer = Customer::create([
            'tenant_id' => $tenant->id,
            'room_id' => $room->id,
            'name' => 'Original Resident',
            'phone' => '0800000000',
            'email' => 'resident@example.test',
        ]);

        $updateResponse = $this->actingAs($user)
            ->withSession(['tenant_id' => $tenant->id])
            ->put('/app/customers/'.$customer->id, [
                'name' => 'Updated Resident',
                'phone' => '0811111111',
                'email' => 'updated@example.test',
                'line_id' => 'updated-line',
                'id_card' => '1234567890123',
                'room_id' => $room->id,
            ]);

        $updateResponse->assertRedirect();
        $this->assertDatabaseHas('customers', [
            'id' => $customer->id,
            'tenant_id' => $tenant->id,
            'name' => 'Updated Resident',
            'phone' => '0811111111',
            'email' => 'updated@example.test',
            'line_id' => 'updated-line',
        ]);

        $deleteResponse = $this->actingAs($user)
            ->withSession(['tenant_id' => $tenant->id])
            ->delete('/app/customers/'.$customer->id);

        $deleteResponse->assertRedirect();
        $this->assertDatabaseMissing('customers', [
            'id' => $customer->id,
        ]);
    }

    public function test_owner_cannot_update_customer_from_another_tenant(): void
    {
        $tenantA = Tenant::create([
            'name' => 'Customer Tenant A',
            'domain' => 'customer-a.local',
            'plan' => 'trial',
            'status' => 'active',
        ]);

        $tenantB = Tenant::create([
            'name' => 'Customer Tenant B',
            'domain' => 'customer-b.local',
            'plan' => 'trial',
            'status' => 'active',
        ]);

        $user = User::factory()->create([
            'tenant_id' => $tenantA->id,
            'role' => 'owner',
            'email_verified_at' => now(),
        ]);

        $foreignCustomer = Customer::withoutGlobalScopes()->create([
            'tenant_id' => $tenantB->id,
            'name' => 'Foreign Resident',
            'phone' => '0899999999',
        ]);

        $response = $this->actingAs($user)
            ->withSession(['tenant_id' => $tenantA->id])
            ->put('/app/customers/'.$foreignCustomer->id, [
                'name' => 'HACKED CUSTOMER',
                'phone' => '0000000000',
                'email' => 'hack@example.test',
                'line_id' => 'hack',
                'id_card' => 'hack',
                'room_id' => null,
            ]);

        $response->assertNotFound();
        $this->assertDatabaseHas('customers', [
            'id' => $foreignCustomer->id,
            'tenant_id' => $tenantB->id,
            'name' => 'Foreign Resident',
        ]);
    }

    public function test_customers_page_displays_rental_history_for_current_tenant(): void
    {
        $tenant = Tenant::create([
            'name' => 'History Dorm',
            'domain' => 'history.local',
            'plan' => 'trial',
            'status' => 'active',
        ]);

        $user = User::factory()->create([
            'tenant_id' => $tenant->id,
            'role' => 'owner',
            'email_verified_at' => now(),
        ]);

        $room = Room::create([
            'tenant_id' => $tenant->id,
            'room_number' => 'H-101',
            'floor' => 1,
            'room_type' => 'Standard',
            'price' => 3800,
            'status' => 'occupied',
        ]);

        $customer = Customer::create([
            'tenant_id' => $tenant->id,
            'room_id' => $room->id,
            'name' => 'History Resident',
        ]);

        Contract::create([
            'tenant_id' => $tenant->id,
            'customer_id' => $customer->id,
            'room_id' => $room->id,
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'deposit' => 5000,
            'monthly_rent' => 3800,
            'status' => 'active',
        ]);

        $response = $this->actingAs($user)
            ->withSession(['tenant_id' => $tenant->id])
            ->get('/app/customers');

        $response->assertOk();
        $response->assertSee('History Resident');
        $response->assertDontSee('No contract history');
        $response->assertSee('H-101');
        $response->assertSee('1 contract(s)');
    }

    public function test_owner_can_update_and_delete_their_contract(): void
    {
        $tenant = Tenant::create([
            'name' => 'Contract CRUD Dorm',
            'domain' => 'contract-crud.local',
            'plan' => 'trial',
            'status' => 'active',
        ]);

        $user = User::factory()->create([
            'tenant_id' => $tenant->id,
            'role' => 'owner',
            'email_verified_at' => now(),
        ]);

        $room = Room::create([
            'tenant_id' => $tenant->id,
            'room_number' => 'K-101',
            'floor' => 1,
            'room_type' => 'Standard',
            'price' => 4000,
            'status' => 'vacant',
        ]);

        $customer = Customer::create([
            'tenant_id' => $tenant->id,
            'room_id' => $room->id,
            'name' => 'Contract Resident',
        ]);

        $contract = Contract::create([
            'tenant_id' => $tenant->id,
            'customer_id' => $customer->id,
            'room_id' => $room->id,
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'deposit' => 5000,
            'monthly_rent' => 4000,
            'status' => 'active',
        ]);

        $room->update(['status' => 'occupied']);

        $updateResponse = $this->actingAs($user)
            ->withSession(['tenant_id' => $tenant->id])
            ->put('/app/contracts/'.$contract->id, [
                'customer_id' => $customer->id,
                'room_id' => $room->id,
                'start_date' => '2026-02-01',
                'end_date' => '2026-11-30',
                'deposit' => 4500,
                'monthly_rent' => 4100,
                'status' => 'cancelled',
            ]);

        $updateResponse->assertRedirect();
        $this->assertDatabaseHas('contracts', [
            'id' => $contract->id,
            'tenant_id' => $tenant->id,
            'status' => 'cancelled',
            'monthly_rent' => 4100,
        ]);
        $this->assertDatabaseHas('rooms', [
            'id' => $room->id,
            'status' => 'vacant',
        ]);

        $deleteResponse = $this->actingAs($user)
            ->withSession(['tenant_id' => $tenant->id])
            ->delete('/app/contracts/'.$contract->id);

        $deleteResponse->assertRedirect();
        $this->assertDatabaseMissing('contracts', [
            'id' => $contract->id,
        ]);
    }

    public function test_owner_cannot_update_contract_from_another_tenant(): void
    {
        $tenantA = Tenant::create([
            'name' => 'Contract Tenant A',
            'domain' => 'contract-a.local',
            'plan' => 'trial',
            'status' => 'active',
        ]);

        $tenantB = Tenant::create([
            'name' => 'Contract Tenant B',
            'domain' => 'contract-b.local',
            'plan' => 'trial',
            'status' => 'active',
        ]);

        $user = User::factory()->create([
            'tenant_id' => $tenantA->id,
            'role' => 'owner',
            'email_verified_at' => now(),
        ]);

        $roomB = Room::withoutGlobalScopes()->create([
            'tenant_id' => $tenantB->id,
            'room_number' => 'B-301',
            'floor' => 3,
            'room_type' => 'Standard',
            'price' => 3900,
            'status' => 'occupied',
        ]);

        $customerB = Customer::withoutGlobalScopes()->create([
            'tenant_id' => $tenantB->id,
            'room_id' => $roomB->id,
            'name' => 'Foreign Contract Resident',
        ]);

        $foreignContract = Contract::withoutGlobalScopes()->create([
            'tenant_id' => $tenantB->id,
            'customer_id' => $customerB->id,
            'room_id' => $roomB->id,
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'deposit' => 5000,
            'monthly_rent' => 3900,
            'status' => 'active',
        ]);

        $response = $this->actingAs($user)
            ->withSession(['tenant_id' => $tenantA->id])
            ->put('/app/contracts/'.$foreignContract->id, [
                'customer_id' => $customerB->id,
                'room_id' => $roomB->id,
                'start_date' => '2026-01-01',
                'end_date' => '2026-10-31',
                'deposit' => 1000,
                'monthly_rent' => 1000,
                'status' => 'cancelled',
            ]);

        $response->assertNotFound();
        $this->assertDatabaseHas('contracts', [
            'id' => $foreignContract->id,
            'tenant_id' => $tenantB->id,
            'status' => 'active',
        ]);
    }

    public function test_owner_can_issue_invoice_and_record_approved_payment(): void
    {
        $tenant = Tenant::create([
            'name' => 'Billing Dorm',
            'domain' => 'billing.local',
            'plan' => 'trial',
            'status' => 'active',
        ]);

        $user = User::factory()->create([
            'tenant_id' => $tenant->id,
            'role' => 'owner',
            'email_verified_at' => now(),
        ]);

        $room = Room::create([
            'tenant_id' => $tenant->id,
            'room_number' => 'I-101',
            'floor' => 1,
            'room_type' => 'Standard',
            'price' => 4500,
            'status' => 'occupied',
        ]);

        $customer = Customer::create([
            'tenant_id' => $tenant->id,
            'room_id' => $room->id,
            'name' => 'Billing Resident',
        ]);

        $contract = Contract::create([
            'tenant_id' => $tenant->id,
            'customer_id' => $customer->id,
            'room_id' => $room->id,
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'deposit' => 5000,
            'monthly_rent' => 4500,
            'status' => 'active',
        ]);

        $invoiceResponse = $this->actingAs($user)
            ->withSession(['tenant_id' => $tenant->id])
            ->post('/app/invoices', [
                'contract_id' => $contract->id,
                'water_fee' => 120,
                'electricity_fee' => 380,
                'service_fee' => 100,
                'status' => 'sent',
                'due_date' => '2026-05-05',
            ]);

        $invoiceResponse->assertRedirect();
        $invoice = \App\Models\Invoice::query()->firstOrFail();
        $this->assertSame('5100.00', number_format((float) $invoice->total_amount, 2, '.', ''));

        $paymentResponse = $this->actingAs($user)
            ->withSession(['tenant_id' => $tenant->id])
            ->post('/app/payments', [
                'invoice_id' => $invoice->id,
                'amount' => 5100,
                'payment_date' => '2026-05-01',
                'method' => 'manual',
                'status' => 'approved',
                'notes' => 'Paid in full',
            ]);

        $paymentResponse->assertRedirect();
        $this->assertDatabaseHas('payments', [
            'tenant_id' => $tenant->id,
            'invoice_id' => $invoice->id,
            'status' => 'approved',
        ]);
        $this->assertDatabaseHas('invoices', [
            'id' => $invoice->id,
            'status' => 'paid',
        ]);
    }

    public function test_dashboard_displays_revenue_trend_for_current_tenant_only(): void
    {
        $tenantA = Tenant::create([
            'name' => 'Trend Dorm A',
            'domain' => 'trend-a.local',
            'plan' => 'trial',
            'status' => 'active',
        ]);

        $tenantB = Tenant::create([
            'name' => 'Trend Dorm B',
            'domain' => 'trend-b.local',
            'plan' => 'trial',
            'status' => 'active',
        ]);

        $user = User::factory()->create([
            'tenant_id' => $tenantA->id,
            'role' => 'owner',
            'email_verified_at' => now(),
        ]);

        $roomA = Room::create([
            'tenant_id' => $tenantA->id,
            'room_number' => 'TA-101',
            'floor' => 1,
            'room_type' => 'Standard',
            'price' => 4300,
            'status' => 'occupied',
        ]);

        $customerA = Customer::create([
            'tenant_id' => $tenantA->id,
            'room_id' => $roomA->id,
            'name' => 'Trend Resident A',
        ]);

        $contractA = Contract::create([
            'tenant_id' => $tenantA->id,
            'customer_id' => $customerA->id,
            'room_id' => $roomA->id,
            'start_date' => now()->subMonths(3)->startOfMonth()->toDateString(),
            'end_date' => now()->addMonths(8)->endOfMonth()->toDateString(),
            'deposit' => 5000,
            'monthly_rent' => 4300,
            'status' => 'active',
        ]);

        $invoiceA = Invoice::create([
            'tenant_id' => $tenantA->id,
            'contract_id' => $contractA->id,
            'customer_id' => $customerA->id,
            'room_id' => $roomA->id,
            'total_amount' => 4321,
            'water_fee' => 0,
            'electricity_fee' => 0,
            'service_fee' => 0,
            'status' => 'paid',
            'due_date' => now()->subMonth()->endOfMonth()->toDateString(),
        ]);

        $roomB = Room::withoutGlobalScopes()->create([
            'tenant_id' => $tenantB->id,
            'room_number' => 'TB-101',
            'floor' => 1,
            'room_type' => 'Standard',
            'price' => 9800,
            'status' => 'occupied',
        ]);

        $customerB = Customer::withoutGlobalScopes()->create([
            'tenant_id' => $tenantB->id,
            'room_id' => $roomB->id,
            'name' => 'Trend Resident B',
        ]);

        $contractB = Contract::withoutGlobalScopes()->create([
            'tenant_id' => $tenantB->id,
            'customer_id' => $customerB->id,
            'room_id' => $roomB->id,
            'start_date' => now()->subMonths(3)->startOfMonth()->toDateString(),
            'end_date' => now()->addMonths(8)->endOfMonth()->toDateString(),
            'deposit' => 5000,
            'monthly_rent' => 9800,
            'status' => 'active',
        ]);

        $invoiceB = Invoice::withoutGlobalScopes()->create([
            'tenant_id' => $tenantB->id,
            'contract_id' => $contractB->id,
            'customer_id' => $customerB->id,
            'room_id' => $roomB->id,
            'total_amount' => 9876,
            'water_fee' => 0,
            'electricity_fee' => 0,
            'service_fee' => 0,
            'status' => 'paid',
            'due_date' => now()->subMonth()->endOfMonth()->toDateString(),
        ]);

        Payment::create([
            'tenant_id' => $tenantA->id,
            'invoice_id' => $invoiceA->id,
            'amount' => 4321,
            'payment_date' => now()->subMonth()->toDateString(),
            'method' => 'manual',
            'status' => 'approved',
        ]);

        Payment::withoutGlobalScopes()->create([
            'tenant_id' => $tenantB->id,
            'invoice_id' => $invoiceB->id,
            'amount' => 9876,
            'payment_date' => now()->subMonth()->toDateString(),
            'method' => 'manual',
            'status' => 'approved',
        ]);

        Payment::create([
            'tenant_id' => $tenantA->id,
            'invoice_id' => $invoiceA->id,
            'amount' => 1111,
            'payment_date' => now()->subMonths(2)->toDateString(),
            'method' => 'manual',
            'status' => 'pending',
        ]);

        $response = $this->actingAs($user)
            ->withSession(['tenant_id' => $tenantA->id])
            ->get('/app/dashboard');

        $response->assertOk();
        $response->assertSee('Revenue Trend');
        $response->assertSee(now()->subMonth()->format('M Y'));
        $response->assertSee('4,321');
        $response->assertDontSee('9,876');
    }

    public function test_owner_cannot_create_payment_for_invoice_from_another_tenant(): void
    {
        $tenantA = Tenant::create([
            'name' => 'Payment Tenant A',
            'domain' => 'payment-a.local',
            'plan' => 'trial',
            'status' => 'active',
        ]);

        $tenantB = Tenant::create([
            'name' => 'Payment Tenant B',
            'domain' => 'payment-b.local',
            'plan' => 'trial',
            'status' => 'active',
        ]);

        $user = User::factory()->create([
            'tenant_id' => $tenantA->id,
            'role' => 'owner',
            'email_verified_at' => now(),
        ]);

        $roomB = Room::withoutGlobalScopes()->create([
            'tenant_id' => $tenantB->id,
            'room_number' => 'PB-101',
            'floor' => 1,
            'room_type' => 'Standard',
            'price' => 4000,
            'status' => 'occupied',
        ]);

        $customerB = Customer::withoutGlobalScopes()->create([
            'tenant_id' => $tenantB->id,
            'room_id' => $roomB->id,
            'name' => 'Foreign Payment Resident',
        ]);

        $contractB = Contract::withoutGlobalScopes()->create([
            'tenant_id' => $tenantB->id,
            'customer_id' => $customerB->id,
            'room_id' => $roomB->id,
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'deposit' => 5000,
            'monthly_rent' => 4000,
            'status' => 'active',
        ]);

        $invoiceB = \App\Models\Invoice::withoutGlobalScopes()->create([
            'tenant_id' => $tenantB->id,
            'contract_id' => $contractB->id,
            'customer_id' => $customerB->id,
            'room_id' => $roomB->id,
            'total_amount' => 4000,
            'water_fee' => 0,
            'electricity_fee' => 0,
            'service_fee' => 0,
            'status' => 'sent',
            'due_date' => '2026-05-05',
        ]);

        $response = $this->actingAs($user)
            ->withSession(['tenant_id' => $tenantA->id])
            ->post('/app/payments', [
                'invoice_id' => $invoiceB->id,
                'amount' => 4000,
                'payment_date' => '2026-05-01',
                'method' => 'manual',
                'status' => 'approved',
            ]);

        $response->assertNotFound();
        $this->assertDatabaseMissing('payments', [
            'invoice_id' => $invoiceB->id,
            'tenant_id' => $tenantA->id,
        ]);
    }

    public function test_sent_invoice_becomes_overdue_automatically_for_current_tenant(): void
    {
        $tenantA = Tenant::create([
            'name' => 'Invoice Status Dorm A',
            'domain' => 'invoice-status-a.local',
            'plan' => 'trial',
            'status' => 'active',
        ]);

        $tenantB = Tenant::create([
            'name' => 'Invoice Status Dorm B',
            'domain' => 'invoice-status-b.local',
            'plan' => 'trial',
            'status' => 'active',
        ]);

        $user = User::factory()->create([
            'tenant_id' => $tenantA->id,
            'role' => 'owner',
            'email_verified_at' => now(),
        ]);

        $roomA = Room::create([
            'tenant_id' => $tenantA->id,
            'room_number' => 'OV-101',
            'floor' => 1,
            'room_type' => 'Standard',
            'price' => 4000,
            'status' => 'occupied',
        ]);

        $customerA = Customer::create([
            'tenant_id' => $tenantA->id,
            'room_id' => $roomA->id,
            'name' => 'Overdue Resident',
        ]);

        $contractA = Contract::create([
            'tenant_id' => $tenantA->id,
            'customer_id' => $customerA->id,
            'room_id' => $roomA->id,
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'deposit' => 5000,
            'monthly_rent' => 4000,
            'status' => 'active',
        ]);

        $overdueCandidate = Invoice::create([
            'tenant_id' => $tenantA->id,
            'contract_id' => $contractA->id,
            'customer_id' => $customerA->id,
            'room_id' => $roomA->id,
            'total_amount' => 4000,
            'water_fee' => 0,
            'electricity_fee' => 0,
            'service_fee' => 0,
            'status' => 'sent',
            'due_date' => now()->subDay()->toDateString(),
        ]);

        $draftInvoice = Invoice::create([
            'tenant_id' => $tenantA->id,
            'contract_id' => $contractA->id,
            'customer_id' => $customerA->id,
            'room_id' => $roomA->id,
            'total_amount' => 4000,
            'water_fee' => 0,
            'electricity_fee' => 0,
            'service_fee' => 0,
            'status' => 'draft',
            'due_date' => now()->subDay()->toDateString(),
        ]);

        $roomB = Room::withoutGlobalScopes()->create([
            'tenant_id' => $tenantB->id,
            'room_number' => 'OV-201',
            'floor' => 2,
            'room_type' => 'Standard',
            'price' => 4100,
            'status' => 'occupied',
        ]);

        $customerB = Customer::withoutGlobalScopes()->create([
            'tenant_id' => $tenantB->id,
            'room_id' => $roomB->id,
            'name' => 'Other Tenant Resident',
        ]);

        $contractB = Contract::withoutGlobalScopes()->create([
            'tenant_id' => $tenantB->id,
            'customer_id' => $customerB->id,
            'room_id' => $roomB->id,
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'deposit' => 5000,
            'monthly_rent' => 4100,
            'status' => 'active',
        ]);

        $otherTenantInvoice = Invoice::withoutGlobalScopes()->create([
            'tenant_id' => $tenantB->id,
            'contract_id' => $contractB->id,
            'customer_id' => $customerB->id,
            'room_id' => $roomB->id,
            'total_amount' => 4100,
            'water_fee' => 0,
            'electricity_fee' => 0,
            'service_fee' => 0,
            'status' => 'sent',
            'due_date' => now()->subDay()->toDateString(),
        ]);

        $response = $this->actingAs($user)
            ->withSession(['tenant_id' => $tenantA->id])
            ->get('/app/invoices');

        $response->assertOk();
        $this->assertDatabaseHas('invoices', [
            'id' => $overdueCandidate->id,
            'status' => 'overdue',
        ]);
        $this->assertDatabaseHas('invoices', [
            'id' => $draftInvoice->id,
            'status' => 'draft',
        ]);
        $this->assertDatabaseHas('invoices', [
            'id' => $otherTenantInvoice->id,
            'status' => 'sent',
        ]);
    }

    public function test_overdue_invoice_becomes_paid_after_approved_payment(): void
    {
        $tenant = Tenant::create([
            'name' => 'Overdue Payment Dorm',
            'domain' => 'overdue-payment.local',
            'plan' => 'trial',
            'status' => 'active',
        ]);

        $user = User::factory()->create([
            'tenant_id' => $tenant->id,
            'role' => 'owner',
            'email_verified_at' => now(),
        ]);

        $room = Room::create([
            'tenant_id' => $tenant->id,
            'room_number' => 'OP-101',
            'floor' => 1,
            'room_type' => 'Standard',
            'price' => 4200,
            'status' => 'occupied',
        ]);

        $customer = Customer::create([
            'tenant_id' => $tenant->id,
            'room_id' => $room->id,
            'name' => 'Late Resident',
        ]);

        $contract = Contract::create([
            'tenant_id' => $tenant->id,
            'customer_id' => $customer->id,
            'room_id' => $room->id,
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'deposit' => 5000,
            'monthly_rent' => 4200,
            'status' => 'active',
        ]);

        $invoice = Invoice::create([
            'tenant_id' => $tenant->id,
            'contract_id' => $contract->id,
            'customer_id' => $customer->id,
            'room_id' => $room->id,
            'total_amount' => 4200,
            'water_fee' => 0,
            'electricity_fee' => 0,
            'service_fee' => 0,
            'status' => 'overdue',
            'due_date' => now()->subDays(7)->toDateString(),
        ]);

        $response = $this->actingAs($user)
            ->withSession(['tenant_id' => $tenant->id])
            ->post('/app/payments', [
                'invoice_id' => $invoice->id,
                'amount' => 4200,
                'payment_date' => now()->toDateString(),
                'method' => 'manual',
                'status' => 'approved',
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('invoices', [
            'id' => $invoice->id,
            'status' => 'paid',
        ]);
    }

    public function test_owner_can_download_invoice_pdf_for_current_tenant(): void
    {
        $tenant = Tenant::create([
            'name' => 'PDF Dorm',
            'domain' => 'pdf.local',
            'plan' => 'trial',
            'status' => 'active',
        ]);

        $user = User::factory()->create([
            'tenant_id' => $tenant->id,
            'role' => 'owner',
            'email_verified_at' => now(),
        ]);

        $room = Room::create([
            'tenant_id' => $tenant->id,
            'room_number' => 'PDF-101',
            'floor' => 1,
            'room_type' => 'Standard',
            'price' => 4300,
            'status' => 'occupied',
        ]);

        $customer = Customer::create([
            'tenant_id' => $tenant->id,
            'room_id' => $room->id,
            'name' => 'PDF Resident',
        ]);

        $contract = Contract::create([
            'tenant_id' => $tenant->id,
            'customer_id' => $customer->id,
            'room_id' => $room->id,
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'deposit' => 5000,
            'monthly_rent' => 4300,
            'status' => 'active',
        ]);

        $invoice = Invoice::create([
            'tenant_id' => $tenant->id,
            'contract_id' => $contract->id,
            'customer_id' => $customer->id,
            'room_id' => $room->id,
            'total_amount' => 4300,
            'water_fee' => 0,
            'electricity_fee' => 0,
            'service_fee' => 0,
            'status' => 'sent',
            'due_date' => now()->addDays(7)->toDateString(),
        ]);

        $response = $this->actingAs($user)
            ->withSession(['tenant_id' => $tenant->id])
            ->get('/app/invoices/'.$invoice->id.'/pdf');

        $response->assertOk();
        $response->assertHeader('content-type', 'application/pdf');
        $response->assertHeader('content-disposition');
    }

    public function test_owner_cannot_download_invoice_pdf_from_another_tenant(): void
    {
        $tenantA = Tenant::create([
            'name' => 'PDF Tenant A',
            'domain' => 'pdf-a.local',
            'plan' => 'trial',
            'status' => 'active',
        ]);

        $tenantB = Tenant::create([
            'name' => 'PDF Tenant B',
            'domain' => 'pdf-b.local',
            'plan' => 'trial',
            'status' => 'active',
        ]);

        $user = User::factory()->create([
            'tenant_id' => $tenantA->id,
            'role' => 'owner',
            'email_verified_at' => now(),
        ]);

        $roomB = Room::withoutGlobalScopes()->create([
            'tenant_id' => $tenantB->id,
            'room_number' => 'PDF-B-101',
            'floor' => 1,
            'room_type' => 'Standard',
            'price' => 4500,
            'status' => 'occupied',
        ]);

        $customerB = Customer::withoutGlobalScopes()->create([
            'tenant_id' => $tenantB->id,
            'room_id' => $roomB->id,
            'name' => 'Foreign PDF Resident',
        ]);

        $contractB = Contract::withoutGlobalScopes()->create([
            'tenant_id' => $tenantB->id,
            'customer_id' => $customerB->id,
            'room_id' => $roomB->id,
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'deposit' => 5000,
            'monthly_rent' => 4500,
            'status' => 'active',
        ]);

        $invoiceB = Invoice::withoutGlobalScopes()->create([
            'tenant_id' => $tenantB->id,
            'contract_id' => $contractB->id,
            'customer_id' => $customerB->id,
            'room_id' => $roomB->id,
            'total_amount' => 4500,
            'water_fee' => 0,
            'electricity_fee' => 0,
            'service_fee' => 0,
            'status' => 'sent',
            'due_date' => now()->addDays(7)->toDateString(),
        ]);

        $response = $this->actingAs($user)
            ->withSession(['tenant_id' => $tenantA->id])
            ->get('/app/invoices/'.$invoiceB->id.'/pdf');

        $response->assertNotFound();
    }

    public function test_owner_can_download_receipt_pdf_for_approved_payment(): void
    {
        $tenant = Tenant::create([
            'name' => 'Receipt Dorm',
            'domain' => 'receipt.local',
            'plan' => 'trial',
            'status' => 'active',
        ]);

        $user = User::factory()->create([
            'tenant_id' => $tenant->id,
            'role' => 'owner',
            'email_verified_at' => now(),
        ]);

        $room = Room::create([
            'tenant_id' => $tenant->id,
            'room_number' => 'RC-101',
            'floor' => 1,
            'room_type' => 'Standard',
            'price' => 4400,
            'status' => 'occupied',
        ]);

        $customer = Customer::create([
            'tenant_id' => $tenant->id,
            'room_id' => $room->id,
            'name' => 'Receipt Resident',
        ]);

        $contract = Contract::create([
            'tenant_id' => $tenant->id,
            'customer_id' => $customer->id,
            'room_id' => $room->id,
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'deposit' => 5000,
            'monthly_rent' => 4400,
            'status' => 'active',
        ]);

        $invoice = Invoice::create([
            'tenant_id' => $tenant->id,
            'contract_id' => $contract->id,
            'customer_id' => $customer->id,
            'room_id' => $room->id,
            'total_amount' => 4400,
            'water_fee' => 0,
            'electricity_fee' => 0,
            'service_fee' => 0,
            'status' => 'paid',
            'due_date' => now()->addDays(7)->toDateString(),
        ]);

        $payment = Payment::create([
            'tenant_id' => $tenant->id,
            'invoice_id' => $invoice->id,
            'amount' => 4400,
            'payment_date' => now()->toDateString(),
            'method' => 'manual',
            'status' => 'approved',
            'notes' => 'Approved payment receipt',
        ]);

        $response = $this->actingAs($user)
            ->withSession(['tenant_id' => $tenant->id])
            ->get('/app/payments/'.$payment->id.'/receipt');

        $response->assertOk();
        $response->assertHeader('content-type', 'application/pdf');
        $response->assertHeader('content-disposition');
    }

    public function test_owner_cannot_download_receipt_pdf_from_another_tenant(): void
    {
        $tenantA = Tenant::create([
            'name' => 'Receipt Tenant A',
            'domain' => 'receipt-a.local',
            'plan' => 'trial',
            'status' => 'active',
        ]);

        $tenantB = Tenant::create([
            'name' => 'Receipt Tenant B',
            'domain' => 'receipt-b.local',
            'plan' => 'trial',
            'status' => 'active',
        ]);

        $user = User::factory()->create([
            'tenant_id' => $tenantA->id,
            'role' => 'owner',
            'email_verified_at' => now(),
        ]);

        $roomB = Room::withoutGlobalScopes()->create([
            'tenant_id' => $tenantB->id,
            'room_number' => 'RC-B-101',
            'floor' => 1,
            'room_type' => 'Standard',
            'price' => 4600,
            'status' => 'occupied',
        ]);

        $customerB = Customer::withoutGlobalScopes()->create([
            'tenant_id' => $tenantB->id,
            'room_id' => $roomB->id,
            'name' => 'Foreign Receipt Resident',
        ]);

        $contractB = Contract::withoutGlobalScopes()->create([
            'tenant_id' => $tenantB->id,
            'customer_id' => $customerB->id,
            'room_id' => $roomB->id,
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'deposit' => 5000,
            'monthly_rent' => 4600,
            'status' => 'active',
        ]);

        $invoiceB = Invoice::withoutGlobalScopes()->create([
            'tenant_id' => $tenantB->id,
            'contract_id' => $contractB->id,
            'customer_id' => $customerB->id,
            'room_id' => $roomB->id,
            'total_amount' => 4600,
            'water_fee' => 0,
            'electricity_fee' => 0,
            'service_fee' => 0,
            'status' => 'paid',
            'due_date' => now()->addDays(7)->toDateString(),
        ]);

        $paymentB = Payment::withoutGlobalScopes()->create([
            'tenant_id' => $tenantB->id,
            'invoice_id' => $invoiceB->id,
            'amount' => 4600,
            'payment_date' => now()->toDateString(),
            'method' => 'manual',
            'status' => 'approved',
        ]);

        $response = $this->actingAs($user)
            ->withSession(['tenant_id' => $tenantA->id])
            ->get('/app/payments/'.$paymentB->id.'/receipt');

        $response->assertNotFound();
    }

    public function test_owner_can_upload_slip_with_payment(): void
    {
        Storage::fake('local');

        $tenant = Tenant::create([
            'name' => 'Slip Dorm',
            'domain' => 'slip.local',
            'plan' => 'trial',
            'status' => 'active',
        ]);

        $user = User::factory()->create([
            'tenant_id' => $tenant->id,
            'role' => 'owner',
            'email_verified_at' => now(),
        ]);

        $room = Room::create([
            'tenant_id' => $tenant->id,
            'room_number' => 'SL-101',
            'floor' => 1,
            'room_type' => 'Standard',
            'price' => 4700,
            'status' => 'occupied',
        ]);

        $customer = Customer::create([
            'tenant_id' => $tenant->id,
            'room_id' => $room->id,
            'name' => 'Slip Resident',
        ]);

        $contract = Contract::create([
            'tenant_id' => $tenant->id,
            'customer_id' => $customer->id,
            'room_id' => $room->id,
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'deposit' => 5000,
            'monthly_rent' => 4700,
            'status' => 'active',
        ]);

        $invoice = Invoice::create([
            'tenant_id' => $tenant->id,
            'contract_id' => $contract->id,
            'customer_id' => $customer->id,
            'room_id' => $room->id,
            'total_amount' => 4700,
            'water_fee' => 0,
            'electricity_fee' => 0,
            'service_fee' => 0,
            'status' => 'sent',
            'due_date' => now()->addDays(7)->toDateString(),
        ]);

        $slip = UploadedFile::fake()->image('receipt.jpg', 400, 300);

        $response = $this->actingAs($user)
            ->withSession(['tenant_id' => $tenant->id])
            ->post('/app/payments', [
                'invoice_id' => $invoice->id,
                'amount' => 4700,
                'payment_date' => now()->toDateString(),
                'method' => 'slip',
                'status' => 'pending',
                'slip' => $slip,
            ]);

        $response->assertRedirect();

        $payment = Payment::first();
        $this->assertNotNull($payment->slip_path);
        Storage::disk('local')->assertExists($payment->slip_path);
    }

    public function test_owner_cannot_view_slip_from_another_tenant(): void
    {
        Storage::fake('local');

        $tenantA = Tenant::create([
            'name' => 'Slip Tenant A',
            'domain' => 'slip-a.local',
            'plan' => 'trial',
            'status' => 'active',
        ]);

        $tenantB = Tenant::create([
            'name' => 'Slip Tenant B',
            'domain' => 'slip-b.local',
            'plan' => 'trial',
            'status' => 'active',
        ]);

        $user = User::factory()->create([
            'tenant_id' => $tenantA->id,
            'role' => 'owner',
            'email_verified_at' => now(),
        ]);

        $roomB = Room::withoutGlobalScopes()->create([
            'tenant_id' => $tenantB->id,
            'room_number' => 'SL-B-101',
            'floor' => 1,
            'room_type' => 'Standard',
            'price' => 4800,
            'status' => 'occupied',
        ]);

        $customerB = Customer::withoutGlobalScopes()->create([
            'tenant_id' => $tenantB->id,
            'room_id' => $roomB->id,
            'name' => 'Foreign Slip Resident',
        ]);

        $contractB = Contract::withoutGlobalScopes()->create([
            'tenant_id' => $tenantB->id,
            'customer_id' => $customerB->id,
            'room_id' => $roomB->id,
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'deposit' => 5000,
            'monthly_rent' => 4800,
            'status' => 'active',
        ]);

        $invoiceB = Invoice::withoutGlobalScopes()->create([
            'tenant_id' => $tenantB->id,
            'contract_id' => $contractB->id,
            'customer_id' => $customerB->id,
            'room_id' => $roomB->id,
            'total_amount' => 4800,
            'water_fee' => 0,
            'electricity_fee' => 0,
            'service_fee' => 0,
            'status' => 'sent',
            'due_date' => now()->addDays(7)->toDateString(),
        ]);

        Storage::disk('local')->put("slips/{$tenantB->id}/test-slip.jpg", 'fake-image-data');

        $paymentB = Payment::withoutGlobalScopes()->create([
            'tenant_id' => $tenantB->id,
            'invoice_id' => $invoiceB->id,
            'amount' => 4800,
            'payment_date' => now()->toDateString(),
            'method' => 'slip',
            'status' => 'pending',
            'slip_path' => "slips/{$tenantB->id}/test-slip.jpg",
        ]);

        $response = $this->actingAs($user)
            ->withSession(['tenant_id' => $tenantA->id])
            ->get('/app/payments/'.$paymentB->id.'/slip');

        $response->assertNotFound();
    }

    public function test_owner_can_approve_pending_payment_and_invoice_becomes_paid(): void
    {
        $tenant = Tenant::create([
            'name' => 'Approve Dorm',
            'domain' => 'approve.local',
            'plan' => 'trial',
            'status' => 'active',
        ]);

        $user = User::factory()->create([
            'tenant_id' => $tenant->id,
            'role' => 'owner',
            'email_verified_at' => now(),
        ]);

        $room = Room::create([
            'tenant_id' => $tenant->id,
            'room_number' => 'AP-101',
            'floor' => 1,
            'room_type' => 'Standard',
            'price' => 4900,
            'status' => 'occupied',
        ]);

        $customer = Customer::create([
            'tenant_id' => $tenant->id,
            'room_id' => $room->id,
            'name' => 'Approve Resident',
        ]);

        $contract = Contract::create([
            'tenant_id' => $tenant->id,
            'customer_id' => $customer->id,
            'room_id' => $room->id,
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'deposit' => 5000,
            'monthly_rent' => 4900,
            'status' => 'active',
        ]);

        $invoice = Invoice::create([
            'tenant_id' => $tenant->id,
            'contract_id' => $contract->id,
            'customer_id' => $customer->id,
            'room_id' => $room->id,
            'total_amount' => 4900,
            'water_fee' => 0,
            'electricity_fee' => 0,
            'service_fee' => 0,
            'status' => 'sent',
            'due_date' => now()->addDays(7)->toDateString(),
        ]);

        $payment = Payment::create([
            'tenant_id' => $tenant->id,
            'invoice_id' => $invoice->id,
            'amount' => 4900,
            'payment_date' => now()->toDateString(),
            'method' => 'slip',
            'status' => 'pending',
        ]);

        $response = $this->actingAs($user)
            ->withSession(['tenant_id' => $tenant->id])
            ->patch('/app/payments/'.$payment->id.'/approve');

        $response->assertRedirect();

        $this->assertDatabaseHas('payments', ['id' => $payment->id, 'status' => 'approved']);
        $this->assertDatabaseHas('invoices', ['id' => $invoice->id, 'status' => 'paid']);
    }

    public function test_owner_can_reject_pending_payment(): void
    {
        $tenant = Tenant::create([
            'name' => 'Reject Dorm',
            'domain' => 'reject.local',
            'plan' => 'trial',
            'status' => 'active',
        ]);

        $user = User::factory()->create([
            'tenant_id' => $tenant->id,
            'role' => 'owner',
            'email_verified_at' => now(),
        ]);

        $room = Room::create([
            'tenant_id' => $tenant->id,
            'room_number' => 'RJ-101',
            'floor' => 1,
            'room_type' => 'Standard',
            'price' => 5000,
            'status' => 'occupied',
        ]);

        $customer = Customer::create([
            'tenant_id' => $tenant->id,
            'room_id' => $room->id,
            'name' => 'Reject Resident',
        ]);

        $contract = Contract::create([
            'tenant_id' => $tenant->id,
            'customer_id' => $customer->id,
            'room_id' => $room->id,
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'deposit' => 5000,
            'monthly_rent' => 5000,
            'status' => 'active',
        ]);

        $invoice = Invoice::create([
            'tenant_id' => $tenant->id,
            'contract_id' => $contract->id,
            'customer_id' => $customer->id,
            'room_id' => $room->id,
            'total_amount' => 5000,
            'water_fee' => 0,
            'electricity_fee' => 0,
            'service_fee' => 0,
            'status' => 'sent',
            'due_date' => now()->addDays(7)->toDateString(),
        ]);

        $payment = Payment::create([
            'tenant_id' => $tenant->id,
            'invoice_id' => $invoice->id,
            'amount' => 5000,
            'payment_date' => now()->toDateString(),
            'method' => 'slip',
            'status' => 'pending',
        ]);

        $response = $this->actingAs($user)
            ->withSession(['tenant_id' => $tenant->id])
            ->patch('/app/payments/'.$payment->id.'/reject');

        $response->assertRedirect();

        $this->assertDatabaseHas('payments', ['id' => $payment->id, 'status' => 'rejected']);
        $this->assertDatabaseHas('invoices', ['id' => $invoice->id, 'status' => 'sent']);
    }

    public function test_owner_cannot_approve_payment_from_another_tenant(): void
    {
        $tenantA = Tenant::create([
            'name' => 'Approve Tenant A',
            'domain' => 'approve-a.local',
            'plan' => 'trial',
            'status' => 'active',
        ]);

        $tenantB = Tenant::create([
            'name' => 'Approve Tenant B',
            'domain' => 'approve-b.local',
            'plan' => 'trial',
            'status' => 'active',
        ]);

        $user = User::factory()->create([
            'tenant_id' => $tenantA->id,
            'role' => 'owner',
            'email_verified_at' => now(),
        ]);

        $roomB = Room::withoutGlobalScopes()->create([
            'tenant_id' => $tenantB->id,
            'room_number' => 'AP-B-101',
            'floor' => 1,
            'room_type' => 'Standard',
            'price' => 5100,
            'status' => 'occupied',
        ]);

        $customerB = Customer::withoutGlobalScopes()->create([
            'tenant_id' => $tenantB->id,
            'room_id' => $roomB->id,
            'name' => 'Foreign Approve Resident',
        ]);

        $contractB = Contract::withoutGlobalScopes()->create([
            'tenant_id' => $tenantB->id,
            'customer_id' => $customerB->id,
            'room_id' => $roomB->id,
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'deposit' => 5000,
            'monthly_rent' => 5100,
            'status' => 'active',
        ]);

        $invoiceB = Invoice::withoutGlobalScopes()->create([
            'tenant_id' => $tenantB->id,
            'contract_id' => $contractB->id,
            'customer_id' => $customerB->id,
            'room_id' => $roomB->id,
            'total_amount' => 5100,
            'water_fee' => 0,
            'electricity_fee' => 0,
            'service_fee' => 0,
            'status' => 'sent',
            'due_date' => now()->addDays(7)->toDateString(),
        ]);

        $paymentB = Payment::withoutGlobalScopes()->create([
            'tenant_id' => $tenantB->id,
            'invoice_id' => $invoiceB->id,
            'amount' => 5100,
            'payment_date' => now()->toDateString(),
            'method' => 'slip',
            'status' => 'pending',
        ]);

        $response = $this->actingAs($user)
            ->withSession(['tenant_id' => $tenantA->id])
            ->patch('/app/payments/'.$paymentB->id.'/approve');

        $response->assertNotFound();
        $this->assertDatabaseHas('payments', ['id' => $paymentB->id, 'status' => 'pending']);
    }

    public function test_resident_can_submit_slip_from_invoice_portal(): void
    {
        Storage::fake('local');

        $tenant = Tenant::create([
            'name' => 'Resident Portal Dorm',
            'domain' => 'resident-portal.local',
            'plan' => 'trial',
            'status' => 'active',
        ]);

        $room = Room::withoutGlobalScopes()->create([
            'tenant_id' => $tenant->id,
            'room_number' => 'RP-101',
            'floor' => 1,
            'room_type' => 'Standard',
            'price' => 5200,
            'status' => 'occupied',
        ]);

        $customer = Customer::withoutGlobalScopes()->create([
            'tenant_id' => $tenant->id,
            'room_id' => $room->id,
            'name' => 'Portal Resident',
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

        $slip = UploadedFile::fake()->image('slip.jpg', 400, 300);

        $response = $this->post(
            route('resident.invoice.pay-slip', $invoice->public_id),
            [
                'amount' => 5200,
                'payment_date' => now()->toDateString(),
                'slip' => $slip,
            ]
        );

        $response->assertRedirect();

        $payment = Payment::withoutGlobalScopes()->where('invoice_id', $invoice->id)->first();
        $this->assertNotNull($payment);
        $this->assertSame('pending', $payment->status);
        $this->assertSame('slip', $payment->method);
        $this->assertNotNull($payment->slip_path);
        Storage::disk('local')->assertExists($payment->slip_path);
        $this->assertSame((string) $tenant->id, (string) $payment->tenant_id);
    }

    public function test_resident_cannot_submit_slip_for_paid_invoice(): void
    {
        Storage::fake('local');

        $tenant = Tenant::create([
            'name' => 'Paid Invoice Dorm',
            'domain' => 'paid-invoice.local',
            'plan' => 'trial',
            'status' => 'active',
        ]);

        $room = Room::withoutGlobalScopes()->create([
            'tenant_id' => $tenant->id,
            'room_number' => 'PI-101',
            'floor' => 1,
            'room_type' => 'Standard',
            'price' => 5300,
            'status' => 'occupied',
        ]);

        $customer = Customer::withoutGlobalScopes()->create([
            'tenant_id' => $tenant->id,
            'room_id' => $room->id,
            'name' => 'Paid Resident',
        ]);

        $contract = Contract::withoutGlobalScopes()->create([
            'tenant_id' => $tenant->id,
            'customer_id' => $customer->id,
            'room_id' => $room->id,
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'deposit' => 5000,
            'monthly_rent' => 5300,
            'status' => 'active',
        ]);

        $invoice = Invoice::withoutGlobalScopes()->create([
            'tenant_id' => $tenant->id,
            'contract_id' => $contract->id,
            'customer_id' => $customer->id,
            'room_id' => $room->id,
            'total_amount' => 5300,
            'water_fee' => 0,
            'electricity_fee' => 0,
            'service_fee' => 0,
            'status' => 'paid',
            'due_date' => now()->addDays(7)->toDateString(),
        ]);

        $slip = UploadedFile::fake()->image('slip2.jpg', 400, 300);

        $response = $this->post(
            route('resident.invoice.pay-slip', $invoice->public_id),
            [
                'amount' => 5300,
                'payment_date' => now()->toDateString(),
                'slip' => $slip,
            ]
        );

        $response->assertRedirect();
        $this->assertDatabaseMissing('payments', ['invoice_id' => $invoice->id]);
    }

    public function test_approval_email_sent_to_resident_with_email(): void
    {
        Mail::fake();

        $tenant = Tenant::create(['name' => 'Email Approve Dorm', 'domain' => 'email-approve.local', 'plan' => 'trial', 'status' => 'active']);
        $user = User::factory()->create(['tenant_id' => $tenant->id, 'role' => 'owner', 'email_verified_at' => now()]);
        $room = Room::create(['tenant_id' => $tenant->id, 'room_number' => 'EA-101', 'floor' => 1, 'room_type' => 'Standard', 'price' => 5400, 'status' => 'occupied']);
        $customer = Customer::create(['tenant_id' => $tenant->id, 'room_id' => $room->id, 'name' => 'Email Resident', 'email' => 'resident@test.local']);
        $contract = Contract::create(['tenant_id' => $tenant->id, 'customer_id' => $customer->id, 'room_id' => $room->id, 'start_date' => '2026-01-01', 'end_date' => '2026-12-31', 'deposit' => 5000, 'monthly_rent' => 5400, 'status' => 'active']);
        $invoice = Invoice::create(['tenant_id' => $tenant->id, 'contract_id' => $contract->id, 'customer_id' => $customer->id, 'room_id' => $room->id, 'total_amount' => 5400, 'water_fee' => 0, 'electricity_fee' => 0, 'service_fee' => 0, 'status' => 'sent', 'due_date' => now()->addDays(7)->toDateString()]);
        $payment = Payment::create(['tenant_id' => $tenant->id, 'invoice_id' => $invoice->id, 'amount' => 5400, 'payment_date' => now()->toDateString(), 'method' => 'slip', 'status' => 'pending']);

        $this->actingAs($user)
            ->withSession(['tenant_id' => $tenant->id])
            ->patch('/app/payments/'.$payment->id.'/approve');

        Mail::assertSent(PaymentNotification::class, fn ($m) => $m->hasTo($customer->email) && $m->event === 'payment_approved');
        $this->assertDatabaseHas('notification_logs', ['tenant_id' => $tenant->id, 'channel' => 'email', 'event' => 'payment_approved', 'status' => 'sent']);
    }

    public function test_rejection_email_sent_to_resident_with_email(): void
    {
        Mail::fake();

        $tenant = Tenant::create(['name' => 'Email Reject Dorm', 'domain' => 'email-reject.local', 'plan' => 'trial', 'status' => 'active']);
        $user = User::factory()->create(['tenant_id' => $tenant->id, 'role' => 'owner', 'email_verified_at' => now()]);
        $room = Room::create(['tenant_id' => $tenant->id, 'room_number' => 'ER-101', 'floor' => 1, 'room_type' => 'Standard', 'price' => 5500, 'status' => 'occupied']);
        $customer = Customer::create(['tenant_id' => $tenant->id, 'room_id' => $room->id, 'name' => 'Reject Email Resident', 'email' => 'reject@test.local']);
        $contract = Contract::create(['tenant_id' => $tenant->id, 'customer_id' => $customer->id, 'room_id' => $room->id, 'start_date' => '2026-01-01', 'end_date' => '2026-12-31', 'deposit' => 5000, 'monthly_rent' => 5500, 'status' => 'active']);
        $invoice = Invoice::create(['tenant_id' => $tenant->id, 'contract_id' => $contract->id, 'customer_id' => $customer->id, 'room_id' => $room->id, 'total_amount' => 5500, 'water_fee' => 0, 'electricity_fee' => 0, 'service_fee' => 0, 'status' => 'sent', 'due_date' => now()->addDays(7)->toDateString()]);
        $payment = Payment::create(['tenant_id' => $tenant->id, 'invoice_id' => $invoice->id, 'amount' => 5500, 'payment_date' => now()->toDateString(), 'method' => 'slip', 'status' => 'pending']);

        $this->actingAs($user)
            ->withSession(['tenant_id' => $tenant->id])
            ->patch('/app/payments/'.$payment->id.'/reject');

        Mail::assertSent(PaymentNotification::class, fn ($m) => $m->hasTo($customer->email) && $m->event === 'payment_rejected');
        $this->assertDatabaseHas('notification_logs', ['tenant_id' => $tenant->id, 'channel' => 'email', 'event' => 'payment_rejected', 'status' => 'sent']);
    }

    public function test_no_email_sent_when_resident_has_no_email_but_log_created(): void
    {
        Mail::fake();

        $tenant = Tenant::create(['name' => 'No Email Dorm', 'domain' => 'no-email.local', 'plan' => 'trial', 'status' => 'active']);
        $user = User::factory()->create(['tenant_id' => $tenant->id, 'role' => 'owner', 'email_verified_at' => now()]);
        $room = Room::create(['tenant_id' => $tenant->id, 'room_number' => 'NE-101', 'floor' => 1, 'room_type' => 'Standard', 'price' => 5600, 'status' => 'occupied']);
        $customer = Customer::create(['tenant_id' => $tenant->id, 'room_id' => $room->id, 'name' => 'No Email Resident']);
        $contract = Contract::create(['tenant_id' => $tenant->id, 'customer_id' => $customer->id, 'room_id' => $room->id, 'start_date' => '2026-01-01', 'end_date' => '2026-12-31', 'deposit' => 5000, 'monthly_rent' => 5600, 'status' => 'active']);
        $invoice = Invoice::create(['tenant_id' => $tenant->id, 'contract_id' => $contract->id, 'customer_id' => $customer->id, 'room_id' => $room->id, 'total_amount' => 5600, 'water_fee' => 0, 'electricity_fee' => 0, 'service_fee' => 0, 'status' => 'sent', 'due_date' => now()->addDays(7)->toDateString()]);
        $payment = Payment::create(['tenant_id' => $tenant->id, 'invoice_id' => $invoice->id, 'amount' => 5600, 'payment_date' => now()->toDateString(), 'method' => 'manual', 'status' => 'pending']);

        $this->actingAs($user)
            ->withSession(['tenant_id' => $tenant->id])
            ->patch('/app/payments/'.$payment->id.'/approve');

        Mail::assertNothingSent();
        $this->assertDatabaseHas('notification_logs', ['tenant_id' => $tenant->id, 'channel' => 'email', 'event' => 'payment_approved', 'status' => 'skipped']);
    }

    public function test_receipt_no_is_generated_on_approve_in_rec_format(): void
    {
        Mail::fake();

        $tenant = Tenant::create(['name' => 'Receipt No Dorm', 'domain' => 'receipt-no.local', 'plan' => 'trial', 'status' => 'active']);
        $user = User::factory()->create(['tenant_id' => $tenant->id, 'role' => 'owner', 'email_verified_at' => now()]);
        $room = Room::create(['tenant_id' => $tenant->id, 'room_number' => 'RN-101', 'floor' => 1, 'room_type' => 'Standard', 'price' => 5700, 'status' => 'occupied']);
        $customer = Customer::create(['tenant_id' => $tenant->id, 'room_id' => $room->id, 'name' => 'RN Resident']);
        $contract = Contract::create(['tenant_id' => $tenant->id, 'customer_id' => $customer->id, 'room_id' => $room->id, 'start_date' => '2026-01-01', 'end_date' => '2026-12-31', 'deposit' => 5000, 'monthly_rent' => 5700, 'status' => 'active']);
        $invoice = Invoice::create(['tenant_id' => $tenant->id, 'contract_id' => $contract->id, 'customer_id' => $customer->id, 'room_id' => $room->id, 'total_amount' => 5700, 'water_fee' => 0, 'electricity_fee' => 0, 'service_fee' => 0, 'status' => 'sent', 'due_date' => now()->addDays(7)->toDateString()]);
        $payment = Payment::create(['tenant_id' => $tenant->id, 'invoice_id' => $invoice->id, 'amount' => 5700, 'payment_date' => now()->toDateString(), 'method' => 'manual', 'status' => 'pending']);

        $this->actingAs($user)
            ->withSession(['tenant_id' => $tenant->id])
            ->patch('/app/payments/'.$payment->id.'/approve');

        $payment->refresh();
        $this->assertMatchesRegularExpression('/^REC-\d{4}-\d{4}$/', $payment->receipt_no);
        $this->assertStringStartsWith('REC-'.now()->year.'-', $payment->receipt_no);
    }

    public function test_receipt_no_increments_per_tenant(): void
    {
        Mail::fake();

        $tenant = Tenant::create(['name' => 'Seq Dorm', 'domain' => 'seq.local', 'plan' => 'trial', 'status' => 'active']);
        $user = User::factory()->create(['tenant_id' => $tenant->id, 'role' => 'owner', 'email_verified_at' => now()]);
        $room = Room::create(['tenant_id' => $tenant->id, 'room_number' => 'SQ-101', 'floor' => 1, 'room_type' => 'Standard', 'price' => 5800, 'status' => 'occupied']);
        $customer = Customer::create(['tenant_id' => $tenant->id, 'room_id' => $room->id, 'name' => 'Seq Resident']);
        $contract = Contract::create(['tenant_id' => $tenant->id, 'customer_id' => $customer->id, 'room_id' => $room->id, 'start_date' => '2026-01-01', 'end_date' => '2026-12-31', 'deposit' => 5000, 'monthly_rent' => 5800, 'status' => 'active']);
        $invoice1 = Invoice::create(['tenant_id' => $tenant->id, 'contract_id' => $contract->id, 'customer_id' => $customer->id, 'room_id' => $room->id, 'total_amount' => 5800, 'water_fee' => 0, 'electricity_fee' => 0, 'service_fee' => 0, 'status' => 'sent', 'due_date' => now()->addDays(7)->toDateString()]);
        $invoice2 = Invoice::create(['tenant_id' => $tenant->id, 'contract_id' => $contract->id, 'customer_id' => $customer->id, 'room_id' => $room->id, 'total_amount' => 5800, 'water_fee' => 0, 'electricity_fee' => 0, 'service_fee' => 0, 'status' => 'sent', 'due_date' => now()->addDays(14)->toDateString()]);
        $p1 = Payment::create(['tenant_id' => $tenant->id, 'invoice_id' => $invoice1->id, 'amount' => 5800, 'payment_date' => now()->toDateString(), 'method' => 'manual', 'status' => 'pending']);
        $p2 = Payment::create(['tenant_id' => $tenant->id, 'invoice_id' => $invoice2->id, 'amount' => 5800, 'payment_date' => now()->toDateString(), 'method' => 'manual', 'status' => 'pending']);

        $this->actingAs($user)->withSession(['tenant_id' => $tenant->id])->patch('/app/payments/'.$p1->id.'/approve');
        $this->actingAs($user)->withSession(['tenant_id' => $tenant->id])->patch('/app/payments/'.$p2->id.'/approve');

        $p1->refresh(); $p2->refresh();
        $this->assertNotEquals($p1->receipt_no, $p2->receipt_no);
        $seq1 = (int) substr($p1->receipt_no, -4);
        $seq2 = (int) substr($p2->receipt_no, -4);
        $this->assertEquals($seq1 + 1, $seq2);
    }

    public function test_resident_can_download_receipt_for_approved_payment(): void
    {
        $tenant = Tenant::create(['name' => 'Resident Receipt Dorm', 'domain' => 'resident-receipt.local', 'plan' => 'trial', 'status' => 'active']);
        $room = Room::withoutGlobalScopes()->create(['tenant_id' => $tenant->id, 'room_number' => 'RR-101', 'floor' => 1, 'room_type' => 'Standard', 'price' => 5900, 'status' => 'occupied']);
        $customer = Customer::withoutGlobalScopes()->create(['tenant_id' => $tenant->id, 'room_id' => $room->id, 'name' => 'Download Resident']);
        $contract = Contract::withoutGlobalScopes()->create(['tenant_id' => $tenant->id, 'customer_id' => $customer->id, 'room_id' => $room->id, 'start_date' => '2026-01-01', 'end_date' => '2026-12-31', 'deposit' => 5000, 'monthly_rent' => 5900, 'status' => 'active']);
        $invoice = Invoice::withoutGlobalScopes()->create(['tenant_id' => $tenant->id, 'contract_id' => $contract->id, 'customer_id' => $customer->id, 'room_id' => $room->id, 'total_amount' => 5900, 'water_fee' => 0, 'electricity_fee' => 0, 'service_fee' => 0, 'status' => 'paid', 'due_date' => now()->addDays(7)->toDateString()]);
        $payment = Payment::withoutGlobalScopes()->create(['tenant_id' => $tenant->id, 'invoice_id' => $invoice->id, 'amount' => 5900, 'payment_date' => now()->toDateString(), 'method' => 'manual', 'status' => 'approved', 'receipt_no' => 'REC-2026-0001']);

        $response = $this->get(\Illuminate\Support\Facades\URL::signedRoute('resident.invoice.receipt', [$invoice->public_id, $payment->id]));

        $response->assertOk();
        $response->assertHeader('content-type', 'application/pdf');
        $response->assertHeader('content-disposition');
    }

    public function test_resident_cannot_download_receipt_for_pending_payment(): void
    {
        $tenant = Tenant::create(['name' => 'Pending Receipt Dorm', 'domain' => 'pending-receipt.local', 'plan' => 'trial', 'status' => 'active']);
        $room = Room::withoutGlobalScopes()->create(['tenant_id' => $tenant->id, 'room_number' => 'PR-101', 'floor' => 1, 'room_type' => 'Standard', 'price' => 6000, 'status' => 'occupied']);
        $customer = Customer::withoutGlobalScopes()->create(['tenant_id' => $tenant->id, 'room_id' => $room->id, 'name' => 'Pending Resident']);
        $contract = Contract::withoutGlobalScopes()->create(['tenant_id' => $tenant->id, 'customer_id' => $customer->id, 'room_id' => $room->id, 'start_date' => '2026-01-01', 'end_date' => '2026-12-31', 'deposit' => 5000, 'monthly_rent' => 6000, 'status' => 'active']);
        $invoice = Invoice::withoutGlobalScopes()->create(['tenant_id' => $tenant->id, 'contract_id' => $contract->id, 'customer_id' => $customer->id, 'room_id' => $room->id, 'total_amount' => 6000, 'water_fee' => 0, 'electricity_fee' => 0, 'service_fee' => 0, 'status' => 'sent', 'due_date' => now()->addDays(7)->toDateString()]);
        $payment = Payment::withoutGlobalScopes()->create(['tenant_id' => $tenant->id, 'invoice_id' => $invoice->id, 'amount' => 6000, 'payment_date' => now()->toDateString(), 'method' => 'slip', 'status' => 'pending']);

        $response = $this->get(\Illuminate\Support\Facades\URL::signedRoute('resident.invoice.receipt', [$invoice->public_id, $payment->id]));

        $response->assertNotFound();
    }

    public function test_suspended_tenant_owner_cannot_access_portal(): void
    {
        $tenant = Tenant::create(['name' => 'Suspended Dorm', 'domain' => 'suspended.local', 'plan' => 'trial', 'status' => 'suspended']);
        $user = User::factory()->create(['tenant_id' => $tenant->id, 'role' => 'owner', 'email_verified_at' => now()]);

        $response = $this->actingAs($user)
            ->withSession(['tenant_id' => $tenant->id])
            ->get('/app/dashboard');

        $response->assertForbidden();
    }

    public function test_suspended_tenant_resident_cannot_view_invoice(): void
    {
        $tenant = Tenant::create(['name' => 'Suspended Resident Dorm', 'domain' => 'suspended-res.local', 'plan' => 'trial', 'status' => 'suspended']);
        $room = Room::withoutGlobalScopes()->create(['tenant_id' => $tenant->id, 'room_number' => 'SR-101', 'floor' => 1, 'room_type' => 'Standard', 'price' => 5000, 'status' => 'occupied']);
        $customer = Customer::withoutGlobalScopes()->create(['tenant_id' => $tenant->id, 'room_id' => $room->id, 'name' => 'Suspended Resident']);
        $contract = Contract::withoutGlobalScopes()->create(['tenant_id' => $tenant->id, 'customer_id' => $customer->id, 'room_id' => $room->id, 'start_date' => '2026-01-01', 'end_date' => '2026-12-31', 'deposit' => 5000, 'monthly_rent' => 5000, 'status' => 'active']);
        $invoice = Invoice::withoutGlobalScopes()->create(['tenant_id' => $tenant->id, 'contract_id' => $contract->id, 'customer_id' => $customer->id, 'room_id' => $room->id, 'total_amount' => 5000, 'water_fee' => 0, 'electricity_fee' => 0, 'service_fee' => 0, 'status' => 'sent', 'due_date' => now()->addDays(7)->toDateString()]);

        $response = $this->get(route('resident.invoice', $invoice->public_id));

        $response->assertForbidden();
    }

    public function test_admin_can_suspend_and_unsuspend_tenant(): void
    {
        $admin = User::factory()->create(['tenant_id' => null, 'role' => 'super_admin', 'email_verified_at' => now()]);
        $tenant = Tenant::create(['name' => 'Toggle Dorm', 'domain' => 'toggle.local', 'plan' => 'trial', 'status' => 'active']);

        // Suspend
        $this->actingAs($admin)
            ->patch(route('admin.tenants.suspend', $tenant))
            ->assertRedirect();

        $this->assertDatabaseHas('tenants', ['id' => $tenant->id, 'status' => 'suspended']);

        // Unsuspend
        $this->actingAs($admin)
            ->patch(route('admin.tenants.unsuspend', $tenant))
            ->assertRedirect();

        $this->assertDatabaseHas('tenants', ['id' => $tenant->id, 'status' => 'active']);
    }

    public function test_non_admin_cannot_suspend_tenant(): void
    {
        $tenant = Tenant::create(['name' => 'Protect Dorm', 'domain' => 'protect.local', 'plan' => 'trial', 'status' => 'active']);
        $owner = User::factory()->create(['tenant_id' => $tenant->id, 'role' => 'owner', 'email_verified_at' => now()]);

        $this->actingAs($owner)
            ->withSession(['tenant_id' => $tenant->id])
            ->patch(route('admin.tenants.suspend', $tenant))
            ->assertForbidden();
    }

    public function test_resident_invoice_requires_signed_url(): void
    {
        $tenant = Tenant::create(['name' => 'Signed URL Dorm', 'domain' => 'signed.local', 'plan' => 'trial', 'status' => 'active']);
        $room = Room::withoutGlobalScopes()->create(['tenant_id' => $tenant->id, 'room_number' => 'SU-101', 'floor' => 1, 'room_type' => 'Standard', 'price' => 5000, 'status' => 'occupied']);
        $customer = Customer::withoutGlobalScopes()->create(['tenant_id' => $tenant->id, 'room_id' => $room->id, 'name' => 'Signed Resident']);
        $contract = Contract::withoutGlobalScopes()->create(['tenant_id' => $tenant->id, 'customer_id' => $customer->id, 'room_id' => $room->id, 'start_date' => '2026-01-01', 'end_date' => '2026-12-31', 'deposit' => 5000, 'monthly_rent' => 5000, 'status' => 'active']);
        $invoice = Invoice::withoutGlobalScopes()->create(['tenant_id' => $tenant->id, 'contract_id' => $contract->id, 'customer_id' => $customer->id, 'room_id' => $room->id, 'total_amount' => 5000, 'water_fee' => 0, 'electricity_fee' => 0, 'service_fee' => 0, 'status' => 'sent', 'due_date' => now()->addDays(7)->toDateString()]);

        // Unsigned URL should be forbidden
        $unsignedUrl = route('resident.invoice', $invoice->public_id);
        $this->get($unsignedUrl)->assertStatus(403);
    }

    public function test_resident_invoice_accessible_via_signed_url(): void
    {
        $tenant = Tenant::create(['name' => 'Signed Access Dorm', 'domain' => 'signed-access.local', 'plan' => 'trial', 'status' => 'active']);
        $room = Room::withoutGlobalScopes()->create(['tenant_id' => $tenant->id, 'room_number' => 'SA-101', 'floor' => 1, 'room_type' => 'Standard', 'price' => 5100, 'status' => 'occupied']);
        $customer = Customer::withoutGlobalScopes()->create(['tenant_id' => $tenant->id, 'room_id' => $room->id, 'name' => 'Access Resident']);
        $contract = Contract::withoutGlobalScopes()->create(['tenant_id' => $tenant->id, 'customer_id' => $customer->id, 'room_id' => $room->id, 'start_date' => '2026-01-01', 'end_date' => '2026-12-31', 'deposit' => 5000, 'monthly_rent' => 5100, 'status' => 'active']);
        $invoice = Invoice::withoutGlobalScopes()->create(['tenant_id' => $tenant->id, 'contract_id' => $contract->id, 'customer_id' => $customer->id, 'room_id' => $room->id, 'total_amount' => 5100, 'water_fee' => 0, 'electricity_fee' => 0, 'service_fee' => 0, 'status' => 'sent', 'due_date' => now()->addDays(7)->toDateString()]);

        // Signed URL should work
        $signedUrl = $invoice->signedResidentUrl();
        $this->get($signedUrl)->assertOk();
    }

    public function test_signed_url_is_included_in_line_notification_message(): void
    {
        $tenant = Tenant::create(['name' => 'Line Signed Dorm', 'domain' => 'line-signed.local', 'plan' => 'trial', 'status' => 'active']);
        $user = User::factory()->create(['tenant_id' => $tenant->id, 'role' => 'owner', 'email_verified_at' => now()]);
        $room = Room::withoutGlobalScopes()->create(['tenant_id' => $tenant->id, 'room_number' => 'LS-101', 'floor' => 1, 'room_type' => 'Standard', 'price' => 5200, 'status' => 'occupied']);
        $customer = Customer::withoutGlobalScopes()->create(['tenant_id' => $tenant->id, 'room_id' => $room->id, 'name' => 'Line Resident']);
        $contract = Contract::withoutGlobalScopes()->create(['tenant_id' => $tenant->id, 'customer_id' => $customer->id, 'room_id' => $room->id, 'start_date' => '2026-01-01', 'end_date' => '2026-12-31', 'deposit' => 5000, 'monthly_rent' => 5200, 'status' => 'active']);

        $this->actingAs($user)
            ->withSession(['tenant_id' => $tenant->id])
            ->post('/app/invoices', [
                'contract_id' => $contract->id,
                'water_fee' => 0,
                'electricity_fee' => 0,
                'service_fee' => 0,
                'status' => 'sent',
                'due_date' => now()->addDays(7)->toDateString(),
            ])->assertRedirect();

        // Notification log message should contain 'signature=' (signed URL fragment)
        $this->assertDatabaseHas('notification_logs', [
            'tenant_id' => $tenant->id,
            'channel' => 'line',
            'event' => 'invoice_created',
        ]);

        $log = \App\Models\NotificationLog::where('tenant_id', $tenant->id)->where('event', 'invoice_created')->latest()->first();
        $this->assertStringContainsString('signature=', $log->message);
    }
}
