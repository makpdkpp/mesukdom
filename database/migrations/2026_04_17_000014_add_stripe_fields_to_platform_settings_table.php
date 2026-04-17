<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('platform_settings', function (Blueprint $table): void {
            $table->boolean('stripe_enabled')->default(false)->after('slipok_timeout_seconds');
            $table->string('stripe_mode')->default('test')->after('stripe_enabled');
            $table->string('stripe_publishable_key')->nullable()->after('stripe_mode');
            $table->text('stripe_secret_key')->nullable()->after('stripe_publishable_key');
            $table->text('stripe_webhook_secret')->nullable()->after('stripe_secret_key');
        });
    }

    public function down(): void
    {
        Schema::table('platform_settings', function (Blueprint $table): void {
            $table->dropColumn([
                'stripe_enabled',
                'stripe_mode',
                'stripe_publishable_key',
                'stripe_secret_key',
                'stripe_webhook_secret',
            ]);
        });
    }
};
