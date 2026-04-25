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
            $table->string('billing_option')->nullable()->after('subscription_current_period_end');
            $table->timestamp('access_expires_at')->nullable()->after('billing_option');
        });

        Schema::table('plans', function (Blueprint $table): void {
            $table->decimal('price_yearly', 10, 2)->nullable()->after('price_monthly');
            $table->decimal('price_prepaid_annual', 10, 2)->nullable()->after('price_yearly');
            $table->string('stripe_yearly_price_id')->nullable()->after('stripe_price_id');
            $table->string('stripe_prepaid_annual_price_id')->nullable()->after('stripe_yearly_price_id');
        });
    }

    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table): void {
            $table->dropColumn([
                'billing_option',
                'access_expires_at',
            ]);
        });

        Schema::table('plans', function (Blueprint $table): void {
            $table->dropColumn([
                'price_yearly',
                'price_prepaid_annual',
                'stripe_yearly_price_id',
                'stripe_prepaid_annual_price_id',
            ]);
        });
    }
};