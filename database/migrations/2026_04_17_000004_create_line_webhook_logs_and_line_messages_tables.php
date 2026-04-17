<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('line_webhook_logs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('event_type');
            $table->string('line_user_id')->nullable();
            $table->json('payload')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'event_type']);
            $table->index(['tenant_id', 'line_user_id']);
        });

        Schema::create('line_messages', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $table->string('direction');
            $table->string('message_type');
            $table->json('payload')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'customer_id']);
            $table->index(['tenant_id', 'direction']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('line_messages');
        Schema::dropIfExists('line_webhook_logs');
    }
};
