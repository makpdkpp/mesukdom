<?php

namespace Database\Seeders;

use App\Models\Contract;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\NotificationLog;
use App\Models\Payment;
use App\Models\Room;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $tenant = Tenant::create([
            'name' => 'MesukDom Demo Residence',
            'domain' => 'demo.local',
            'plan' => 'pro',
            'status' => 'active',
            'trial_ends_at' => now()->addDays(14),
        ]);

        User::factory()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Dorm Owner',
            'email' => 'owner@example.com',
            'role' => 'owner',
        ]);

        $roomA = Room::create([
            'tenant_id' => $tenant->id,
            'room_number' => 'A-101',
            'floor' => 1,
            'room_type' => 'Standard',
            'price' => 3500,
            'status' => 'occupied',
        ]);

        $roomB = Room::create([
            'tenant_id' => $tenant->id,
            'room_number' => 'A-102',
            'floor' => 1,
            'room_type' => 'Deluxe',
            'price' => 4200,
            'status' => 'vacant',
        ]);

        $customer = Customer::create([
            'tenant_id' => $tenant->id,
            'room_id' => $roomA->id,
            'name' => 'Somchai Jaidee',
            'phone' => '0812345678',
            'email' => 'resident@example.com',
            'line_id' => '@somchai-room',
            'id_card' => '1234567890123',
        ]);

        $contract = Contract::create([
            'tenant_id' => $tenant->id,
            'customer_id' => $customer->id,
            'room_id' => $roomA->id,
            'start_date' => now()->startOfMonth(),
            'end_date' => now()->addYear()->endOfMonth(),
            'deposit' => 5000,
            'monthly_rent' => 3500,
            'status' => 'active',
        ]);

        $invoice = Invoice::create([
            'tenant_id' => $tenant->id,
            'contract_id' => $contract->id,
            'customer_id' => $customer->id,
            'room_id' => $roomA->id,
            'public_id' => (string) Str::ulid(),
            'invoice_no' => 'INV-DEMO-0001',
            'water_fee' => 150,
            'electricity_fee' => 450,
            'service_fee' => 100,
            'total_amount' => 4200,
            'status' => 'sent',
            'issued_at' => now(),
            'due_date' => now()->addDays(7),
        ]);

        Payment::create([
            'tenant_id' => $tenant->id,
            'invoice_id' => $invoice->id,
            'amount' => 4200,
            'payment_date' => now(),
            'method' => 'online',
            'status' => 'approved',
            'notes' => 'Demo payment completed',
        ]);

        NotificationLog::create([
            'tenant_id' => $tenant->id,
            'channel' => 'line',
            'event' => 'welcome',
            'target' => $customer->name,
            'message' => 'LINE OA ready for resident billing reminders',
            'status' => 'queued',
            'payload' => ['room' => $roomA->room_number, 'vacant_room' => $roomB->room_number],
        ]);
    }
}
