<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Jobs\ProcessWebhookEventJob;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Room;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

final class OwnerCommandDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_request_revenue_summary_from_line_command(): void
    {
        Http::fake(['https://api.line.me/*' => Http::response(['ok' => true], 200)]);

        $tenant = Tenant::factory()->create([
            'name' => 'Owner Command Dorm',
            'line_channel_access_token' => 'tenant-token',
            'line_channel_secret' => 'tenant-secret',
        ]);

        $owner = User::factory()->create([
            'tenant_id' => $tenant->id,
            'role' => 'owner',
            'line_user_id' => 'U-owner-command',
            'line_user_id_hash' => hash('sha256', 'U-owner-command'),
            'line_linked_at' => now(),
        ]);

        $room = Room::query()->create([
            'tenant_id' => $tenant->id,
            'room_number' => 'A-101',
            'price' => 3000,
            'status' => 'occupied',
        ]);

        $customer = Customer::query()->create([
            'tenant_id' => $tenant->id,
            'room_id' => $room->id,
            'name' => 'Resident One',
            'phone' => '0811111111',
        ]);

        $invoice = Invoice::query()->create([
            'tenant_id' => $tenant->id,
            'customer_id' => $customer->id,
            'room_id' => $room->id,
            'status' => 'paid',
            'total_amount' => 3200,
            'due_date' => now()->toDateString(),
        ]);

        Payment::query()->create([
            'tenant_id' => $tenant->id,
            'invoice_id' => $invoice->id,
            'amount' => 3200,
            'payment_date' => now()->toDateString(),
            'method' => 'cash',
            'status' => 'approved',
        ]);

        $job = new ProcessWebhookEventJob($tenant->id, [
            'type' => 'message',
            'replyToken' => 'reply-owner-1',
            'source' => ['userId' => 'U-owner-command'],
            'message' => ['type' => 'text', 'text' => 'สรุปรายรับ'],
        ]);

        app()->call([$job, 'handle']);

        $this->assertDatabaseHas('notification_logs', [
            'tenant_id' => $tenant->id,
            'event' => 'message',
            'status' => 'owner_command_replied',
        ]);

        $this->assertDatabaseHas('line_messages', [
            'tenant_id' => $tenant->id,
            'direction' => 'outbound',
            'message_type' => 'flex',
        ]);

        $owner->refresh();
        self::assertSame('U-owner-command', $owner->line_user_id);
    }

    public function test_unlinked_user_cannot_access_owner_dashboard_command(): void
    {
        Http::fake(['https://api.line.me/*' => Http::response(['ok' => true], 200)]);

        $tenant = Tenant::factory()->create([
            'line_channel_access_token' => 'tenant-token',
            'line_channel_secret' => 'tenant-secret',
        ]);

        $job = new ProcessWebhookEventJob($tenant->id, [
            'type' => 'message',
            'replyToken' => 'reply-owner-2',
            'source' => ['userId' => 'U-not-linked'],
            'message' => ['type' => 'text', 'text' => 'สรุปรายรับ'],
        ]);

        app()->call([$job, 'handle']);

        $this->assertDatabaseHas('notification_logs', [
            'tenant_id' => $tenant->id,
            'event' => 'message',
            'status' => 'owner_command_denied',
        ]);
    }
}