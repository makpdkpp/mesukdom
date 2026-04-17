<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Jobs\ProcessStripeWebhookEventJob;
use App\Models\PlatformSetting;
use App\Models\StripeWebhookEvent;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Stripe\Webhook;

final class StripeWebhookController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $payload = $request->getContent();
        $signature = (string) $request->header('Stripe-Signature', '');

        $platformSetting = PlatformSetting::current();

        if (! $platformSetting->stripe_enabled || blank($platformSetting->stripe_webhook_secret)) {
            return response()->json(['ok' => false, 'message' => 'Stripe webhook not configured.'], 422);
        }

        try {
            $event = Webhook::constructEvent($payload, $signature, (string) $platformSetting->stripe_webhook_secret);
        } catch (\Throwable $e) {
            Log::warning('Invalid Stripe webhook signature', ['error' => $e->getMessage()]);
            return response()->json(['ok' => false], 401);
        }

        $eventId = (string) $event->id;

        $row = StripeWebhookEvent::query()->firstOrCreate(
            ['event_id' => $eventId],
            [
                'event_type' => (string) $event->type,
                'livemode' => (bool) $event->livemode,
                'received_at' => now(),
                'payload' => $event->toArray(),
                'status' => 'received',
            ]
        );

        ProcessStripeWebhookEventJob::dispatch($row->id);

        return response()->json(['ok' => true]);
    }
}
