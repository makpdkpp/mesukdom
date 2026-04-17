<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Str;

/**
 * @property ?string $line_channel_access_token
 * @property ?string $line_channel_secret
 * @property ?string $line_basic_id
 * @property ?int $plan_id
 *
 * @use HasFactory<\Database\Factories\TenantFactory>
 */
class Tenant extends Model
{
    use HasFactory;

    protected $fillable = [
        'plan_id',
        'name',
        'domain',
        'promptpay_number',
        'line_channel_id',
        'line_basic_id',
        'line_webhook_url',
        'line_channel_access_token',
        'line_channel_secret',
        'line_rich_menu_id',
        'support_contact_name',
        'support_contact_phone',
        'support_line_id',
        'plan',
        'status',
        'trial_ends_at',
    ];

    protected function casts(): array
    {
        return [
            'trial_ends_at' => 'date',
        ];
    }

    /**
     * @return Attribute<?string, ?string>
     */
    protected function lineChannelAccessToken(): Attribute
    {
        return $this->encryptedNullableStringAttribute();
    }

    /**
     * @return Attribute<?string, ?string>
     */
    protected function lineChannelSecret(): Attribute
    {
        return $this->encryptedNullableStringAttribute();
    }

    /**
     * @return Attribute<?string, ?string>
     */
    protected function encryptedNullableStringAttribute(): Attribute
    {
        return Attribute::make(
            get: fn (mixed $value): ?string => $this->decryptAttributeValue($value),
            set: fn (mixed $value): ?string => $this->encryptAttributeValue($value),
        );
    }

    private function decryptAttributeValue(mixed $value): ?string
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

    private function encryptAttributeValue(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (! is_scalar($value)) {
            return null;
        }

        return Crypt::encryptString((string) $value);
    }

    /**
     * @return HasMany<User, $this>
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /**
     * @return BelongsTo<Plan, $this>
     */
    public function plan(): BelongsTo
    {
        return $this->subscriptionPlan();
    }

    /**
     * @return BelongsTo<Plan, $this>
     */
    public function subscriptionPlan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    public function resolvedPlan(): ?Plan
    {
        if ($this->relationLoaded('subscriptionPlan')) {
            /** @var ?Plan $plan */
            $plan = $this->getRelation('subscriptionPlan');

            return $plan;
        }

        return $this->subscriptionPlan()->first();
    }

    /**
     * @return HasMany<Room, $this>
     */
    public function rooms(): HasMany
    {
        return $this->hasMany(Room::class);
    }

    /**
     * @return HasMany<Customer, $this>
     */
    public function customers(): HasMany
    {
        return $this->hasMany(Customer::class);
    }

    /**
     * @return HasMany<Contract, $this>
     */
    public function contracts(): HasMany
    {
        return $this->hasMany(Contract::class);
    }

    /**
     * @return HasMany<Invoice, $this>
     */
    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    /**
     * @return HasMany<Payment, $this>
     */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    /**
     * @return HasMany<NotificationLog, $this>
     */
    public function notificationLogs(): HasMany
    {
        return $this->hasMany(NotificationLog::class);
    }

    /**
     * @return HasMany<LineWebhookLog, $this>
     */
    public function lineWebhookLogs(): HasMany
    {
        return $this->hasMany(LineWebhookLog::class);
    }

    /**
     * @return HasMany<LineMessage, $this>
     */
    public function lineMessages(): HasMany
    {
        return $this->hasMany(LineMessage::class);
    }

    /**
     * @return HasMany<BroadcastMessage, $this>
     */
    public function broadcasts(): HasMany
    {
        return $this->hasMany(BroadcastMessage::class);
    }

    /**
     * @return HasMany<SaasInvoice, $this>
     */
    public function saasInvoices(): HasMany
    {
        return $this->hasMany(SaasInvoice::class);
    }

    public function normalizedLineBasicId(): ?string
    {
        $value = trim((string) $this->line_basic_id);

        if ($value === '') {
            return null;
        }

        return Str::startsWith($value, '@') ? $value : '@'.$value;
    }

    public function lineAddFriendUrl(): ?string
    {
        $basicId = $this->normalizedLineBasicId();

        if ($basicId === null) {
            return null;
        }

        return 'https://line.me/R/ti/p/'.$basicId;
    }

    public function isSuspended(): bool
    {
        return $this->status === 'suspended';
    }

    public function isTrialExpired(): bool
    {
        if ($this->trial_ends_at === null) {
            return false;
        }

        return $this->trial_ends_at->endOfDay()->isPast();
    }

    public function hasActiveSubscription(): bool
    {
        return in_array($this->subscription_status, ['active', 'trialing'], true);
    }

    public function canWrite(): bool
    {
        if ($this->isSuspended()) {
            return false;
        }

        if ($this->hasActiveSubscription()) {
            return true;
        }

        return ! $this->isTrialExpired();
    }
}
