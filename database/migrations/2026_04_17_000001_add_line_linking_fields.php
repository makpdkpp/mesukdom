<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tenants', function (Blueprint $table): void {
            $table->string('line_channel_id')->nullable()->after('promptpay_number');
        });

        Schema::table('customers', function (Blueprint $table): void {
            $table->timestamp('line_linked_at')->nullable()->after('line_user_id');
        });
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table): void {
            $table->dropColumn('line_linked_at');
        });

        Schema::table('tenants', function (Blueprint $table): void {
            $table->dropColumn('line_channel_id');
        });
    }
};