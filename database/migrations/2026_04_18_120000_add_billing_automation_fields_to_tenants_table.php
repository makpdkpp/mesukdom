<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tenants', function (Blueprint $table): void {
            $table->decimal('default_water_fee', 10, 2)->default(0)->after('support_line_id');
            $table->decimal('default_electricity_fee', 10, 2)->default(0)->after('default_water_fee');
            $table->decimal('default_service_fee', 10, 2)->default(0)->after('default_electricity_fee');
            $table->unsignedTinyInteger('utility_entry_reminder_day')->default(25)->after('default_service_fee');
            $table->unsignedTinyInteger('invoice_generate_day')->default(1)->after('utility_entry_reminder_day');
            $table->unsignedTinyInteger('invoice_send_day')->default(2)->after('invoice_generate_day');
            $table->string('invoice_send_channels', 20)->default('line')->after('invoice_send_day');
            $table->unsignedTinyInteger('overdue_reminder_after_days')->default(1)->after('invoice_send_channels');
            $table->string('overdue_reminder_channels', 20)->default('line')->after('overdue_reminder_after_days');
        });
    }

    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table): void {
            $table->dropColumn([
                'default_water_fee',
                'default_electricity_fee',
                'default_service_fee',
                'utility_entry_reminder_day',
                'invoice_generate_day',
                'invoice_send_day',
                'invoice_send_channels',
                'overdue_reminder_after_days',
                'overdue_reminder_channels',
            ]);
        });
    }
};
