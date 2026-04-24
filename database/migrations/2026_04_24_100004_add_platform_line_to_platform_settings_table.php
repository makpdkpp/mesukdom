<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('platform_settings', function (Blueprint $table): void {
            // Platform LINE OA credentials
            $table->string('platform_line_channel_id')->nullable()->after('stripe_webhook_secret');
            $table->string('platform_line_basic_id')->nullable()->after('platform_line_channel_id');
            $table->text('platform_line_channel_access_token')->nullable()->after('platform_line_basic_id');
            $table->text('platform_line_channel_secret')->nullable()->after('platform_line_channel_access_token');
            $table->string('platform_line_webhook_url')->nullable()->after('platform_line_channel_secret');

            // Default toggles for owner notifications (true = enabled by default)
            $table->boolean('default_notify_owner_payment_received')->default(true)->after('platform_line_webhook_url');
            $table->boolean('default_notify_owner_utility_reminder_day')->default(true)->after('default_notify_owner_payment_received');
            $table->boolean('default_notify_owner_invoice_create_day')->default(true)->after('default_notify_owner_utility_reminder_day');
            $table->boolean('default_notify_owner_invoice_send_day')->default(true)->after('default_notify_owner_invoice_create_day');
            $table->boolean('default_notify_owner_overdue_digest')->default(true)->after('default_notify_owner_invoice_send_day');
            $table->string('default_notify_owner_channels', 20)->default('line')->after('default_notify_owner_overdue_digest');

            // Platform broadcast feature flag
            $table->boolean('platform_line_owner_broadcast_enabled')->default(false)->after('default_notify_owner_channels');
        });
    }

    public function down(): void
    {
        Schema::table('platform_settings', function (Blueprint $table): void {
            $table->dropColumn([
                'platform_line_channel_id',
                'platform_line_basic_id',
                'platform_line_channel_access_token',
                'platform_line_channel_secret',
                'platform_line_webhook_url',
                'default_notify_owner_payment_received',
                'default_notify_owner_utility_reminder_day',
                'default_notify_owner_invoice_create_day',
                'default_notify_owner_invoice_send_day',
                'default_notify_owner_overdue_digest',
                'default_notify_owner_channels',
                'platform_line_owner_broadcast_enabled',
            ]);
        });
    }
};
