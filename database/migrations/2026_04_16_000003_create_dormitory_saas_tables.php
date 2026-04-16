<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tenants', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('domain')->nullable()->unique();
            $table->string('plan')->default('trial');
            $table->string('status')->default('active');
            $table->date('trial_ends_at')->nullable();
            $table->timestamps();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('tenant_id')->nullable()->after('id')->constrained()->nullOnDelete();
            $table->string('role')->default('owner')->after('password');
        });

        Schema::create('rooms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('room_number');
            $table->unsignedInteger('floor')->default(1);
            $table->string('room_type')->default('Standard');
            $table->decimal('price', 10, 2)->default(0);
            $table->string('status')->default('vacant');
            $table->timestamps();
        });

        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('room_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->string('line_id')->nullable();
            $table->string('line_user_id')->nullable();
            $table->string('id_card')->nullable();
            $table->timestamps();
        });

        Schema::create('contracts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('room_id')->constrained()->cascadeOnDelete();
            $table->date('start_date');
            $table->date('end_date');
            $table->decimal('deposit', 10, 2)->default(0);
            $table->decimal('monthly_rent', 10, 2)->default(0);
            $table->string('status')->default('active');
            $table->timestamps();
        });

        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('contract_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('room_id')->nullable()->constrained()->nullOnDelete();
            $table->string('public_id')->unique();
            $table->string('invoice_no')->unique();
            $table->decimal('total_amount', 10, 2)->default(0);
            $table->decimal('water_fee', 10, 2)->default(0);
            $table->decimal('electricity_fee', 10, 2)->default(0);
            $table->decimal('service_fee', 10, 2)->default(0);
            $table->string('status')->default('draft');
            $table->date('issued_at')->nullable();
            $table->date('due_date');
            $table->timestamps();
        });

        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('invoice_id')->constrained()->cascadeOnDelete();
            $table->decimal('amount', 10, 2);
            $table->date('payment_date');
            $table->string('method')->default('manual');
            $table->string('status')->default('pending');
            $table->string('slip_path')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('notification_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->nullable()->constrained()->nullOnDelete();
            $table->string('channel');
            $table->string('event');
            $table->string('target')->nullable();
            $table->text('message')->nullable();
            $table->string('status')->default('queued');
            $table->json('payload')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_logs');
        Schema::dropIfExists('payments');
        Schema::dropIfExists('invoices');
        Schema::dropIfExists('contracts');
        Schema::dropIfExists('customers');
        Schema::dropIfExists('rooms');

        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('tenant_id');
            $table->dropColumn('role');
        });

        Schema::dropIfExists('tenants');
    }
};
