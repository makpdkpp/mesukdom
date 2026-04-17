<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Jobs\ProcessWebhookEventJob;
use App\Models\Tenant;
use App\Support\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LineWebhookController extends Controller
{
    public function __invoke(Request $request): JsonResponse
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

        foreach ($request->json('events', []) as $event) {
            $this->handleEvent($tenant, $event);
        }

        return response()->json(['ok' => true]);
    }

    protected function handleEvent(Tenant $tenant, array $event): void
    {
        ProcessWebhookEventJob::dispatch($tenant->id, $event);
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
