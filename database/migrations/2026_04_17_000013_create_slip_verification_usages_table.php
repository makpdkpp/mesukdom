<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('slip_verification_usages', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('plan_id')->nullable()->constrained('plans')->nullOnDelete();
            $table->foreignId('payment_id')->nullable()->constrained('payments')->nullOnDelete();
            $table->string('provider')->default('slipok');
            $table->string('usage_month', 7);
            $table->string('status')->default('review');
            $table->json('request_payload')->nullable();
            $table->json('response_payload')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'plan_id', 'provider', 'usage_month'], 'slip_verifications_monthly_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('slip_verification_usages');
    }
};