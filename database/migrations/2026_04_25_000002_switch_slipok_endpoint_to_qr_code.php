<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const OLD_SLIPOK_URL = 'https://connect.slip2go.com/api/verify-slip/qr-base64/info';
    private const NEW_SLIPOK_URL = 'https://connect.slip2go.com/api/verify-slip/qr-code/info';

    public function up(): void
    {
        if (! Schema::hasTable('platform_settings')) {
            return;
        }

        DB::table('platform_settings')
            ->where('slipok_api_url', self::OLD_SLIPOK_URL)
            ->update([
                'slipok_api_url' => self::NEW_SLIPOK_URL,
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        if (! Schema::hasTable('platform_settings')) {
            return;
        }

        DB::table('platform_settings')
            ->where('slipok_api_url', self::NEW_SLIPOK_URL)
            ->update([
                'slipok_api_url' => self::OLD_SLIPOK_URL,
                'updated_at' => now(),
            ]);
    }
};