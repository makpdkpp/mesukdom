<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BroadcastMessage extends Model
{
    use BelongsToTenant, HasFactory;

    protected $fillable = [
        'tenant_id',
        'scope',
        'target_building',
        'target_floor',
        'room_id',
        'message',
        'recipient_count',
        'sent_at',
    ];

    protected function casts(): array
    {
        return [
            'target_floor' => 'integer',
            'recipient_count' => 'integer',
            'sent_at' => 'datetime',
        ];
    }

    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }
}
