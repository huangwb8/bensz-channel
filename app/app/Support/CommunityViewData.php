<?php

namespace App\Support;

use App\Models\Article;
use App\Models\Channel;
use App\Models\Comment;
use App\Models\CommentSubscription;
use App\Models\User;
use Illuminate\Support\Collection;

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
        ]);

        $articleBody = $this->articleBodyFormatter->format($article->html_body);
        $comments = Comment::query()
            ->where('article_id', $article->id)
            ->where('is_visible', true)
            ->with('user')
            ->orderBy('created_at')
            ->get();
        $commentTree = $this->buildCommentTree($comments);
        $currentUser = request()->user();
        $subscribedCommentIds = $currentUser instanceof User
            ? CommentSubscription::query()
                ->where('user_id', $currentUser->id)
                ->where('is_active', true)
                ->whereIn('comment_id', $comments->pluck('id')->all())
                ->pluck('comment_id')
                ->map(fn (mixed $id): int => (int) $id)
                ->all()
            : [];
        $manageableCommentIds = $currentUser instanceof User
            ? $this->collectManageableCommentIds($commentTree, $currentUser)
            : [];

        return [
            ...$this->chrome($article->channel, $article),
            'pageTitle' => $article->title,
            'currentChannel' => $article->channel,
            'article' => $article,
            'articleBody' => $articleBody,
            'commentTree' => $commentTree,
            'commentCount' => $comments->count(),
            'subscribedCommentIds' => $subscribedCommentIds,
            'manageableCommentIds' => $manageableCommentIds,
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

    /**
     * @param  Collection<int, Comment>  $comments
     * @return Collection<int, Comment>
     */
    private function buildCommentTree(Collection $comments): Collection
    {
        $grouped = $comments->groupBy(fn (Comment $comment) => $comment->parent_id ?? 0);

        $build = function (int $parentId = 0) use (&$build, $grouped): Collection {
            return collect($grouped->get($parentId, []))
                ->map(function (Comment $comment) use (&$build): Comment {
                    $comment->setRelation('threadChildren', $build($comment->id));

                    return $comment;
                })
                ->values();
        };

        return $build();
    }

    /**
     * @param  Collection<int, Comment>  $comments
     * @return list<int>
     */
    private function collectManageableCommentIds(Collection $comments, User $user, bool $inheritedManage = false): array
    {
        $manageableCommentIds = [];

        foreach ($comments as $comment) {
            $canManageCurrent = $user->isAdmin()
                || $inheritedManage
                || $comment->user_id === $user->id;

            if ($canManageCurrent) {
                $manageableCommentIds[] = $comment->id;
            }

            $manageableCommentIds = [
                ...$manageableCommentIds,
                ...$this->collectManageableCommentIds(
                    $comment->threadChildren,
                    $user,
                    $canManageCurrent,
                ),
            ];
        }

        return array_values(array_unique($manageableCommentIds));
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
