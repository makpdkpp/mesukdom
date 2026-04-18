<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('buildings', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->unsignedInteger('floor_count')->default(1);
            $table->json('room_types')->nullable();
            $table->timestamps();

            $table->unique(['tenant_id', 'name']);
        });

        Schema::table('rooms', function (Blueprint $table): void {
            $table->foreignId('building_id')->nullable()->after('tenant_id')->constrained('buildings')->nullOnDelete();
        });

        $now = now();

        $roomGroups = DB::table('rooms')
            ->select('tenant_id', 'building')
            ->distinct()
            ->orderBy('tenant_id')
            ->get();

        foreach ($roomGroups as $roomGroup) {
            $tenantId = (int) $roomGroup->tenant_id;
            $buildingName = trim((string) ($roomGroup->building ?? 'Main'));
            $buildingName = $buildingName !== '' ? $buildingName : 'Main';

            $floorCount = (int) DB::table('rooms')
                ->where('tenant_id', $tenantId)
                ->where('building', $buildingName)
                ->max('floor');

            $roomTypes = DB::table('rooms')
                ->where('tenant_id', $tenantId)
                ->where('building', $buildingName)
                ->select('room_type', DB::raw('MAX(price) as price'))
                ->groupBy('room_type')
                ->orderBy('room_type')
                ->get()
                ->map(static fn (object $roomType): array => [
                    'name' => trim((string) ($roomType->room_type ?? 'Standard')) ?: 'Standard',
                    'price' => round((float) ($roomType->price ?? 0), 2),
                ])
                ->values()
                ->all();

            $buildingId = DB::table('buildings')->insertGetId([
                'tenant_id' => $tenantId,
                'name' => $buildingName,
                'floor_count' => max(1, $floorCount),
                'room_types' => json_encode($roomTypes, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            DB::table('rooms')
                ->where('tenant_id', $tenantId)
                ->where('building', $buildingName)
                ->update(['building_id' => $buildingId]);
        }
    }

    public function down(): void
    {
        Schema::table('rooms', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('building_id');
        });

        Schema::dropIfExists('buildings');
    }
};