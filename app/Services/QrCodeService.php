<?php

declare(strict_types=1);

namespace App\Services;

use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;

final class QrCodeService
{
    public function generateSvg(string $content, int $size = 300): string
    {
        $renderer = new ImageRenderer(new RendererStyle($size), new SvgImageBackEnd());

        return (new Writer($renderer))->writeString($content);
    }
}