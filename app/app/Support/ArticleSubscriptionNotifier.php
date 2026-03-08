<?php

namespace App\Support;

use App\Models\Article;
use App\Models\User;
use App\Notifications\ArticlePublishedNotification;
use Illuminate\Support\Facades\Notification;

class ArticleSubscriptionNotifier
{
    public function send(Article $article): void
    {
        $article->loadMissing('channel');

        $recipients = User::query()
            ->whereNotNull('email')
            ->whereKeyNot($article->author_id)
            ->with(['notificationPreference', 'emailChannelSubscriptions'])
            ->get()
            ->filter(fn (User $user) => $user->subscribesToChannelArticles($article->channel_id));

        if ($recipients->isEmpty()) {
            return;
        }

        Notification::send($recipients, new ArticlePublishedNotification($article));
    }
}
