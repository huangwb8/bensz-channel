<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('channels', function (Blueprint $table) {
            $table->boolean('show_in_top_nav')->default(true)->after('is_public');
        });

        DB::table('channels')
            ->where('slug', 'uncategorized')
            ->update(['show_in_top_nav' => false]);
    }

    public function down(): void
    {
        Schema::table('channels', function (Blueprint $table) {
            $table->dropColumn('show_in_top_nav');
        });
    }
};
