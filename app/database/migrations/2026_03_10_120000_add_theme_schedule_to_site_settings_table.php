<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('site_settings', function (Blueprint $table): void {
            $table->string('theme_mode', 20)->nullable()->after('cdn_asset_url');
            $table->string('theme_day_start', 5)->nullable()->after('theme_mode');
            $table->string('theme_night_start', 5)->nullable()->after('theme_day_start');
        });
    }

    public function down(): void
    {
        Schema::table('site_settings', function (Blueprint $table): void {
            $table->dropColumn(['theme_mode', 'theme_day_start', 'theme_night_start']);
        });
    }
};
