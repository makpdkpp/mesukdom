<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->string('line_user_id')->nullable()->after('role');
            $table->string('line_user_id_hash', 64)->nullable()->after('line_user_id');
            $table->timestamp('line_linked_at')->nullable()->after('line_user_id_hash');
            $table->string('platform_line_user_id')->nullable()->after('line_linked_at');
            $table->string('platform_line_user_id_hash', 64)->nullable()->after('platform_line_user_id');
            $table->timestamp('platform_line_linked_at')->nullable()->after('platform_line_user_id_hash');

            $table->index('line_user_id_hash');
            $table->index('platform_line_user_id_hash');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropIndex(['line_user_id_hash']);
            $table->dropIndex(['platform_line_user_id_hash']);
            $table->dropColumn([
                'line_user_id',
                'line_user_id_hash',
                'line_linked_at',
                'platform_line_user_id',
                'platform_line_user_id_hash',
                'platform_line_linked_at',
            ]);
        });
    }
};
