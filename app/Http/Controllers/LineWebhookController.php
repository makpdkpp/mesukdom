<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\LineWebhookLog;
use App\Models\NotificationLog;
use App\Models\Tenant;
use App\Services\Line\LineWebhookHandler;
use App\Support\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LineWebhookController extends Controller
{
    public function __invoke(Request $request, LineWebhookHandler $webhookHandler): JsonResponse
    {
        $payload = $request->getContent();
        $signature = (string) $request->header('x-line-signature', '');
        $tenant = $this->resolveTenant($payload, $signature);

        if (! $tenant) {
            return response()->json([
                'ok' => false,
                'message' => 'Invalid LINE signature.',
            ], 401);
        }

        app(TenantContext::class)->set($tenant);

        try {
            foreach ($request->json('events', []) as $event) {
                if (! is_array($event)) {
                    continue;
                }

                /** @var array<string, mixed> $event */
                $this->handleEvent($tenant, $event, $webhookHandler);
            }
        } finally {
            app(TenantContext::class)->set(null);
        }

        return response()->json(['ok' => true]);
    }

    /**
     * @param array<string, mixed> $event
     */
    protected function handleEvent(Tenant $tenant, array $event, LineWebhookHandler $webhookHandler): void
    {
        $typeValue = data_get($event, 'type', 'unknown');
        $userIdValue = data_get($event, 'source.userId', 'guest');

        $type = is_string($typeValue) ? $typeValue : 'unknown';
        $userId = is_string($userIdValue) ? $userIdValue : 'guest';
        $result = $webhookHandler->handle($tenant, $event);

        LineWebhookLog::query()->create([
            'tenant_id' => $tenant->id,
            'event_type' => $type,
            'line_user_id' => $userId,
            'payload' => $event,
        ]);

        NotificationLog::query()->create([
            'tenant_id' => $tenant->id,
            'channel' => 'line',
            'event' => $type,
            'target' => $userId,
            'message' => $result['message'],
            'status' => $result['status'],
            'payload' => array_merge(['event' => $event], $result['payload']),
        ]);
    }

    protected function resolveTenant(string $payload, string $signature): ?Tenant
    {
        if ($signature === '') {
            return null;
        }

        foreach (Tenant::query()->whereNotNull('line_channel_secret')->get() as $tenant) {
            $expected = base64_encode(hash_hmac('sha256', $payload, $tenant->line_channel_secret, true));

            if (hash_equals($expected, $signature)) {
                return $tenant;
            }
        }

        return null;
    }
}
