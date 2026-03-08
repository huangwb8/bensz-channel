<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;

class Channel extends Model
{
    /** @use HasFactory<\Database\Factories\ChannelFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'accent_color',
        'icon',
        'sort_order',
        'is_public',
    ];

    protected function casts(): array
    {
        return [
            'is_public' => 'bool',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function articles(): HasMany
    {
        return $this->hasMany(Article::class);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }
}
