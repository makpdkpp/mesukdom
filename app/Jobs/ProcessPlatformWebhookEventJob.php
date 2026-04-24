<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\NotificationLog;
use App\Services\Line\PlatformLineWebhookHandler;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

final class ProcessPlatformWebhookEventJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    /**
     * @param array<string,mixed> $event
     */
    public function __construct(private readonly array $event)
    {
        $this->onConnection((string) config('queue.line.connection', config('queue.default', 'database')));
        $this->onQueue((string) config('queue.line.queue', 'line'));
    }

    public function handle(PlatformLineWebhookHandler $handler): void
    {
        $result = $handler->handle($this->event);

        NotificationLog::query()->create([
            'tenant_id' => null,
            'channel' => 'line:platform',
            'event' => (string) data_get($this->event, 'type', 'unknown'),
            'target' => (string) data_get($this->event, 'source.userId', ''),
            'message' => $result['message'],
            'status' => $result['status'],
            'payload' => array_merge(['event' => $this->event], $result['payload']),
        ]);
    }
}