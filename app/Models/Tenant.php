<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Tenant extends Model
{
    use HasFactory;

    protected $fillable = [
        'plan_id',
        'name',
        'domain',
        'promptpay_number',
        'line_channel_id',
        'line_channel_access_token',
        'line_channel_secret',
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

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    public function rooms(): HasMany
    {
        return $this->hasMany(Room::class);
    }

    public function customers(): HasMany
    {
        return $this->hasMany(Customer::class);
    }

    public function contracts(): HasMany
    {
        return $this->hasMany(Contract::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function notificationLogs(): HasMany
    {
        return $this->hasMany(NotificationLog::class);
    }

    public function lineWebhookLogs(): HasMany
    {
        return $this->hasMany(LineWebhookLog::class);
    }

    public function lineMessages(): HasMany
    {
        return $this->hasMany(LineMessage::class);
    }

    public function broadcasts(): HasMany
    {
        return $this->hasMany(BroadcastMessage::class);
    }
}
