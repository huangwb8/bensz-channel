<?php

namespace App\Models;

use App\Enums\CdnMode;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class SiteSetting extends Model
{
    protected static function booted(): void
    {
        static::saved(static fn (): bool => Cache::forget('site_settings.current'));
        static::deleted(static fn (): bool => Cache::forget('site_settings.current'));
    }

    protected function casts(): array
    {
        return [
            'auth_enabled_methods' => 'array',
            'cdn_mode' => CdnMode::class,
            'cdn_storage_access_key' => 'encrypted',
            'cdn_storage_secret_key' => 'encrypted',
            'cdn_sync_enabled' => 'boolean',
            'cdn_sync_on_build' => 'boolean',
            'article_image_max_mb' => 'integer',
        ];
    }

    protected $fillable = [
        'app_name',
        'site_name',
        'site_tagline',
        'auth_enabled_methods',
        'cdn_asset_url',
        'cdn_mode',
        'cdn_storage_provider',
        'cdn_storage_access_key',
        'cdn_storage_secret_key',
        'cdn_storage_bucket',
        'cdn_storage_region',
        'cdn_storage_endpoint',
        'cdn_sync_enabled',
        'cdn_sync_on_build',
        'theme_mode',
        'theme_day_start',
        'theme_night_start',
        'article_image_max_mb',
    ];
}
