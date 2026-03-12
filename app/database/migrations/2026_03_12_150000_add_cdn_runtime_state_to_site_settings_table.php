<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('site_settings', function (Blueprint $table): void {
            $table->boolean('cdn_is_active')->default(false)->after('cdn_storage_endpoint');
            $table->longText('cdn_applied_snapshot')->nullable()->after('cdn_is_active');
        });
    }

    public function down(): void
    {
        Schema::table('site_settings', function (Blueprint $table): void {
            $table->dropColumn([
                'cdn_is_active',
                'cdn_applied_snapshot',
            ]);
        });
    }
};
