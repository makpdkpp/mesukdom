<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Jobs\ProcessPlatformWebhookEventJob;
use App\Models\PlatformSetting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class PlatformLineWebhookController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $payload = $request->getContent();
        $signature = (string) $request->header('x-line-signature', '');

        if (! $this->signatureValid($payload, $signature)) {
            return response()->json(['ok' => false, 'message' => 'Invalid LINE signature.'], 401);
        }

        foreach ($request->json('events', []) as $event) {
            if (is_array($event)) {
                ProcessPlatformWebhookEventJob::dispatch($event);
            }
        }

        return response()->json(['ok' => true]);
    }

    private function signatureValid(string $payload, string $signature): bool
    {
        $secret = PlatformSetting::current()->platform_line_channel_secret;

        if (! is_string($secret) || $secret === '' || $signature === '') {
            return false;
        }

        $expected = base64_encode(hash_hmac('sha256', $payload, $secret, true));

        return hash_equals($expected, $signature);
    }
}