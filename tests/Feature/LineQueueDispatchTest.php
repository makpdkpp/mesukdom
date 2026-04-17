<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Jobs\ProcessWebhookEventJob;
use App\Jobs\SendLineMessageJob;
use App\Models\Contract;
use App\Models\Customer;
use App\Models\Room;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class LineQueueDispatchTest extends TestCase
{
    use RefreshDatabase;

    public function test_line_webhook_dispatches_processing_job_to_line_queue(): void
    {
        Queue::fake();

        $tenant = Tenant::query()->create([
            'name' => 'Queued Webhook Dorm',
            'line_channel_secret' => 'queued-webhook-secret',
        ]);

        $payload = [
            'events' => [[
                'type' => 'follow',
                'replyToken' => 'reply-token-1',
                'source' => ['userId' => 'U-demo-user'],
            ]],
        ];

        $json = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $signature = base64_encode(hash_hmac('sha256', $json, $tenant->line_channel_secret, true));

        $this->call(
            'POST',
            '/api/line/webhook',
            [],
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_X_LINE_SIGNATURE' => $signature,
            ],
            $json
        )->assertOk();

        Queue::assertPushed(ProcessWebhookEventJob::class, function (ProcessWebhookEventJob $job): bool {
            return $job->queue === config('queue.line.queue', 'line')
                && $job->connection === config('queue.line.connection');
        });
    }

    public function test_invoice_created_dispatches_line_send_job_to_line_queue(): void
    {
        Queue::fake();

        $tenant = Tenant::create([
            'name' => 'Queued Invoice Dorm',
            'domain' => 'queued-invoice.local',
            'plan' => 'trial',
            'status' => 'active',
        ]);

        $user = User::factory()->create([
            'tenant_id' => $tenant->id,
            'role' => 'owner',
            'email_verified_at' => now(),
        ]);

        $room = Room::withoutGlobalScopes()->create([
            'tenant_id' => $tenant->id,
            'building' => 'Main',
            'room_number' => 'QI-101',
            'floor' => 1,
            'room_type' => 'Standard',
            'price' => 4200,
            'status' => 'occupied',
        ]);

        $customer = Customer::withoutGlobalScopes()->create([
            'tenant_id' => $tenant->id,
            'room_id' => $room->id,
            'name' => 'Queued Resident',
            'line_user_id' => 'U-queued-resident',
        ]);

        $contract = Contract::withoutGlobalScopes()->create([
            'tenant_id' => $tenant->id,
            'customer_id' => $customer->id,
            'room_id' => $room->id,
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'deposit' => 5000,
            'monthly_rent' => 4200,
            'status' => 'active',
        ]);

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

        Queue::assertPushed(SendLineMessageJob::class, function (SendLineMessageJob $job): bool {
            return $job->queue === config('queue.line.queue', 'line')
                && $job->connection === config('queue.line.connection');
        });
    }
}
