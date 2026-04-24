<?php

declare(strict_types=1);

namespace App\Services\Line;

use App\Models\PlatformSetting;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

final class PlatformLineService
{
    /**
     * @param array<string,mixed> $message
     * @return array<string,mixed>
     */
    public function replyMessage(?string $replyToken, array $message): array
    {
        $token = $this->resolveAccessToken();

        if (! $replyToken || ! $token) {
            return ['status' => 'skipped'];
        }

        $response = Http::withToken($token)
            ->post('https://api.line.me/v2/bot/message/reply', [
                'replyToken' => $replyToken,
                'messages' => [$message],
            ]);

        return $this->responsePayload($response);
    }

    /**
     * @return array<string,mixed>
     */
    public function replyText(?string $replyToken, string $message): array
    {
        return $this->replyMessage($replyToken, ['type' => 'text', 'text' => $message]);
    }

    /**
     * @return array<string,mixed>
     */
    public function pushText(?string $lineUserId, string $message): array
    {
        $token = $this->resolveAccessToken();

        if (! $lineUserId || ! $token) {
            return ['status' => 'skipped'];
        }

        $response = Http::withToken($token)
            ->post('https://api.line.me/v2/bot/message/push', [
                'to' => $lineUserId,
                'messages' => [[
                    'type' => 'text',
                    'text' => $message,
                ]],
            ]);

        return $this->responsePayload($response);
    }

    private function resolveAccessToken(): ?string
    {
        $setting = PlatformSetting::current();
        $token = $setting->platform_line_channel_access_token;

        return is_string($token) && $token !== '' ? $token : null;
    }

    /**
     * @return array<string,mixed>
     */
    private function responsePayload(Response $response): array
    {
        $body = $response->json() ?: $response->body();

        return [
            'status' => $response->successful() ? 'sent' : 'failed',
            'status_code' => $response->status(),
            'response' => $body,
        ];
    }
}