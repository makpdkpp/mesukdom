<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\NotificationLog;
use App\Models\Tenant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class LineWebhookController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $tenant = Tenant::query()->orderBy('id')->first();

        foreach ($request->input('events', []) as $event) {
            $type = (string) data_get($event, 'type', 'unknown');
            $userId = (string) data_get($event, 'source.userId', 'guest');
            $text = trim((string) data_get($event, 'message.text', ''));
            $message = 'LINE event received';

            if ($type === 'follow') {
                $message = 'New LINE follower connected';
            }

            if ($type === 'message' && Str::startsWith($text, 'bind:')) {
                $phone = trim(Str::after($text, 'bind:'));

                $customer = Customer::withoutGlobalScopes()->where('phone', $phone)->first();

                if ($customer) {
                    $customer->update(['line_user_id' => $userId]);
                    $message = 'Resident LINE account linked';
                }
            }

            NotificationLog::create([
                'tenant_id' => $tenant?->id,
                'channel' => 'line',
                'event' => $type,
                'target' => $userId,
                'message' => $message,
                'status' => 'received',
                'payload' => $event,
            ]);
        }

        return response()->json(['ok' => true]);
    }
}
