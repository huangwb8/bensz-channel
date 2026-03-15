<?php

namespace App\Support;

use App\Models\Comment;
use Illuminate\Support\Facades\DB;

class CommentModerationService
{
    public function __construct(
        private readonly StaticPageBuilder $staticPageBuilder,
    ) {}

    public function updateVisibility(Comment $comment, bool $isVisible): Comment
    {
        $article = $comment->article()->with('channel')->firstOrFail();

        DB::transaction(function () use ($comment, $article, $isVisible): void {
            $comment->update([
                'is_visible' => $isVisible,
            ]);

            $article->refreshCommentCount();
        });

        $this->staticPageBuilder->rebuildAfterComment($article->fresh(['channel']));

        return $comment->fresh(['article.channel', 'user']);
    }

    public function delete(Comment $comment): void
    {
        $article = $comment->article()->with('channel')->firstOrFail();

        DB::transaction(function () use ($comment, $article): void {
            $comment->delete();
            $article->refreshCommentCount();
        });

        $this->staticPageBuilder->rebuildAfterComment($article->fresh(['channel']));
    }
}
