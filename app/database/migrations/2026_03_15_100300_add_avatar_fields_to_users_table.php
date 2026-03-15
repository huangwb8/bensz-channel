<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->string('avatar_type', 32)->default('generated')->after('avatar_url');
            $table->string('avatar_style', 32)->default('classic_letter')->after('avatar_type');
        });

        DB::table('users')
            ->whereNotNull('avatar_url')
            ->update(['avatar_type' => 'external']);
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn(['avatar_type', 'avatar_style']);
        });
    }
};
