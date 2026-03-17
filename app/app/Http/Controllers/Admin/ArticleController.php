<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Models\Channel;
use App\Models\Tag;
use App\Support\ArticleSubscriptionNotifier;
use App\Support\MarkdownRenderer;
use App\Support\StaticPageBuilder;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class ArticleController extends Controller
{
    public function index(): View
    {
        return view('admin.articles.index', [
            'articles' => Article::query()
                ->with(['channel', 'author', 'tags'])
                ->orderByDesc('is_pinned')
                ->orderByDesc('is_featured')
                ->latest()
                ->get(),
        ]);
    }

    public function create(): View
    {
        return view('admin.articles.form', [
            'article' => new Article([
                'cover_gradient' => 'from-violet-500 via-fuchsia-500 to-cyan-500',
                'is_published' => true,
                'is_pinned' => false,
                'is_featured' => false,
                'published_at' => now(),
            ]),
            'channels' => Channel::query()->assignableArticleChannels()->ordered()->get(),
            'tags' => Tag::query()->ordered()->get(),
            'selectedTagIds' => [],
            'formAction' => route('admin.articles.store'),
            'formMethod' => 'POST',
        ]);
    }

    public function store(
        Request $request,
        MarkdownRenderer $markdownRenderer,
        ArticleSubscriptionNotifier $articleSubscriptionNotifier,
        StaticPageBuilder $staticPageBuilder,
    ): RedirectResponse {
        $validated = $this->validateArticle($request);

        $article = Article::query()->create([
            ...$this->articleAttributes($validated),
            'author_id' => $request->user()->id,
            'excerpt' => $validated['excerpt'] ?: $markdownRenderer->excerpt($validated['markdown_body']),
            'html_body' => $markdownRenderer->toHtml($validated['markdown_body']),
        ]);

        $article->tags()->sync($validated['tag_ids'] ?? []);
        $article->load(['channel', 'author', 'tags']);

        if ($this->isLiveArticle($article)) {
            $articleSubscriptionNotifier->send($article);
        }

        $staticPageBuilder->rebuildArticle($article->fresh(['channel', 'tags']));

        return to_route('admin.articles.index')->with('status', '文章已发布。');
    }

    public function edit(Article $article): View
    {
        return view('admin.articles.form', [
            'article' => $article->loadMissing('tags'),
            'channels' => Channel::query()->assignableArticleChannels()->ordered()->get(),
            'tags' => Tag::query()->ordered()->get(),
            'selectedTagIds' => $article->tags()->pluck('tags.id')->all(),
            'formAction' => route('admin.articles.update', $article),
            'formMethod' => 'PUT',
        ]);
    }

    public function update(
        Request $request,
        Article $article,
        MarkdownRenderer $markdownRenderer,
        ArticleSubscriptionNotifier $articleSubscriptionNotifier,
        StaticPageBuilder $staticPageBuilder,
    ): RedirectResponse {
        $before = $staticPageBuilder->captureArticleState($article->loadMissing('channel'));
        $wasLive = $this->isLiveArticle($article);
        $validated = $this->validateArticle($request, $article);

        $article->update([
            ...$this->articleAttributes($validated),
            'excerpt' => $validated['excerpt'] ?: $markdownRenderer->excerpt($validated['markdown_body']),
            'html_body' => $markdownRenderer->toHtml($validated['markdown_body']),
        ]);
        $article->tags()->sync($validated['tag_ids'] ?? []);
        $article->load(['channel', 'author', 'tags']);

        if (! $wasLive && $this->isLiveArticle($article->fresh())) {
            $articleSubscriptionNotifier->send($article->fresh(['channel', 'author', 'tags']));
        }

        $staticPageBuilder->rebuildArticle($article->fresh(['channel', 'tags']), $before);

        return to_route('admin.articles.index')->with('status', '文章已更新。');
    }

    public function togglePin(Article $article, StaticPageBuilder $staticPageBuilder): RedirectResponse
    {
        $before = $staticPageBuilder->captureArticleState($article->loadMissing('channel'));

        $article->update([
            'is_pinned' => ! $article->is_pinned,
        ]);

        $staticPageBuilder->rebuildArticle($article->fresh(['channel']), $before);

        return to_route('admin.articles.index')->with('status', $article->fresh()->is_pinned ? '文章已置顶。' : '文章已取消置顶。');
    }

    public function toggleFeature(Article $article, StaticPageBuilder $staticPageBuilder): RedirectResponse
    {
        $before = $staticPageBuilder->captureArticleState($article->loadMissing('channel'));

        $article->update([
            'is_featured' => ! $article->is_featured,
        ]);

        $staticPageBuilder->rebuildArticle($article->fresh(['channel']), $before);

        return to_route('admin.articles.index')->with('status', $article->fresh()->is_featured ? '文章已设为精华。' : '文章已取消精华。');
    }

    public function destroy(Article $article, StaticPageBuilder $staticPageBuilder): RedirectResponse
    {
        $before = $staticPageBuilder->captureArticleState($article->loadMissing('channel'));

        $article->delete();

        $staticPageBuilder->rebuildDeletedArticle($before);

        return to_route('admin.articles.index')->with('status', '文章已删除。');
    }

    public function bulkDestroy(Request $request, StaticPageBuilder $staticPageBuilder): RedirectResponse
    {
        $selectedArticleIds = collect($request->input('selected_article_ids', []))
            ->map(fn (mixed $value): int => (int) $value)
            ->filter(fn (int $value): bool => $value > 0)
            ->unique()
            ->values();

        if ($selectedArticleIds->isEmpty()) {
            return to_route('admin.articles.index')->with('status', '请先选择要删除的文章。');
        }

        $articles = Article::query()
            ->with('channel')
            ->whereIn('id', $selectedArticleIds->all())
            ->get();

        if ($articles->isEmpty()) {
            return to_route('admin.articles.index')->with('status', '未删除任何文章：所选文章不存在或已删除。');
        }

        $beforeStates = $articles
            ->map(fn (Article $article): array => $staticPageBuilder->captureArticleState($article))
            ->all();

        DB::transaction(function () use ($articles): void {
            Article::query()
                ->whereIn('id', $articles->modelKeys())
                ->delete();
        });

        $staticPageBuilder->rebuildDeletedArticles($beforeStates);

        return to_route('admin.articles.index')->with('status', sprintf('已删除 %d 篇文章。', $articles->count()));
    }

    private function validateArticle(Request $request, ?Article $article = null): array
    {
        $validated = $request->validate([
            'channel_id' => [
                'required',
                'exists:channels,id',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    $channel = Channel::query()->find($value);

                    if ($channel instanceof Channel && ! $channel->canOwnArticlesDirectly()) {
                        $fail('精华频道只负责聚合展示，不能作为文章主频道。');
                    }
                },
            ],
            'title' => ['required', 'string', 'max:120'],
            'slug' => ['nullable', 'string', 'max:140', Rule::unique('articles', 'slug')->ignore($article?->id)],
            'excerpt' => ['nullable', 'string', 'max:200'],
            'markdown_body' => ['required', 'string'],
            'cover_gradient' => ['required', 'string', 'max:128'],
            'published_at' => ['nullable', 'date'],
            'is_published' => ['nullable', 'boolean'],
            'is_pinned' => ['nullable', 'boolean'],
            'is_featured' => ['nullable', 'boolean'],
            'tag_ids' => ['nullable', 'array'],
            'tag_ids.*' => ['integer', 'exists:tags,id'],
        ]);

        $validated['slug'] = Str::slug($validated['slug'] ?: $validated['title']);
        $validated['is_published'] = (bool) ($validated['is_published'] ?? false);
        $validated['is_pinned'] = (bool) ($validated['is_pinned'] ?? false);
        $validated['is_featured'] = (bool) ($validated['is_featured'] ?? false);
        $validated['published_at'] = $validated['published_at'] ?? now();

        return $validated;
    }

    private function articleAttributes(array $validated): array
    {
        return collect($validated)
            ->except('tag_ids')
            ->all();
    }

    private function isLiveArticle(Article $article): bool
    {
        return $article->is_published
            && $article->published_at !== null
            && ! $article->published_at->isFuture();
    }
}
