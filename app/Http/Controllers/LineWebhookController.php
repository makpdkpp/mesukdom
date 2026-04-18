<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Jobs\ProcessWebhookEventJob;
use App\Models\Tenant;
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

        foreach ($request->json('events', []) as $event) {
            if (! is_array($event)) {
                continue;
            }

            /** @var array<string, mixed> $event */
            ProcessWebhookEventJob::dispatch($tenant->id, $event);
        }

        return response()->json(['ok' => true]);
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
