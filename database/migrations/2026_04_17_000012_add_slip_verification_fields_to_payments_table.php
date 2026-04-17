<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table): void {
            $table->string('verification_provider')->nullable()->after('receipt_no');
            $table->string('verification_status')->nullable()->after('verification_provider');
            $table->text('verification_note')->nullable()->after('verification_status');
            $table->text('verification_qr_code')->nullable()->after('verification_note');
            $table->json('verification_payload')->nullable()->after('verification_qr_code');
            $table->timestamp('verification_checked_at')->nullable()->after('verification_payload');
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table): void {
            $table->dropColumn([
                'verification_provider',
                'verification_status',
                'verification_note',
                'verification_qr_code',
                'verification_payload',
                'verification_checked_at',
            ]);
        });
    }
};