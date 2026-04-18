<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table): void {
            $table->decimal('room_fee', 10, 2)->default(0)->after('invoice_no');
        });

        Schema::create('utility_records', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('contract_id')->constrained()->cascadeOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('room_id')->constrained()->cascadeOnDelete();
            $table->string('billing_month', 7);
            $table->decimal('water_units', 10, 2)->default(0);
            $table->decimal('electricity_units', 10, 2)->default(0);
            $table->decimal('other_amount', 10, 2)->default(0);
            $table->string('other_description')->nullable();
            $table->timestamps();

            $table->unique(['tenant_id', 'contract_id', 'billing_month'], 'utility_records_tenant_contract_month_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('utility_records');

        Schema::table('invoices', function (Blueprint $table): void {
            $table->dropColumn('room_fee');
        });
    }
};
