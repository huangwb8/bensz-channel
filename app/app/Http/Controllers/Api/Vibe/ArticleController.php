<?php

namespace App\Http\Controllers\Api\Vibe;

use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Support\ArticleSubscriptionNotifier;
use App\Support\MarkdownRenderer;
use App\Support\StaticPageBuilder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class ArticleController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Article::query()->with(['channel:id,name,slug', 'author:id,name']);

        if ($request->filled('channel_id')) {
            $query->where('channel_id', $request->integer('channel_id'));
        }

        if ($request->has('published')) {
            $query->where('is_published', $request->boolean('published'));
        }

        if ($request->has('pinned')) {
            $query->where('is_pinned', $request->boolean('pinned'));
        }

        if ($request->has('featured')) {
            $query->where('is_featured', $request->boolean('featured'));
        }

        $articles = $query->latest()->paginate(20);

        return response()->json($articles);
    }

    public function show(string $article): JsonResponse
    {
        $article = $this->resolveArticle($article);

        return response()->json(['article' => $article->load(['channel:id,name,slug', 'author:id,name'])]);
    }

    public function store(
        Request $request,
        MarkdownRenderer $markdownRenderer,
        ArticleSubscriptionNotifier $articleSubscriptionNotifier,
        StaticPageBuilder $staticPageBuilder,
    ): JsonResponse {
        $validated = $this->validateArticle($request);

        $apiKey = $request->attributes->get('devtools_api_key');

        $article = Article::query()->create([
            ...$validated,
            'author_id' => $apiKey->user_id,
            'excerpt' => $validated['excerpt'] ?: $markdownRenderer->excerpt($validated['markdown_body']),
            'html_body' => $markdownRenderer->toHtml($validated['markdown_body']),
        ]);

        if ($this->isLiveArticle($article)) {
            $articleSubscriptionNotifier->send($article);
        }

        $staticPageBuilder->buildAll();

        return response()->json(['article' => $article->load(['channel:id,name,slug', 'author:id,name'])], 201);
    }

    public function update(
        Request $request,
        string $article,
        MarkdownRenderer $markdownRenderer,
        ArticleSubscriptionNotifier $articleSubscriptionNotifier,
        StaticPageBuilder $staticPageBuilder,
    ): JsonResponse {
        $article = $this->resolveArticle($article);
        $wasLive = $this->isLiveArticle($article);
        $validated = $this->validateArticle($request, $article);

        $payload = $validated;

        if (array_key_exists('markdown_body', $validated)) {
            $payload['html_body'] = $markdownRenderer->toHtml($validated['markdown_body']);

            if (! array_key_exists('excerpt', $validated) || $validated['excerpt'] === '') {
                $payload['excerpt'] = $markdownRenderer->excerpt($validated['markdown_body']);
            }
        }

        $article->update($payload);

        if (! $wasLive && $this->isLiveArticle($article->fresh())) {
            $articleSubscriptionNotifier->send($article->fresh(['channel', 'author']));
        }

        $staticPageBuilder->buildAll();

        return response()->json(['article' => $article->fresh(['channel:id,name,slug', 'author:id,name'])]);
    }

    public function destroy(string $article, StaticPageBuilder $staticPageBuilder): JsonResponse
    {
        $article = $this->resolveArticle($article);

        $article->delete();

        $staticPageBuilder->buildAll();

        return response()->json(['ok' => true]);
    }

    private function validateArticle(Request $request, ?Article $article = null): array
    {
        $isUpdate = $article !== null;

        $validated = $request->validate([
            'channel_id' => [$isUpdate ? 'sometimes' : 'required', 'exists:channels,id'],
            'title' => [$isUpdate ? 'sometimes' : 'required', 'string', 'max:120'],
            'slug' => [$isUpdate ? 'sometimes' : 'nullable', 'nullable', 'string', 'max:140', Rule::unique('articles', 'slug')->ignore($article?->id)],
            'excerpt' => [$isUpdate ? 'sometimes' : 'nullable', 'nullable', 'string', 'max:200'],
            'markdown_body' => [$isUpdate ? 'sometimes' : 'required', 'string'],
            'cover_gradient' => [$isUpdate ? 'sometimes' : 'nullable', 'nullable', 'string', 'max:128'],
            'published_at' => [$isUpdate ? 'sometimes' : 'nullable', 'nullable', 'date'],
            'is_published' => [$isUpdate ? 'sometimes' : 'nullable', 'nullable', 'boolean'],
            'is_pinned' => [$isUpdate ? 'sometimes' : 'nullable', 'nullable', 'boolean'],
            'is_featured' => [$isUpdate ? 'sometimes' : 'nullable', 'nullable', 'boolean'],
        ]);

        if (array_key_exists('slug', $validated) || array_key_exists('title', $validated) || ! $isUpdate) {
            $validated['slug'] = $this->makeArticleSlug(
                $validated['slug'] ?? $validated['title'] ?? null,
                $article,
            );
        }

        foreach (['is_published', 'is_pinned', 'is_featured'] as $field) {
            if (array_key_exists($field, $validated)) {
                $validated[$field] = (bool) $validated[$field];
            } elseif (! $isUpdate) {
                $validated[$field] = false;
            }
        }

        if (! array_key_exists('published_at', $validated)) {
            if (! $isUpdate) {
                $validated['published_at'] = ($validated['is_published'] ?? false) ? now() : null;
            } elseif (($validated['is_published'] ?? false) && $article?->published_at === null) {
                $validated['published_at'] = now();
            }
        }

        if (! $isUpdate) {
            $validated['cover_gradient'] ??= 'from-violet-500 via-fuchsia-500 to-cyan-500';
            $validated['excerpt'] ??= '';
        }

        return $validated;
    }

    private function resolveArticle(string $identifier): Article
    {
        return Article::query()
            ->where(function ($query) use ($identifier): void {
                $query->where('slug', $identifier);

                if (ctype_digit($identifier)) {
                    $query->orWhere('id', (int) $identifier);
                }
            })
            ->firstOrFail();
    }

    private function makeArticleSlug(?string $source, ?Article $article = null): string
    {
        $slug = Str::slug((string) $source);

        if ($slug !== '') {
            return $slug;
        }

        if ($article instanceof Article && $article->slug !== '') {
            return $article->slug;
        }

        return 'article-' . Str::lower(Str::random(8));
    }

    private function isLiveArticle(Article $article): bool
    {
        return $article->is_published
            && $article->published_at !== null
            && ! $article->published_at->isFuture();
    }
}
