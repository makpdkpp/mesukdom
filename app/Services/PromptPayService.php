<?php

namespace App\Services;

use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;

/**
 * Generates Thai PromptPay QR code (EMVCo Merchant Presented QR / BOT standard)
 *
 * Supports:
 *   - Phone number  (0XXXXXXXXX ? 0066XXXXXXXXX)
 *   - National ID   (13 digits)
 *   - e-Wallet ID   (15 digits)
 */
class PromptPayService
{
    /**
     * Build the EMV payload string for PromptPay.
     *
     * @param  string      $target  Phone / NationalID / eWallet
     * @param  float|null  $amount  Amount in THB (null = any amount)
     */
    public function buildPayload(string $target, ?float $amount = null): string
    {
        $target = preg_replace('/[^0-9]/', '', $target);

        // Normalise phone number ? 0066XXXXXXXXX
        if (strlen($target) === 10 && str_starts_with($target, '0')) {
            $target = '0066' . substr($target, 1);
        } elseif (strlen($target) === 9) {
            $target = '0066' . $target;
        }

        // AID
        $isEWallet = strlen($target) === 15;
        $aid = $isEWallet ? 'A000000677010114' : 'A000000677010111';

        $merchantAccount    = $this->tlv('00', $aid) . $this->tlv('01', $target);
        $merchantAccountTag = $this->tlv('29', $merchantAccount);

        $payload =
            $this->tlv('00', '01') .
            $this->tlv('01', '12') .
            $merchantAccountTag .
            $this->tlv('53', '764') .
            ($amount !== null ? $this->tlv('54', number_format($amount, 2, '.', '')) : '') .
            $this->tlv('58', 'TH') .
            $this->tlv('59', 'PromptPay') .
            $this->tlv('60', 'Bangkok') .
            '6304';

        return $payload . $this->crc16($payload);
    }

    /**
     * Generate a PromptPay QR code and return SVG string.
     */
    public function generateSvg(string $target, ?float $amount = null, int $size = 300): string
    {
        $payload  = $this->buildPayload($target, $amount);
        $renderer = new ImageRenderer(new RendererStyle($size), new SvgImageBackEnd());

        return (new Writer($renderer))->writeString($payload);
    }

    private function tlv(string $tag, string $value): string
    {
        return $tag . str_pad(strlen($value), 2, '0', STR_PAD_LEFT) . $value;
    }

    private function crc16(string $payload): string
    {
        $crc = 0xFFFF;
        for ($i = 0, $len = strlen($payload); $i < $len; $i++) {
            $crc ^= ord($payload[$i]) << 8;
            for ($j = 0; $j < 8; $j++) {
                $crc = ($crc & 0x8000) ? (($crc << 1) ^ 0x1021) : ($crc << 1);
                $crc &= 0xFFFF;
            }
        }

        return strtoupper(str_pad(dechex($crc), 4, '0', STR_PAD_LEFT));
    }
}
