<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Building extends Model
{
    use BelongsToTenant, HasFactory;

    protected $fillable = [
        'tenant_id',
        'name',
        'floor_count',
        'room_types',
    ];

    protected function casts(): array
    {
        return [
            'room_types' => 'array',
        ];
    }

    public function rooms(): HasMany
    {
        return $this->hasMany(Room::class);
    }

    public function normalizedRoomTypes(): array
    {
        $roomTypes = is_array($this->room_types) ? $this->room_types : [];

        return collect($roomTypes)
            ->map(function (mixed $roomType): ?array {
                if (! is_array($roomType)) {
                    return null;
                }

                $name = trim((string) ($roomType['name'] ?? ''));
                if ($name === '') {
                    return null;
                }

                return [
                    'name' => $name,
                    'price' => round((float) ($roomType['price'] ?? 0), 2),
                ];
            })
            ->filter()
            ->values()
            ->all();
    }
}