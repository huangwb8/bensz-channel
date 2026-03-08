<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SiteSetting extends Model
{
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
    ];
}
