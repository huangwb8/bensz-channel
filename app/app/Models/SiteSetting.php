<?php

namespace App\Models;

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
        ];
    }

    protected $fillable = [
        'app_name',
        'site_name',
        'site_tagline',
        'auth_enabled_methods',
        'cdn_asset_url',
    ];
}
