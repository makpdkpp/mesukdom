<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\LineMessage;
use App\Models\NotificationLog;
use App\Models\Tenant;
use App\Services\Line\LineService;
use App\Support\TenantContext;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

final class SendLineMessageJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    /**
     * @param array<string, mixed> $payload
     */
    public function __construct(
        private readonly int $tenantId,
        private readonly string $event,
        private readonly ?string $lineUserId,
        private readonly string $message,
        private readonly ?string $target = null,
        private readonly ?int $customerId = null,
        private readonly array $payload = [],
    ) {
        $this->onConnection((string) config('queue.line.connection', config('queue.default', 'database')));
        $this->onQueue((string) config('queue.line.queue', 'line'));
    }

    public function handle(LineService $lineService, TenantContext $tenantContext): void
    {
        $tenant = Tenant::query()->find($this->tenantId);

        if (! $tenant) {
            return;
        }

        $tenantContext->set($tenant);

        try {
            $result = $lineService->pushText($tenant, $this->lineUserId, $this->message);

            LineMessage::query()->create([
                'tenant_id' => $tenant->id,
                'customer_id' => $this->customerId,
                'direction' => 'outbound',
                'message_type' => 'text',
                'payload' => [
                    'message' => $this->message,
                    'response' => $result,
                    'event' => $this->event,
                ],
                'sent_at' => now(),
            ]);

            NotificationLog::query()->create([
                'tenant_id' => $tenant->id,
                'channel' => 'line',
                'event' => $this->event,
                'target' => $this->target,
                'message' => $this->message,
                'status' => $result['status'],
                'payload' => array_merge($this->payload, ['response' => $result]),
            ]);
        } finally {
            $tenantContext->set(null);
        }
    }
}
