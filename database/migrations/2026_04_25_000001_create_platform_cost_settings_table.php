<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('platform_cost_settings', function (Blueprint $table): void {
            $table->id();
            $table->string('provider');
            $table->string('cost_type');
            $table->decimal('unit_cost', 12, 4)->default(0);
            $table->decimal('percentage_rate', 8, 4)->default(0);
            $table->decimal('fixed_fee', 12, 4)->default(0);
            $table->unsignedInteger('included_quota')->default(0);
            $table->decimal('overage_unit_cost', 12, 4)->default(0);
            $table->string('currency', 10)->default('THB');
            $table->date('effective_from');
            $table->date('effective_to')->nullable();
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['provider', 'is_active', 'effective_from'], 'platform_cost_provider_active_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('platform_cost_settings');
    }
};