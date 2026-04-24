<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\NotificationLog;
use App\Services\Line\PlatformLineService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

final class SendPlatformLineMessageJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    /**
     * @param array<string,mixed> $payload
     */
    public function __construct(
        private readonly int $userId,
        private readonly ?int $tenantId,
        private readonly ?string $lineUserId,
        private readonly string $event,
        private readonly string $message,
        private readonly array $payload = [],
    ) {
        $this->onConnection((string) config('queue.line.connection', config('queue.default', 'database')));
        $this->onQueue((string) config('queue.line.queue', 'line'));
    }

    public function handle(PlatformLineService $service): void
    {
        $result = $service->pushText($this->lineUserId, $this->message);

        NotificationLog::query()->create([
            'tenant_id' => $this->tenantId,
            'channel' => 'line:platform',
            'event' => $this->event,
            'target' => 'user:'.$this->userId,
            'message' => $this->message,
            'status' => $result['status'] ?? 'failed',
            'payload' => array_merge($this->payload, ['response' => $result]),
        ]);
    }

    public function failed(Throwable $throwable): void
    {
        Log::warning('SendPlatformLineMessageJob failed', [
            'user_id' => $this->userId,
            'tenant_id' => $this->tenantId,
            'event' => $this->event,
            'status' => 'failed',
            'error' => $throwable->getMessage(),
        ]);
    }
}