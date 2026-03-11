<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('banned_at')->nullable()->after('last_seen_at');
            $table->timestamp('banned_until')->nullable()->after('banned_at');
            $table->index(['banned_at', 'banned_until']);
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['banned_at', 'banned_until']);
            $table->dropColumn(['banned_at', 'banned_until']);
        });
    }
};
