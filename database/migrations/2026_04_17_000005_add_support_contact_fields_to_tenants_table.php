<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tenants', function (Blueprint $table): void {
            $table->string('support_contact_name')->nullable()->after('line_channel_secret');
            $table->string('support_contact_phone')->nullable()->after('support_contact_name');
            $table->string('support_line_id')->nullable()->after('support_contact_phone');
        });
    }

    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table): void {
            $table->dropColumn([
                'support_contact_name',
                'support_contact_phone',
                'support_line_id',
            ]);
        });
    }
};
