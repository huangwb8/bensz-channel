<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Comment extends Model
{
    /** @use HasFactory<\Database\Factories\CommentFactory> */
    use HasFactory;

    protected $fillable = [
        'article_id',
        'user_id',
        'parent_id',
        'root_id',
        'markdown_body',
        'html_body',
        'is_visible',
    ];

    protected function casts(): array
    {
        return [
            'is_visible' => 'bool',
        ];
    }

    public function article(): BelongsTo
    {
        return $this->belongsTo(Article::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function root(): BelongsTo
    {
        return $this->belongsTo(self::class, 'root_id');
    }

    public function replies(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('created_at');
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(CommentSubscription::class);
    }

    public function canBeManagedBy(?User $user): bool
    {
        if (! $user instanceof User) {
            return false;
        }

        if ($user->isAdmin() || $this->user_id === $user->id) {
            return true;
        }

        $parentId = $this->parent_id;
        $seenParentIds = [];

        while ($parentId !== null) {
            if (isset($seenParentIds[$parentId])) {
                return false;
            }

            $seenParentIds[$parentId] = true;

            $parent = self::query()
                ->select(['id', 'user_id', 'parent_id'])
                ->find($parentId);

            if (! $parent instanceof self) {
                return false;
            }

            if ($parent->user_id === $user->id) {
                return true;
            }

            $parentId = $parent->parent_id;
        }

        return false;
    }
}
