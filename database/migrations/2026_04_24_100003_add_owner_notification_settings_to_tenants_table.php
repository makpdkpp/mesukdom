<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tenants', function (Blueprint $table): void {
            // Nullable booleans: null = inherit platform default
            $table->boolean('notify_owner_payment_received')->nullable()->after('overdue_reminder_channels');
            $table->boolean('notify_owner_utility_reminder_day')->nullable()->after('notify_owner_payment_received');
            $table->boolean('notify_owner_invoice_create_day')->nullable()->after('notify_owner_utility_reminder_day');
            $table->boolean('notify_owner_invoice_send_day')->nullable()->after('notify_owner_invoice_create_day');
            $table->boolean('notify_owner_overdue_digest')->nullable()->after('notify_owner_invoice_send_day');
            $table->string('notify_owner_channels', 20)->nullable()->after('notify_owner_overdue_digest');
        });
    }

    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table): void {
            $table->dropColumn([
                'notify_owner_payment_received',
                'notify_owner_utility_reminder_day',
                'notify_owner_invoice_create_day',
                'notify_owner_invoice_send_day',
                'notify_owner_overdue_digest',
                'notify_owner_channels',
            ]);
        });
    }
};
