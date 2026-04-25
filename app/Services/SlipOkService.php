<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\PlatformSetting;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;

final class SlipOkService
{
    private const ENDPOINT_MODE_QR_BASE64 = 'qr_base64';
    private const ENDPOINT_MODE_QR_CODE = 'qr_code';

    /**
     * @return array{status:string,message:string,request:array<string,mixed>,response:array<string,mixed>}
     */
    public function verifySlip(PlatformSetting $setting, ?string $qrCode, ?string $qrBase64): array
    {
        $requestPayload = $this->buildPayload($setting, $qrCode, $qrBase64);

        $response = Http::timeout((int) ($setting->slipok_timeout_seconds ?: 15))
            ->acceptJson()
            ->withHeaders([
                (string) $setting->slipok_secret_header_name => $this->authorizationValue($setting),
            ])
            ->post((string) $setting->slipok_api_url, $requestPayload);

        $responseBody = $response->json();

        if (! is_array($responseBody)) {
            $responseBody = ['raw' => $response->body()];
        }

        return [
            'status' => $this->interpretResponse($response->successful(), $responseBody),
            'message' => $this->messageFromResponse($response->successful(), $responseBody),
            'request' => $requestPayload,
            'response' => $responseBody,
        ];
    }

    /**
     * @return array{payload: array<string, mixed>}
     */
    private function buildPayload(PlatformSetting $setting, ?string $qrCode, ?string $qrBase64): array
    {
        $payload = [
            'payload' => [],
        ];

        if ($this->endpointMode($setting) === self::ENDPOINT_MODE_QR_BASE64) {
            if ($qrBase64 === null || $qrBase64 === '') {
                throw new \RuntimeException('Slip verification qr-base64 endpoint requires a base64 slip payload.');
            }

            $payload['payload']['imageBase64'] = $qrBase64;

            return $payload;
        }

        if ($qrCode === null || $qrCode === '') {
            throw new \RuntimeException('Slip verification qr-code endpoint requires a decoded QR string.');
        }

        $payload['payload']['qrCode'] = $qrCode;

        return $payload;
    }

    public function requiresDecodedQrCode(PlatformSetting $setting): bool
    {
        return $this->endpointMode($setting) === self::ENDPOINT_MODE_QR_CODE;
    }

    /**
     * @param array<string, mixed> $responseBody
     */
    private function interpretResponse(bool $requestOk, array $responseBody): string
    {
        if (! $requestOk) {
            return 'failed';
        }

        $code = $this->stringValue(Arr::get($responseBody, 'code'));

        if ($code === '200000') {
            return 'verified';
        }

        $truthyPaths = [
            'success',
            'status',
            'verified',
            'valid',
            'data.success',
            'data.status',
            'data.verified',
            'data.valid',
            'result.success',
            'result.status',
            'result.verified',
            'result.valid',
        ];

        foreach ($truthyPaths as $path) {
            $value = Arr::get($responseBody, $path);

            if (is_bool($value)) {
                return $value ? 'verified' : 'failed';
            }

            if (is_string($value)) {
                $normalized = strtolower(trim($value));

                if (in_array($normalized, ['verified', 'success', 'ok', 'slip found', 'slip found.'], true)) {
                    return 'verified';
                }

                if (in_array($normalized, ['failed', 'invalid', 'error'], true)) {
                    return 'failed';
                }
            }
        }

        $message = strtolower($this->stringValue(Arr::get($responseBody, 'message', Arr::get($responseBody, 'error', ''))));

        if ($message !== '' && preg_match('/slip found|verified|success/', $message) === 1) {
            return 'verified';
        }

        if ($message !== '' && preg_match('/invalid|fail|duplicate|not found|error/', $message) === 1) {
            return 'failed';
        }

        return 'review';
    }

    /**
     * @param array<string, mixed> $responseBody
     */
    private function messageFromResponse(bool $requestOk, array $responseBody): string
    {
        if (! $requestOk) {
            return $this->stringValue(Arr::get($responseBody, 'message', 'Slip verification request failed.'), 'Slip verification request failed.');
        }

        return $this->stringValue(Arr::get(
            $responseBody,
            'message',
            Arr::get($responseBody, 'statusMessage', 'Slip verification completed.')
        ), 'Slip verification completed.');
    }

    private function stringValue(mixed $value, string $default = ''): string
    {
        return is_scalar($value) ? (string) $value : $default;
    }

    private function authorizationValue(PlatformSetting $setting): string
    {
        $secret = $this->stringValue($setting->slipok_api_secret);
        $headerName = strtolower($this->stringValue($setting->slipok_secret_header_name, 'Authorization'));

        if ($headerName === 'authorization' && ! str_starts_with(strtolower($secret), 'bearer ')) {
            return 'Bearer '.$secret;
        }

        return $secret;
    }

    private function endpointMode(PlatformSetting $setting): string
    {
        $url = strtolower((string) $setting->slipok_api_url);

        if (str_contains($url, '/qr-base64/')) {
            return self::ENDPOINT_MODE_QR_BASE64;
        }

        if (str_contains($url, '/qr-code/')) {
            return self::ENDPOINT_MODE_QR_CODE;
        }

        return self::ENDPOINT_MODE_QR_CODE;
    }
}
