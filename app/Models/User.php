<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Contracts\Encryption\DecryptException;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Crypt;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Laravel\Jetstream\HasProfilePhoto;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasApiTokens;

    /** @use HasFactory<UserFactory> */
    use HasFactory;

    use HasProfilePhoto;
    use Notifiable;
    use TwoFactorAuthenticatable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'tenant_id',
        'name',
        'email',
        'password',
        'role',
        'line_user_id',
        'line_user_id_hash',
        'line_linked_at',
        'platform_line_user_id',
        'platform_line_user_id_hash',
        'platform_line_linked_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_recovery_codes',
        'two_factor_secret',
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array<int, string>
     */
    protected $appends = [
        'profile_photo_url',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'line_linked_at' => 'datetime',
            'platform_line_linked_at' => 'datetime',
        ];
    }

    /**
     * @return Attribute<?string, ?string>
     */
    protected function lineUserId(): Attribute
    {
        return $this->encryptedNullableStringAttribute();
    }

    /**
     * @return Attribute<?string, ?string>
     */
    protected function platformLineUserId(): Attribute
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

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<OwnerLineLink, $this>
     */
    public function ownerLineLinks(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(OwnerLineLink::class);
    }

    /**
     * @return BelongsTo<Tenant, $this>
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function hasLinkedTenantLine(): bool
    {
        return $this->line_user_id !== null && $this->line_linked_at !== null;
    }

    public function hasLinkedPlatformLine(): bool
    {
        return $this->platform_line_user_id !== null && $this->platform_line_linked_at !== null;
    }

    public function hasRole(string $role): bool
    {
        return $this->role === $role;
    }

    public function hasAnyRole(array|string $roles): bool
    {
        $roles = is_array($roles) ? $roles : [$roles];

        return in_array($this->role, $roles, true);
    }

    public function canAccessTenantPortal(): bool
    {
        return $this->hasAnyRole(['owner', 'staff']);
    }

    public function canAccessAdminPortal(): bool
    {
        return $this->hasAnyRole(['super_admin', 'support_admin']);
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
}
