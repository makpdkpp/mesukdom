<?php

declare(strict_types=1);

namespace App\Services\Line;

use App\Models\OwnerLineLink;
use App\Services\OwnerLineLinkService;

final class PlatformLineWebhookHandler
{
    public function __construct(
        private readonly PlatformLineService $platformLineService,
        private readonly OwnerLineLinkService $ownerLineLinkService,
    ) {}

    /**
     * @param array<string,mixed> $event
     * @return array{message:string,status:string,payload:array<string,mixed>}
     */
    public function handle(array $event): array
    {
        $type = (string) data_get($event, 'type', 'unknown');
        $replyToken = data_get($event, 'replyToken');
        $replyToken = is_string($replyToken) && $replyToken !== '' ? $replyToken : null;
        $userId = (string) data_get($event, 'source.userId', '');

        if ($type === 'follow') {
            $reply = $this->platformLineService->replyText($replyToken, 'เชื่อมต่อ Platform LINE OA แล้ว หากต้องการผูกบัญชีผู้ดูแล ให้พิมพ์ ADMIN:XXXXXX จากหน้า /admin/platform-line');

            return [
                'message' => 'Platform LINE follower connected',
                'status' => 'received',
                'payload' => ['reply' => $reply],
            ];
        }

        if (in_array($type, ['message', 'postback'], true)) {
            $input = $type === 'message'
                ? trim((string) data_get($event, 'message.text', ''))
                : trim((string) data_get($event, 'postback.data', ''));

            $parsed = OwnerLineLinkService::parseInboundToken($input);
            if ($parsed === null || $userId === '') {
                $reply = $this->platformLineService->replyText($replyToken, 'คำสั่งที่รองรับ: ADMIN:XXXXXX');

                return [
                    'message' => 'Platform LINE unsupported command',
                    'status' => 'ignored',
                    'payload' => ['reply' => $reply],
                ];
            }

            if ($parsed['scope'] !== OwnerLineLink::SCOPE_PLATFORM) {
                $message = 'รหัส OWNER ใช้สำหรับผูกเจ้าของหอผ่าน LINE OA ของหอ ไม่ใช่ช่องนี้ กรุณาส่งรหัสนี้ไปที่ LINE OA ของหอแทน';
                $reply = $this->platformLineService->replyText($replyToken, $message);

                return [
                    'message' => $message,
                    'status' => 'wrong_scope_owner_token',
                    'payload' => ['reply' => $reply],
                ];
            }

            $token = $parsed['token'];
            $link = $this->ownerLineLinkService->consume(OwnerLineLink::SCOPE_PLATFORM, $token, $userId);
            $message = $link === null
                ? 'รหัสผูก Platform LINE ไม่ถูกต้องหรือหมดอายุแล้ว'
                : 'ผูก Platform LINE ของผู้ดูแลสำเร็จ';

            $reply = $this->platformLineService->replyText($replyToken, $message);

            return [
                'message' => $message,
                'status' => $link === null ? 'invalid_platform_link_token' : 'platform_linked',
                'payload' => ['reply' => $reply],
            ];
        }

        return [
            'message' => 'Platform LINE event received',
            'status' => 'received',
            'payload' => [],
        ];
    }
}