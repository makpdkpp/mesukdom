<?php

declare(strict_types=1);

namespace App\Services;

use Zxing\QrReader;

class SlipQrDecoder
{
    public function decodeFromFile(string $path): ?string
    {
        if (! is_file($path)) {
            return null;
        }

        $extension = strtolower((string) pathinfo($path, PATHINFO_EXTENSION));

        if (in_array($extension, ['pdf'], true)) {
            return null;
        }

        try {
            $reader = new QrReader($path);
            $text = $reader->text();
        } catch (\Throwable) {
            return null;
        }

        if (! is_string($text) || trim($text) === '') {
            return null;
        }

        return trim($text);
    }
}