<?php

declare(strict_types=1);

namespace App\Services\Line;

use App\Models\Tenant;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use GdImage;
use RuntimeException;

final class LineService
{
    /**
     * @return array<string, mixed>
     */
    public function replyLinkPrompt(Tenant $tenant, ?string $replyToken, string $message, string $url): array
    {
        return $this->replyMessage($tenant, $replyToken, [
            'type' => 'template',
            'altText' => 'ยืนยันห้องพัก',
            'template' => [
                'type' => 'buttons',
                'title' => 'ยืนยันห้องพัก',
                'text' => $message,
                'actions' => [[
                    'type' => 'uri',
                    'label' => 'ยืนยันห้องพัก',
                    'uri' => $url,
                ]],
            ],
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function replyText(Tenant $tenant, ?string $replyToken, string $message): array
    {
        return $this->replyMessage($tenant, $replyToken, [
            'type' => 'text',
            'text' => $message,
        ]);
    }

    /**
     * @param array<string,mixed> $flex
     * @return array<string,mixed>
     */
    public function replyFlex(Tenant $tenant, ?string $replyToken, array $flex): array
    {
        return $this->replyMessage($tenant, $replyToken, $flex);
    }

    /**
     * @param array<string, mixed> $message
     * @return array<string, mixed>
     */
    public function replyMessage(Tenant $tenant, ?string $replyToken, array $message): array
    {
        $token = $this->resolveAccessToken($tenant);

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
     * @return array<string, mixed>
     */
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

    /**
     * @return array<string, mixed>
     */
    public function syncResidentRichMenu(Tenant $tenant): array
    {
        $token = $this->resolveAccessToken($tenant);

        if (! $token) {
            return ['status' => 'skipped'];
        }

        $oldRichMenuId = $tenant->line_rich_menu_id;

        $createResponse = Http::withToken($token)
            ->post('https://api.line.me/v2/bot/richmenu', $this->residentRichMenuDefinition());

        if (! $createResponse->successful()) {
            return $this->responsePayload($createResponse);
        }

        $richMenuId = (string) data_get($createResponse->json(), 'richMenuId', '');

        if ($richMenuId === '') {
            return [
                'status' => 'failed',
                'response' => $createResponse->json() ?: $createResponse->body(),
            ];
        }

        $imageResponse = Http::withToken($token)
            ->withHeaders(['Content-Type' => 'image/png'])
            ->send('POST', 'https://api-data.line.me/v2/bot/richmenu/'.$richMenuId.'/content', [
                'body' => $this->residentRichMenuImage(),
            ]);

        if (! $imageResponse->successful()) {
            return $this->responsePayload($imageResponse);
        }

        $setDefaultResponse = Http::withToken($token)
            ->post('https://api.line.me/v2/bot/user/all/richmenu/'.$richMenuId);

        if (! $setDefaultResponse->successful()) {
            return $this->responsePayload($setDefaultResponse);
        }

        if ($oldRichMenuId) {
            Http::withToken($token)->delete('https://api.line.me/v2/bot/richmenu/'.$oldRichMenuId);
        }

        return [
            'status' => 'sent',
            'richMenuId' => $richMenuId,
            'response' => [
                'create' => $createResponse->json() ?: $createResponse->body(),
                'default' => $setDefaultResponse->json() ?: $setDefaultResponse->body(),
            ],
        ];
    }

    private function resolveAccessToken(Tenant $tenant): ?string
    {
        $tenantToken = $tenant->line_channel_access_token;

        if (is_string($tenantToken) && $tenantToken !== '') {
            return $tenantToken;
        }

        return null;
    }

    /**
     * @return array<string, mixed>
     */
    private function residentRichMenuDefinition(): array
    {
        return [
            'size' => [
                'width' => 2500,
                'height' => 1686,
            ],
            'selected' => true,
            'name' => 'MesukDom Resident Menu',
            'chatBarText' => 'เมนูผู้เช่า',
            'areas' => [
                $this->menuArea(0, 0, 'invoice', 'ดูบิล'),
                $this->menuArea(833, 0, 'pay', 'ชำระเงิน'),
                $this->menuArea(1666, 0, 'history', 'ประวัติการจ่าย'),
                $this->menuArea(0, 843, 'repair', 'แจ้งซ่อม'),
                $this->menuArea(833, 843, 'announcements', 'ประกาศหอ'),
                $this->menuArea(1666, 843, 'contact', 'ติดต่อเจ้าของ'),
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function menuArea(int $x, int $y, string $action, string $label): array
    {
        return [
            'bounds' => [
                'x' => $x,
                'y' => $y,
                'width' => 833,
                'height' => 843,
            ],
            'action' => [
                'type' => 'postback',
                'label' => $label,
                'data' => 'action='.$action,
                'displayText' => $label,
            ],
        ];
    }

    private function residentRichMenuImage(): string
    {
        if (! function_exists('imagecreatetruecolor')) {
            throw new RuntimeException('The GD extension is required to generate the LINE rich menu image.');
        }

        $image = imagecreatetruecolor(2500, 1686);

        if (! $image instanceof GdImage) {
            throw new RuntimeException('Unable to create the LINE rich menu image canvas.');
        }

        $background = $this->allocateColor($image, 247, 249, 252);
        $line = $this->allocateColor($image, 203, 213, 225);
        $dark = $this->allocateColor($image, 15, 23, 42);
        $accent = $this->allocateColor($image, 5, 150, 105);
        $amber = $this->allocateColor($image, 245, 158, 11);
        $rose = $this->allocateColor($image, 225, 29, 72);
        $sky = $this->allocateColor($image, 2, 132, 199);
        $slate = $this->allocateColor($image, 71, 85, 105);
        $white = $this->allocateColor($image, 255, 255, 255);
        $softWhite = $this->allocateColor($image, 241, 245, 249);

        imagefill($image, 0, 0, $background);
        imagefilledrectangle($image, 0, 0, 2500, 180, $dark);
        $this->drawHeaderText($image, 'MESUKDORM', 84, 112, $white, 40);
        $this->drawHeaderText($image, 'Resident LINE Menu', 410, 112, $softWhite, 30);

        $areas = [
            [0, 180, $sky, 'ดูบิลล่าสุด', 'INVOICE', 'invoice'],
            [833, 180, $accent, 'แจ้งยอดชำระ', 'PAY NOW', 'pay'],
            [1666, 180, $amber, 'ประวัติการจ่าย', 'HISTORY', 'history'],
            [0, 933, $rose, 'แจ้งซ่อม', 'REPAIR', 'repair'],
            [833, 933, $dark, 'ประกาศหอพัก', 'NEWS', 'announcements'],
            [1666, 933, $accent, 'ติดต่อเจ้าของ', 'CONTACT', 'contact'],
        ];

        foreach ($areas as [$x, $y, $fill, $label, $caption, $icon]) {
            imagefilledrectangle($image, $x, $y, $x + 833, $y + 843, $fill);
            imagerectangle($image, $x, $y, $x + 833, $y + 843, $line);
            imagefilledrectangle($image, $x + 60, $y + 60, $x + 773, $y + 180, $this->withAlpha($image, 255, 255, 255, 100));
            $this->drawSmallLabel($image, $caption, $x + 96, $y + 136, $white);
            $this->drawMenuIcon($image, $icon, $x + 417, $y + 355, 130, $white, $softWhite, $slate);
            $this->drawCenteredText($image, $label, $x, $y + 230, 833, 320, $white, 52);
            $this->drawCenteredText($image, $this->menuHint($icon), $x, $y + 545, 833, 160, $softWhite, 26);
        }

        ob_start();
        imagepng($image);
        $binary = (string) ob_get_clean();
        imagedestroy($image);

        return $binary;
    }

    private function drawCenteredText(GdImage $image, string $label, int $x, int $y, int $width, int $height, int $color, int $fontSize = 42): void
    {
        $fontPath = $this->resolveRichMenuFontPath();

        if ($fontPath !== null && function_exists('imagettftext')) {
            $box = imagettfbbox($fontSize, 0, $fontPath, $label);

            if ($box === false) {
                throw new RuntimeException('Unable to measure LINE rich menu text.');
            }

            $textWidth = abs($box[4] - $box[0]);
            $textHeight = abs($box[5] - $box[1]);
            $textX = (int) round($x + (($width - $textWidth) / 2));
            $textY = (int) round($y + (($height + $textHeight) / 2));
            imagettftext($image, $fontSize, 0, $textX, $textY, $color, $fontPath, $label);

            return;
        }

        imagestring($image, 5, $x + 300, $y + 400, $label, $color);
    }

    private function drawHeaderText(GdImage $image, string $label, int $x, int $y, int $color, int $fontSize): void
    {
        $fontPath = $this->resolveRichMenuFontPath();

        if ($fontPath !== null && function_exists('imagettftext')) {
            imagettftext($image, $fontSize, 0, $x, $y, $color, $fontPath, $label);

            return;
        }

        imagestring($image, 5, $x, $y - 24, $label, $color);
    }

    private function drawSmallLabel(GdImage $image, string $label, int $x, int $y, int $color): void
    {
        $fontPath = $this->resolveRichMenuFontPath();

        if ($fontPath !== null && function_exists('imagettftext')) {
            imagettftext($image, 24, 0, $x, $y, $color, $fontPath, $label);

            return;
        }

        imagestring($image, 4, $x, $y - 18, $label, $color);
    }

    private function drawMenuIcon(GdImage $image, string $icon, int $centerX, int $centerY, int $size, int $primary, int $secondary, int $outline): void
    {
        imagefilledellipse($image, $centerX, $centerY, $size * 2, $size * 2, $this->withAlpha($image, 255, 255, 255, 108));
        imageellipse($image, $centerX, $centerY, $size * 2, $size * 2, $secondary);

        match ($icon) {
            'invoice' => $this->drawInvoiceIcon($image, $centerX, $centerY, $size, $primary, $outline),
            'pay' => $this->drawPayIcon($image, $centerX, $centerY, $size, $primary, $outline),
            'history' => $this->drawHistoryIcon($image, $centerX, $centerY, $size, $primary, $outline),
            'repair' => $this->drawRepairIcon($image, $centerX, $centerY, $size, $primary, $outline),
            'announcements' => $this->drawAnnouncementIcon($image, $centerX, $centerY, $size, $primary, $outline),
            'contact' => $this->drawContactIcon($image, $centerX, $centerY, $size, $primary, $outline),
            default => null,
        };
    }

    private function drawInvoiceIcon(GdImage $image, int $centerX, int $centerY, int $size, int $primary, int $outline): void
    {
        imagefilledrectangle($image, $centerX - 56, $centerY - 74, $centerX + 56, $centerY + 74, $primary);
        imagerectangle($image, $centerX - 56, $centerY - 74, $centerX + 56, $centerY + 74, $outline);
        for ($line = -30; $line <= 30; $line += 30) {
            imageline($image, $centerX - 34, $centerY + $line, $centerX + 34, $centerY + $line, $outline);
        }
    }

    private function drawPayIcon(GdImage $image, int $centerX, int $centerY, int $size, int $primary, int $outline): void
    {
        imagefilledellipse($image, $centerX, $centerY, 128, 128, $primary);
        imageellipse($image, $centerX, $centerY, 128, 128, $outline);
        imageline($image, $centerX, $centerY - 44, $centerX, $centerY + 44, $outline);
        imagearc($image, $centerX - 10, $centerY - 16, 76, 56, 260, 90, $outline);
        imagearc($image, $centerX + 10, $centerY + 16, 76, 56, 80, 270, $outline);
    }

    private function drawHistoryIcon(GdImage $image, int $centerX, int $centerY, int $size, int $primary, int $outline): void
    {
        imagearc($image, $centerX, $centerY, 144, 144, 35, 325, $primary);
        imageline($image, $centerX, $centerY, $centerX, $centerY - 38, $primary);
        imageline($image, $centerX, $centerY, $centerX + 34, $centerY + 18, $primary);
        imageline($image, $centerX - 42, $centerY - 60, $centerX - 64, $centerY - 86, $outline);
        imageline($image, $centerX - 42, $centerY - 60, $centerX - 8, $centerY - 60, $outline);
    }

    private function drawRepairIcon(GdImage $image, int $centerX, int $centerY, int $size, int $primary, int $outline): void
    {
        imageline($image, $centerX - 58, $centerY + 56, $centerX + 6, $centerY - 8, $primary);
        imagefilledpolygon($image, [
            $centerX + 6, $centerY - 8,
            $centerX + 58, $centerY - 60,
            $centerX + 82, $centerY - 36,
            $centerX + 30, $centerY + 16,
        ], 4, $primary);
        imagefilledrectangle($image, $centerX - 84, $centerY + 46, $centerX - 30, $centerY + 76, $outline);
    }

    private function drawAnnouncementIcon(GdImage $image, int $centerX, int $centerY, int $size, int $primary, int $outline): void
    {
        imagefilledpolygon($image, [
            $centerX - 70, $centerY - 20,
            $centerX + 20, $centerY - 62,
            $centerX + 20, $centerY + 22,
            $centerX - 70, $centerY + 64,
        ], 4, $primary);
        imagefilledrectangle($image, $centerX + 20, $centerY - 22, $centerX + 54, $centerY + 16, $primary);
        imageline($image, $centerX - 36, $centerY + 64, $centerX - 12, $centerY + 104, $outline);
    }

    private function drawContactIcon(GdImage $image, int $centerX, int $centerY, int $size, int $primary, int $outline): void
    {
        imagefilledellipse($image, $centerX, $centerY - 30, 86, 86, $primary);
        imagefilledarc($image, $centerX, $centerY + 68, 164, 118, 180, 360, $primary, IMG_ARC_PIE);
        imageellipse($image, $centerX, $centerY - 30, 86, 86, $outline);
    }

    private function menuHint(string $icon): string
    {
        return match ($icon) {
            'invoice' => 'ตรวจยอดล่าสุดและวันครบกำหนด',
            'pay' => 'รับลิงก์จ่ายเงินและส่งสลิป',
            'history' => 'ดูการชำระย้อนหลังล่าสุด',
            'repair' => 'ส่งเรื่องซ่อมผ่านฟอร์มทันที',
            'announcements' => 'เช็กข่าวสารและประกาศสำคัญ',
            'contact' => 'ดูเบอร์ติดต่อและ LINE เจ้าของ',
            default => 'เลือกเมนูเพื่อใช้งาน',
        };
    }

    /**
     * @param int<0, 255> $red
     * @param int<0, 255> $green
     * @param int<0, 255> $blue
     * @param int<0, 127> $alpha
     */
    private function withAlpha(GdImage $image, int $red, int $green, int $blue, int $alpha): int
    {
        $color = imagecolorallocatealpha($image, $red, $green, $blue, $alpha);

        if ($color === false) {
            throw new RuntimeException('Unable to allocate a translucent color for the LINE rich menu image.');
        }

        return $color;
    }

    private function resolveRichMenuFontPath(): ?string
    {
        $candidates = [
            'C:/Windows/Fonts/tahomabd.ttf',
            'C:/Windows/Fonts/tahoma.ttf',
            'C:/Windows/Fonts/LeelawUI.ttf',
            'C:/Windows/Fonts/arialbd.ttf',
        ];

        foreach ($candidates as $candidate) {
            if (is_file($candidate)) {
                return $candidate;
            }
        }

        return null;
    }

    /**
     * @param int<0, 255> $red
     * @param int<0, 255> $green
     * @param int<0, 255> $blue
     */
    private function allocateColor(GdImage $image, int $red, int $green, int $blue): int
    {
        $color = imagecolorallocate($image, $red, $green, $blue);

        if ($color === false) {
            throw new RuntimeException('Unable to allocate a color for the LINE rich menu image.');
        }

        return $color;
    }

    /**
     * @return array{status:string,response:mixed}
     */
    private function responsePayload(Response $response): array
    {
        return [
            'status' => $response->successful() ? 'sent' : 'failed',
            'response' => $response->json() ?: $response->body(),
        ];
    }
}
