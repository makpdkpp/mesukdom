<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('line_webhook_logs', function (Blueprint $table): void {
            $table->string('event_hash', 64)->nullable()->after('tenant_id');
            $table->unique(['tenant_id', 'event_hash'], 'line_webhook_logs_tenant_event_hash_unique');
        });
    }

    public function down(): void
    {
        Schema::table('line_webhook_logs', function (Blueprint $table): void {
            $table->dropUnique('line_webhook_logs_tenant_event_hash_unique');
            $table->dropColumn('event_hash');
        });
    }
};