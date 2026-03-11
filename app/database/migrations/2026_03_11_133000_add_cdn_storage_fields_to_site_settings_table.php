<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('site_settings', function (Blueprint $table): void {
            $table->string('cdn_mode', 20)->default('origin')->after('cdn_asset_url');
            $table->string('cdn_storage_provider', 50)->nullable()->after('cdn_mode');
            $table->text('cdn_storage_access_key')->nullable()->after('cdn_storage_provider');
            $table->text('cdn_storage_secret_key')->nullable()->after('cdn_storage_access_key');
            $table->string('cdn_storage_bucket', 100)->nullable()->after('cdn_storage_secret_key');
            $table->string('cdn_storage_region', 100)->nullable()->after('cdn_storage_bucket');
            $table->string('cdn_storage_endpoint', 255)->nullable()->after('cdn_storage_region');
            $table->boolean('cdn_sync_enabled')->default(false)->after('cdn_storage_endpoint');
            $table->boolean('cdn_sync_on_build')->default(true)->after('cdn_sync_enabled');
        });
    }

    public function down(): void
    {
        Schema::table('site_settings', function (Blueprint $table): void {
            $table->dropColumn([
                'cdn_mode',
                'cdn_storage_provider',
                'cdn_storage_access_key',
                'cdn_storage_secret_key',
                'cdn_storage_bucket',
                'cdn_storage_region',
                'cdn_storage_endpoint',
                'cdn_sync_enabled',
                'cdn_sync_on_build',
            ]);
        });
    }
};
