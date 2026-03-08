<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LoginCode extends Model
{
    /** @use HasFactory<\Database\Factories\LoginCodeFactory> */
    use HasFactory;

    public const CHANNEL_EMAIL = 'email';

    public const CHANNEL_PHONE = 'phone';

    protected $fillable = [
        'channel',
        'target',
        'code',
        'expires_at',
        'consumed_at',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'consumed_at' => 'datetime',
            'meta' => 'array',
        ];
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query
            ->whereNull('consumed_at')
            ->where('expires_at', '>', now());
    }

    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }
}
