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
            $table->timestamp('stripe_invoice_last_synced_at')->nullable()->after('stripe_subscription_id');
            $table->unsignedInteger('stripe_invoice_last_sync_count')->nullable()->after('stripe_invoice_last_synced_at');
        });
    }

    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table): void {
            $table->dropColumn([
                'stripe_invoice_last_synced_at',
                'stripe_invoice_last_sync_count',
            ]);
        });
    }
};