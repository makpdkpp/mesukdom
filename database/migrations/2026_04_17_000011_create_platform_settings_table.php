<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('platform_settings', function (Blueprint $table): void {
            $table->id();
            $table->boolean('slipok_enabled')->default(false);
            $table->string('slipok_api_url')->nullable();
            $table->text('slipok_api_secret')->nullable();
            $table->string('slipok_secret_header_name')->default('X-API-SECRET');
            $table->unsignedInteger('slipok_timeout_seconds')->default(15);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('platform_settings');
    }
};