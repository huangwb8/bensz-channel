<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Article extends Model
{
    /** @use HasFactory<\Database\Factories\ArticleFactory> */
    use HasFactory;

    protected $fillable = [
        'channel_id',
        'author_id',
        'title',
        'slug',
        'excerpt',
        'markdown_body',
        'html_body',
        'is_published',
        'published_at',
        'cover_gradient',
        'comment_count',
    ];

    protected function casts(): array
    {
        return [
            'is_published' => 'bool',
            'published_at' => 'datetime',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function channel(): BelongsTo
    {
        return $this->belongsTo(Channel::class);
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class)->where('is_visible', true)->latest();
    }

    public function allComments(): HasMany
    {
        return $this->hasMany(Comment::class)->latest();
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query
            ->where('is_published', true)
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now());
    }

    public function scopeLatestPublished(Builder $query): Builder
    {
        return $query->orderByDesc('published_at')->orderByDesc('id');
    }
}
