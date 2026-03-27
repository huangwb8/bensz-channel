<?php

namespace App\Jobs;

use App\Models\Article;
use App\Models\User;
use App\Notifications\ArticlePublishedNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Notification;

class SendArticlePublishedNotificationsJob implements ShouldQueue
{
    use Queueable;

    public function __construct(public readonly int $articleId) {}

    public function handle(): void
    {
        $article = Article::query()
            ->with(['channel', 'tags'])
            ->find($this->articleId);

        if (! $article instanceof Article || ! $article->is_published || $article->published_at === null || $article->published_at->isFuture()) {
            return;
        }

        $recipients = User::query()
            ->whereNotNull('email')
            ->whereKeyNot($article->author_id)
            ->with(['notificationPreference', 'emailChannelSubscriptions', 'emailTagSubscriptions'])
            ->get()
            ->filter(fn (User $user) => $user->subscribesToArticle($article));

        if ($recipients->isEmpty()) {
            return;
        }

        Notification::send($recipients, new ArticlePublishedNotification($article));
    }
}
