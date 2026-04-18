<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Jobs\SendLineMessageJob;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Tests\TestCase;

final class SendLineMessageJobTest extends TestCase
{
    use RefreshDatabase;

    public function test_send_line_message_job_records_successful_delivery(): void
    {
        Http::fake(['https://api.line.me/*' => Http::response(['ok' => true], 200)]);

        $tenant = Tenant::query()->create([
            'name' => 'Line Success Dorm',
            'line_channel_access_token' => 'line-success-token',
        ]);

        $job = new SendLineMessageJob(
            tenantId: $tenant->id,
            event: 'invoice_reminder',
            lineUserId: 'U-success',
            message: 'Payment reminder',
            target: 'U-success',
            customerId: null,
            payload: ['invoice_id' => 1001],
        );

        app()->call([$job, 'handle']);

        $this->assertDatabaseHas('line_messages', [
            'tenant_id' => $tenant->id,
            'direction' => 'outbound',
            'message_type' => 'text',
        ]);

        $this->assertDatabaseHas('notification_logs', [
            'tenant_id' => $tenant->id,
            'channel' => 'line',
            'event' => 'invoice_reminder',
            'status' => 'sent',
        ]);
    }

    public function test_send_line_message_job_records_skipped_delivery_without_throwing(): void
    {
        $tenant = Tenant::query()->create([
            'name' => 'Line Skipped Dorm',
        ]);

        $job = new SendLineMessageJob(
            tenantId: $tenant->id,
            event: 'invoice_reminder',
            lineUserId: null,
            message: 'Payment reminder',
            target: null,
        );

        app()->call([$job, 'handle']);

        $this->assertDatabaseHas('notification_logs', [
            'tenant_id' => $tenant->id,
            'channel' => 'line',
            'event' => 'invoice_reminder',
            'status' => 'skipped',
        ]);
    }

    public function test_send_line_message_job_throws_on_failed_delivery_and_records_final_failure(): void
    {
        Http::fake(['https://api.line.me/*' => Http::response(['message' => 'API unavailable'], 500)]);

        $tenant = Tenant::query()->create([
            'name' => 'Line Failure Dorm',
            'line_channel_access_token' => 'line-failure-token',
        ]);

        $job = new SendLineMessageJob(
            tenantId: $tenant->id,
            event: 'invoice_reminder',
            lineUserId: 'U-failure',
            message: 'Payment reminder',
            target: 'U-failure',
            payload: ['invoice_id' => 2002],
        );

        try {
            app()->call([$job, 'handle']);
            self::fail('Expected LINE delivery failure to throw.');
        } catch (RuntimeException $e) {
            $job->failed($e);
        }

        $this->assertDatabaseMissing('line_messages', [
            'tenant_id' => $tenant->id,
            'direction' => 'outbound',
            'message_type' => 'text',
        ]);

        $this->assertDatabaseHas('notification_logs', [
            'tenant_id' => $tenant->id,
            'channel' => 'line',
            'event' => 'invoice_reminder',
            'status' => 'failed',
        ]);
    }
}