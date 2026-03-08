<?php

namespace App\Support;

use App\Models\Article;
use App\Models\Channel;
use App\Models\Comment;
use App\Models\User;

class CommunityViewData
{
    public function layout(?Channel $currentChannel = null, ?Article $currentArticle = null): array
    {
        return [
            'siteName' => config('community.site.name'),
            'siteTagline' => config('community.site.tagline'),
            'channels' => Channel::query()
                ->ordered()
                ->withCount(['articles' => fn ($query) => $query->published()])
                ->get(),
            'pageTitle' => null,
            'currentChannel' => $currentChannel,
            'currentArticle' => $currentArticle,
        ];
    }

    public function home(): array
    {
        $featuredArticle = Article::query()
            ->published()
            ->with(['channel', 'author'])
            ->latestPublished()
            ->first();

        return [
            ...$this->chrome(),
            'pageTitle' => null,
            'featuredArticle' => $featuredArticle,
            'latestArticles' => Article::query()
                ->published()
                ->with(['channel', 'author'])
                ->latestPublished()
                ->limit(12)
                ->get(),
        ];
    }

    public function channel(Channel $channel): array
    {
        return [
            ...$this->chrome($channel),
            'pageTitle' => $channel->name,
            'currentChannel' => $channel,
            'channelArticles' => Article::query()
                ->published()
                ->whereBelongsTo($channel)
                ->with(['channel', 'author'])
                ->latestPublished()
                ->limit(20)
                ->get(),
        ];
    }

    public function article(Article $article): array
    {
        $article->load([
            'channel',
            'author',
            'comments.user',
        ]);

        return [
            ...$this->chrome($article->channel, $article),
            'pageTitle' => $article->title,
            'currentChannel' => $article->channel,
            'article' => $article,
            'relatedArticles' => Article::query()
                ->published()
                ->where('channel_id', $article->channel_id)
                ->whereKeyNot($article->id)
                ->with(['channel', 'author'])
                ->latestPublished()
                ->limit(5)
                ->get(),
        ];
    }

    public function chrome(?Channel $currentChannel = null, ?Article $currentArticle = null): array
    {
        return [
            ...$this->layout($currentChannel, $currentArticle),
            'recentComments' => Comment::query()
                ->where('is_visible', true)
                ->with(['article.channel', 'user'])
                ->latest()
                ->limit(5)
                ->get(),
            'stats' => [
                'channels' => Channel::query()->count(),
                'articles' => Article::query()->published()->count(),
                'comments' => Comment::query()->where('is_visible', true)->count(),
                'members' => User::query()->where('role', User::ROLE_MEMBER)->count(),
            ],
            'qrProviders' => config('community.auth.qr_providers'),
        ];
    }
}
