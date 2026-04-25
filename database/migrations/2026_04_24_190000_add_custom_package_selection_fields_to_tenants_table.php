<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tenants', function (Blueprint $table): void {
            $table->unsignedInteger('subscribed_room_limit')->nullable()->after('access_expires_at');
            $table->boolean('subscribed_slipok_enabled')->default(false)->after('subscribed_room_limit');
            $table->unsignedInteger('subscribed_slipok_monthly_limit')->nullable()->after('subscribed_slipok_enabled');
        });
    }

    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table): void {
            $table->dropColumn([
                'subscribed_room_limit',
                'subscribed_slipok_enabled',
                'subscribed_slipok_monthly_limit',
            ]);
        });
    }
};