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
 * @property bool $stripe_enabled
 * @property string $stripe_mode
 * @property ?string $stripe_publishable_key
 * @property ?string $stripe_secret_key
 * @property ?string $stripe_webhook_secret
 * @property ?string $platform_line_channel_id
 * @property ?string $platform_line_basic_id
 * @property ?string $platform_line_channel_access_token
 * @property ?string $platform_line_channel_secret
 * @property ?string $platform_line_webhook_url
 * @property bool $default_notify_owner_payment_received
 * @property bool $default_notify_owner_utility_reminder_day
 * @property bool $default_notify_owner_invoice_create_day
 * @property bool $default_notify_owner_invoice_send_day
 * @property bool $default_notify_owner_overdue_digest
 * @property string $default_notify_owner_channels
 * @property bool $platform_line_owner_broadcast_enabled
 */
final class PlatformSetting extends Model
{
    protected $fillable = [
        'slipok_enabled',
        'slipok_api_url',
        'slipok_api_secret',
        'slipok_secret_header_name',
        'slipok_timeout_seconds',
        'stripe_enabled',
        'stripe_mode',
        'stripe_publishable_key',
        'stripe_secret_key',
        'stripe_webhook_secret',
        'platform_line_channel_id',
        'platform_line_basic_id',
        'platform_line_channel_access_token',
        'platform_line_channel_secret',
        'platform_line_webhook_url',
        'default_notify_owner_payment_received',
        'default_notify_owner_utility_reminder_day',
        'default_notify_owner_invoice_create_day',
        'default_notify_owner_invoice_send_day',
        'default_notify_owner_overdue_digest',
        'default_notify_owner_channels',
        'platform_line_owner_broadcast_enabled',
    ];

    protected function casts(): array
    {
        return [
            'slipok_enabled' => 'boolean',
            'slipok_timeout_seconds' => 'integer',
            'stripe_enabled' => 'boolean',
            'default_notify_owner_payment_received' => 'boolean',
            'default_notify_owner_utility_reminder_day' => 'boolean',
            'default_notify_owner_invoice_create_day' => 'boolean',
            'default_notify_owner_invoice_send_day' => 'boolean',
            'default_notify_owner_overdue_digest' => 'boolean',
            'platform_line_owner_broadcast_enabled' => 'boolean',
        ];
    }

    public static function current(): self
    {
        return self::query()->firstOrCreate([], [
            'slipok_enabled' => false,
            'slipok_api_url' => 'https://connect.slip2go.com/api/verify-slip/qr-base64/info',
            'slipok_secret_header_name' => 'Authorization',
            'slipok_timeout_seconds' => 15,
            'stripe_enabled' => false,
            'stripe_mode' => 'test',
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

    /**
     * @return Attribute<?string, ?string>
     */
    protected function stripeSecretKey(): Attribute
    {
        return Attribute::make(
            get: fn (mixed $value): ?string => $this->decryptNullableString($value),
            set: fn (mixed $value): ?string => $this->encryptNullableString($value),
        );
    }

    /**
     * @return Attribute<?string, ?string>
     */
    protected function stripeWebhookSecret(): Attribute
    {
        return Attribute::make(
            get: fn (mixed $value): ?string => $this->decryptNullableString($value),
            set: fn (mixed $value): ?string => $this->encryptNullableString($value),
        );
    }

    /**
     * @return Attribute<?string, ?string>
     */
    protected function platformLineChannelAccessToken(): Attribute
    {
        return Attribute::make(
            get: fn (mixed $value): ?string => $this->decryptNullableString($value),
            set: fn (mixed $value): ?string => $this->encryptNullableString($value),
        );
    }

    /**
     * @return Attribute<?string, ?string>
     */
    protected function platformLineChannelSecret(): Attribute
    {
        return Attribute::make(
            get: fn (mixed $value): ?string => $this->decryptNullableString($value),
            set: fn (mixed $value): ?string => $this->encryptNullableString($value),
        );
    }

    public function hasPlatformLineCredentials(): bool
    {
        return filled($this->platform_line_channel_id)
            && filled($this->platform_line_channel_access_token)
            && filled($this->platform_line_channel_secret);
    }

    public function hasSlipOkCredentials(): bool
    {
        return $this->slipok_enabled
            && filled($this->slipok_api_url)
            && filled($this->slipok_api_secret)
            && filled($this->slipok_secret_header_name);
    }

    public function hasStripeCredentials(): bool
    {
        if (! $this->stripe_enabled) {
            return false;
        }

        return filled($this->stripe_publishable_key)
            && filled($this->stripe_secret_key)
            && filled($this->stripe_webhook_secret)
            && in_array($this->stripe_mode, ['test', 'live'], true);
    }

    public function stripeReadinessPayload(): array
    {
        return [
            'enabled' => (bool) $this->stripe_enabled,
            'mode' => (string) ($this->stripe_mode ?? 'test'),
            'publishable_key_configured' => filled($this->stripe_publishable_key),
            'secret_key_configured' => filled($this->stripe_secret_key),
            'webhook_secret_configured' => filled($this->stripe_webhook_secret),
            'ready' => $this->hasStripeCredentials(),
        ];
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