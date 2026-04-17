<?php

declare(strict_types=1);

namespace App\Services\Line;

use App\Models\Tenant;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

final class LineService
{
    public function replyText(Tenant $tenant, ?string $replyToken, string $message): array
    {
        $token = $this->resolveAccessToken($tenant);

        if (! $replyToken || ! $token) {
            return ['status' => 'skipped'];
        }

        $response = Http::withToken($token)
            ->post('https://api.line.me/v2/bot/message/reply', [
                'replyToken' => $replyToken,
                'messages' => [
                    [
                        'type' => 'text',
                        'text' => $message,
                    ],
                ],
            ]);

        return $this->responsePayload($response);
    }

    public function pushText(Tenant $tenant, ?string $lineUserId, string $message): array
    {
        $token = $this->resolveAccessToken($tenant);

        if (! $lineUserId || ! $token) {
            return ['status' => 'skipped'];
        }

        $response = Http::withToken($token)
            ->post('https://api.line.me/v2/bot/message/push', [
                'to' => $lineUserId,
                'messages' => [
                    [
                        'type' => 'text',
                        'text' => $message,
                    ],
                ],
            ]);

        return $this->responsePayload($response);
    }

    private function resolveAccessToken(Tenant $tenant): ?string
    {
        return $tenant->line_channel_access_token ?: config('services.line.channel_access_token');
    }

    private function responsePayload(Response $response): array
    {
        return [
            'status' => $response->successful() ? 'sent' : 'failed',
            'response' => $response->json() ?: $response->body(),
        ];
    }
}
