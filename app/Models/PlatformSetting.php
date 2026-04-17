<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;

/**
 * @property bool $slipok_enabled
 * @property ?string $slipok_api_url
 * @property ?string $slipok_api_secret
 * @property ?string $slipok_secret_header_name
 * @property int $slipok_timeout_seconds
 */
final class PlatformSetting extends Model
{
    protected $fillable = [
        'slipok_enabled',
        'slipok_api_url',
        'slipok_api_secret',
        'slipok_secret_header_name',
        'slipok_timeout_seconds',
    ];

    protected function casts(): array
    {
        return [
            'slipok_enabled' => 'boolean',
            'slipok_timeout_seconds' => 'integer',
        ];
    }

    public static function current(): self
    {
        return self::query()->firstOrCreate([], [
            'slipok_enabled' => false,
            'slipok_api_url' => 'https://connect.slip2go.com/api/verify-slip/qr-base64/info',
            'slipok_secret_header_name' => 'Authorization',
            'slipok_timeout_seconds' => 15,
        ]);
    }

    /**
     * @return Attribute<?string, ?string>
     */
    protected function slipokApiSecret(): Attribute
    {
        return Attribute::make(
            get: fn (mixed $value): ?string => $this->decryptNullableString($value),
            set: fn (mixed $value): ?string => $this->encryptNullableString($value),
        );
    }

    public function hasSlipOkCredentials(): bool
    {
        return $this->slipok_enabled
            && filled($this->slipok_api_url)
            && filled($this->slipok_api_secret)
            && filled($this->slipok_secret_header_name);
    }

    private function decryptNullableString(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (! is_string($value)) {
            return null;
        }

        try {
            return Crypt::decryptString($value);
        } catch (DecryptException) {
            return $value;
        }
    }

    private function encryptNullableString(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        return Crypt::encryptString(is_scalar($value) ? (string) $value : '');
    }
}