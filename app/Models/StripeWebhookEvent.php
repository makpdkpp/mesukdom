<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

final class StripeWebhookEvent extends Model
{
    protected $fillable = [
        'event_id',
        'event_type',
        'livemode',
        'received_at',
        'payload',
        'status',
        'last_error',
    ];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'livemode' => 'boolean',
            'received_at' => 'datetime',
        ];
    }
}
