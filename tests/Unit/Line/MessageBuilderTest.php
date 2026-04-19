<?php

namespace Tests\Unit\Line;

use App\Models\Contract;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Room;
use App\Models\Tenant;
use App\Services\Line\MessageBuilder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MessageBuilderTest extends TestCase
{
    use RefreshDatabase;

    public function test_latest_invoice_uses_real_newline_before_url(): void
    {
        $tenant = Tenant::create([
            'name' => 'Line Msg Dorm',
            'domain' => 'line-msg.local',
            'plan' => 'trial',
            'status' => 'active',
        ]);

        $room = Room::withoutGlobalScopes()->create([
            'tenant_id' => $tenant->id,
            'room_number' => 'A-101',
            'floor' => 1,
            'room_type' => 'Standard',
            'price' => 3500,
            'status' => 'occupied',
        ]);

        $customer = Customer::withoutGlobalScopes()->create([
            'tenant_id' => $tenant->id,
            'room_id' => $room->id,
            'name' => 'Resident A',
        ]);

        $contract = Contract::withoutGlobalScopes()->create([
            'tenant_id' => $tenant->id,
            'customer_id' => $customer->id,
            'room_id' => $room->id,
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'deposit' => 5000,
            'monthly_rent' => 3500,
            'status' => 'active',
        ]);

        $invoice = Invoice::withoutGlobalScopes()->create([
            'tenant_id' => $tenant->id,
            'contract_id' => $contract->id,
            'customer_id' => $customer->id,
            'room_id' => $room->id,
            'total_amount' => 3502,
            'water_fee' => 0,
            'electricity_fee' => 0,
            'service_fee' => 2,
            'status' => 'overdue',
            'due_date' => '2026-04-17',
        ]);

        $message = app(MessageBuilder::class)->latestInvoice($invoice);

        $this->assertStringNotContainsString('\\nhttp', $message);
        $this->assertStringContainsString("\nhttp", $message);
    }
}
