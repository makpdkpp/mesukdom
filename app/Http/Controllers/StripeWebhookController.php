<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Jobs\ProcessStripeWebhookEventJob;
use App\Models\PlatformSetting;
use App\Models\StripeWebhookEvent;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Bus;

final class StripeWebhookController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $payload = $request->getContent();
        $signature = (string) $request->header('Stripe-Signature', '');

        $platformSetting = PlatformSetting::current();
        $webhookSecret = $platformSetting->stripe_webhook_secret;

        if (! $platformSetting->stripe_enabled || ! is_string($webhookSecret) || blank($webhookSecret)) {
            return response()->json(['ok' => false, 'message' => 'Stripe webhook not configured.'], 422);
        }

        try {
            $event = $this->parseAndVerifyEvent($payload, $signature, $webhookSecret);
        } catch (\Throwable $e) {
            Log::warning('Invalid Stripe webhook signature', ['error' => $e->getMessage()]);
            return response()->json(['ok' => false], 401);
        }

        $eventId = $this->stringValue($event['id'] ?? null);

        if ($eventId === '') {
            return response()->json(['ok' => false, 'message' => 'Stripe event payload missing id.'], 422);
        }

        $row = StripeWebhookEvent::query()->firstOrCreate(
            ['event_id' => $eventId],
            [
                'event_type' => $this->stringValue($event['type'] ?? null, 'unknown'),
                'livemode' => (bool) ($event['livemode'] ?? false),
                'received_at' => now(),
                'payload' => $event,
                'status' => 'received',
            ]
        );

        if ($row->status !== 'processed') {
            Bus::dispatchSync(new ProcessStripeWebhookEventJob($row->id));
        }

        return response()->json(['ok' => true]);
    }

    /**
     * @return array<string, mixed>
     */
    private function parseAndVerifyEvent(string $payload, string $signatureHeader, string $webhookSecret): array
    {
        $timestamp = null;
        $signatures = [];

        foreach (explode(',', $signatureHeader) as $part) {
            [$key, $value] = array_pad(explode('=', trim($part), 2), 2, null);

            if ($key === 't' && $value !== null && ctype_digit($value)) {
                $timestamp = (int) $value;
            }

            if ($key === 'v1' && $value !== null && $value !== '') {
                $signatures[] = $value;
            }
        }

        if ($timestamp === null || $signatures === []) {
            throw new \InvalidArgumentException('Stripe signature header is malformed.');
        }

        if (abs(time() - $timestamp) > 300) {
            throw new \InvalidArgumentException('Stripe signature timestamp is outside the allowed tolerance.');
        }

        $expectedSignature = hash_hmac('sha256', $timestamp . '.' . $payload, $webhookSecret);
        $isValid = collect($signatures)->contains(fn (string $signature): bool => hash_equals($expectedSignature, $signature));

        if (! $isValid) {
            throw new \InvalidArgumentException('Stripe signature verification failed.');
        }

        $decoded = json_decode($payload, true, 512, JSON_THROW_ON_ERROR);

        if (! is_array($decoded)) {
            throw new \InvalidArgumentException('Stripe event payload is invalid.');
        }

        /** @var array<string, mixed> $decoded */
        return $decoded;
    }

    private function stringValue(mixed $value, string $default = ''): string
    {
        return is_string($value) ? $value : $default;
    }
}
