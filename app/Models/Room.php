<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use App\Support\TenantContext;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Room extends Model
{
    use BelongsToTenant, HasFactory;

    protected $fillable = [
        'tenant_id',
        'building_id',
        'building',
        'room_number',
        'floor',
        'room_type',
        'price',
        'status',
    ];

    protected static function booted(): void
    {
        static::saving(function (Room $room): void {
            $tenantId = $room->tenant_id ?: app(TenantContext::class)->id();
            if (! $tenantId) {
                return;
            }

            $room->building = trim((string) ($room->building ?: 'Main'));
            $room->room_type = trim((string) ($room->room_type ?: 'Standard'));
            $room->floor = max(1, (int) $room->floor);

            $building = $room->building_id
                ? Building::withoutGlobalScopes()->find($room->building_id)
                : null;

            if (! $building) {
                $building = Building::withoutGlobalScopes()->firstOrCreate(
                    [
                        'tenant_id' => $tenantId,
                        'name' => $room->building,
                    ],
                    [
                        'floor_count' => max(1, (int) $room->floor),
                        'room_types' => [
                            [
                                'name' => $room->room_type,
                                'price' => round((float) $room->price, 2),
                            ],
                        ],
                    ],
                );
            }

            $room->building_id = $building->id;
            $room->building = $building->name;

            $roomTypes = collect($building->normalizedRoomTypes());
            if ($roomTypes->doesntContain(fn (array $roomType): bool => $roomType['name'] === $room->room_type)) {
                $roomTypes->push([
                    'name' => $room->room_type,
                    'price' => round((float) $room->price, 2),
                ]);
            }

            $dirty = false;

            if ($building->floor_count < $room->floor) {
                $building->floor_count = $room->floor;
                $dirty = true;
            }

            if ($building->room_types !== $roomTypes->values()->all()) {
                $building->room_types = $roomTypes->values()->all();
                $dirty = true;
            }

            if ($dirty) {
                $building->save();
            }
        });
    }

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
        ];
    }

    public function buildingRecord(): BelongsTo
    {
        return $this->belongsTo(Building::class, 'building_id');
    }

    public function customers(): HasMany
    {
        return $this->hasMany(Customer::class);
    }

    public function contracts(): HasMany
    {
        return $this->hasMany(Contract::class);
    }

    public function broadcasts(): HasMany
    {
        return $this->hasMany(BroadcastMessage::class);
    }

    public function utilityRecords(): HasMany
    {
        return $this->hasMany(UtilityRecord::class);
    }
}
