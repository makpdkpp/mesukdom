<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('broadcast_messages', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('scope');
            $table->string('target_building')->nullable();
            $table->unsignedInteger('target_floor')->nullable();
            $table->foreignId('room_id')->nullable()->constrained()->nullOnDelete();
            $table->text('message');
            $table->unsignedInteger('recipient_count')->default(0);
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'scope']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('broadcast_messages');
    }
};
