<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('saas_invoices', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('plan_id')->nullable()->constrained('plans')->nullOnDelete();

            $table->string('stripe_invoice_id')->unique();
            $table->string('stripe_customer_id')->nullable()->index();
            $table->string('stripe_subscription_id')->nullable()->index();
            $table->string('stripe_payment_intent_id')->nullable()->index();

            $table->string('status')->nullable();
            $table->string('currency', 10)->nullable();
            $table->bigInteger('amount_due')->nullable();
            $table->bigInteger('amount_paid')->nullable();
            $table->bigInteger('amount_remaining')->nullable();

            $table->timestamp('period_start')->nullable();
            $table->timestamp('period_end')->nullable();
            $table->timestamp('issued_at')->nullable();
            $table->timestamp('paid_at')->nullable();

            $table->text('hosted_invoice_url')->nullable();
            $table->text('invoice_pdf_url')->nullable();

            $table->json('payload')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('saas_invoices');
    }
};
