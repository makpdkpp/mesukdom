<?php

declare(strict_types=1);

namespace App\Services\Line;

final class CommandRouter
{
    public function fromText(string $text): string
    {
        $normalized = trim(mb_strtolower($text));

        return match ($normalized) {
            'บิล', 'invoice' => 'invoice',
            'จ่าย', 'pay' => 'pay',
            'ประวัติ', 'history' => 'history',
            'แจ้งซ่อม', 'repair' => 'repair',
            'ประกาศ', 'announcement', 'announcements' => 'announcements',
            'ติดต่อเจ้าของ', 'contact', 'owner' => 'contact',
            default => 'unknown',
        };
    }

    public function fromPostback(string $data): string
    {
        if ($data === '') {
            return 'unknown';
        }

        parse_str($data, $parsed);
        $action = mb_strtolower((string) ($parsed['action'] ?? $data));

        return match ($action) {
            'invoice' => 'invoice',
            'pay', 'payment' => 'pay',
            'history' => 'history',
            'repair' => 'repair',
            'announcements', 'announcement' => 'announcements',
            'contact', 'owner-contact' => 'contact',
            default => 'unknown',
        };
    }
}
