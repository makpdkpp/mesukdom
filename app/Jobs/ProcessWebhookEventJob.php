<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\LineWebhookLog;
use App\Models\NotificationLog;
use App\Models\Tenant;
use App\Services\Line\LineWebhookHandler;
use App\Support\TenantContext;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

final class ProcessWebhookEventJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    /**
     * @param array<string, mixed> $event
     */
    public function __construct(
        private readonly int $tenantId,
        private readonly array $event,
    ) {
        $this->onConnection((string) config('queue.line.connection', config('queue.default', 'database')));
        $this->onQueue((string) config('queue.line.queue', 'line'));
    }

    public function handle(LineWebhookHandler $webhookHandler, TenantContext $tenantContext): void
    {
        $tenant = Tenant::query()->find($this->tenantId);

        if (! $tenant) {
            return;
        }

        $tenantContext->set($tenant);

        try {
            $type = (string) data_get($this->event, 'type', 'unknown');
            $userId = (string) data_get($this->event, 'source.userId', 'guest');
            $result = $webhookHandler->handle($tenant, $this->event);

            LineWebhookLog::query()->create([
                'tenant_id' => $tenant->id,
                'event_type' => $type,
                'line_user_id' => $userId,
                'payload' => $this->event,
            ]);

            NotificationLog::query()->create([
                'tenant_id' => $tenant->id,
                'channel' => 'line',
                'event' => $type,
                'target' => $userId,
                'message' => $result['message'],
                'status' => $result['status'],
                'payload' => array_merge(['event' => $this->event], $result['payload']),
            ]);
        } finally {
            $tenantContext->set(null);
        }
    }
}
