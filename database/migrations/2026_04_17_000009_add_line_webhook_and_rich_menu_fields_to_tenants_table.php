<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tenants', function (Blueprint $table): void {
            $table->text('line_channel_access_token')->nullable()->change();
            $table->text('line_channel_secret')->nullable()->change();
            $table->string('line_webhook_url')->nullable()->after('line_channel_id');
            $table->string('line_rich_menu_id')->nullable()->after('line_channel_secret');
        });
    }

    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table): void {
            $table->dropColumn(['line_webhook_url', 'line_rich_menu_id']);
            $table->string('line_channel_access_token', 500)->nullable()->change();
            $table->string('line_channel_secret', 255)->nullable()->change();
        });
    }
};
