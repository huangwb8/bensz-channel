<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\Concerns\HasPublicId;
use Illuminate\Database\Eloquent\Builder;

class Channel extends Model
{
    /** @use HasFactory<\Database\Factories\ChannelFactory> */
    use HasFactory, HasPublicId;

    public const SLUG_UNCATEGORIZED = 'uncategorized';
    public const SLUG_FEATURED = 'featured';

    protected $fillable = [
        'name',
        'slug',
        'description',
        'accent_color',
        'icon',
        'sort_order',
        'is_public',
        'show_in_top_nav',
    ];

    protected function casts(): array
    {
        return [
            'is_public' => 'bool',
            'show_in_top_nav' => 'bool',
        ];
    }

    public function isReserved(): bool
    {
        return in_array($this->slug, [self::SLUG_UNCATEGORIZED, self::SLUG_FEATURED, 'all'], true)
            || in_array($this->name, ['未分类', '精华', '全部'], true);
    }

    public function isFeaturedChannel(): bool
    {
        return $this->slug === self::SLUG_FEATURED || $this->name === '精华';
    }

    public function isUncategorizedChannel(): bool
    {
        return $this->slug === self::SLUG_UNCATEGORIZED || $this->name === '未分类';
    }

    public function canOwnArticlesDirectly(): bool
    {
        return ! $this->isFeaturedChannel();
    }


    public function articles(): HasMany
    {
        return $this->hasMany(Article::class);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }

    public function scopeVisibleInTopNav(Builder $query): Builder
    {
        return $query->where('show_in_top_nav', true);
    }

    public function scopeAssignableArticleChannels(Builder $query): Builder
    {
        return $query
            ->where('slug', '!=', self::SLUG_FEATURED)
            ->where('name', '!=', '精华');
    }
}
