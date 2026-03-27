<?php

namespace App\Support;

use App\Models\Article;
use App\Jobs\SendArticlePublishedNotificationsJob;

class ArticleSubscriptionNotifier
{
    public function send(Article $article): void
    {
        SendArticlePublishedNotificationsJob::dispatch($article->id);
    }
}
