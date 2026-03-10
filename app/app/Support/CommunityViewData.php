<?php

namespace App\Support;

use App\Models\Article;
use App\Models\Channel;
use App\Models\Comment;
use App\Models\User;

class CommunityViewData
{
    public function __construct(
        private readonly ArticleBodyFormatter $articleBodyFormatter,
    ) {
    }

    public function layout(?Channel $currentChannel = null, ?Article $currentArticle = null): array
    {
        $themeMode = (string) config('community.theme.mode', 'auto');
        $themeDayStart = (string) config('community.theme.day_start', '07:00');
        $themeNightStart = (string) config('community.theme.night_start', '19:00');

        return [
            'siteName' => config('community.site.name'),
            'siteTagline' => config('community.site.tagline'),
            'themeMode' => $themeMode,
            'themeDayStart' => $themeDayStart,
            'themeNightStart' => $themeNightStart,
            'themeApplied' => $this->resolveInitialTheme($themeMode),
            'channels' => Channel::query()
                ->visibleInTopNav()
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
        $pinnedArticle = Article::query()
            ->published()
            ->pinned()
            ->with(['channel', 'author'])
            ->latestPublished()
            ->first();

        return [
            ...$this->chrome(),
            'pageTitle' => null,
            'pinnedArticle' => $pinnedArticle,
            'latestArticles' => Article::query()
                ->published()
                ->when($pinnedArticle instanceof Article, fn ($query) => $query->whereKeyNot($pinnedArticle->id))
                ->with(['channel', 'author'])
                ->latestPublished()
                ->limit(12)
                ->get(),
        ];
    }

    public function channel(Channel $channel): array
    {
        $articleQuery = Article::query()
            ->published()
            ->with(['channel', 'author'])
            ->latestPublished();

        if ($channel->isFeaturedChannel()) {
            $articleQuery->featured();
        } else {
            $articleQuery->whereBelongsTo($channel);
        }

        return [
            ...$this->chrome($channel),
            'pageTitle' => $channel->name,
            'currentChannel' => $channel,
            'channelArticles' => $articleQuery
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

        $articleBody = $this->articleBodyFormatter->format($article->html_body);

        return [
            ...$this->chrome($article->channel, $article),
            'pageTitle' => $article->title,
            'currentChannel' => $article->channel,
            'article' => $article,
            'articleBody' => $articleBody,
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

    private function resolveInitialTheme(string $mode): string
    {
        $normalizedMode = strtolower(trim($mode));

        return in_array($normalizedMode, ['light', 'dark'], true)
            ? $normalizedMode
            : 'light';
    }
}
