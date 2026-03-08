<?php

namespace App\Http\Controllers\Api\Vibe;

use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Models\Channel;
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

        if ($request->filled('published')) {
            $query->where('is_published', (bool) $request->input('published'));
        }

        $articles = $query->latest()->paginate(20);

        return response()->json($articles);
    }

    public function show(Article $article): JsonResponse
    {
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
        Article $article,
        MarkdownRenderer $markdownRenderer,
        ArticleSubscriptionNotifier $articleSubscriptionNotifier,
        StaticPageBuilder $staticPageBuilder,
    ): JsonResponse {
        $wasLive = $this->isLiveArticle($article);
        $validated = $this->validateArticle($request, $article);

        $article->update([
            ...$validated,
            'excerpt' => $validated['excerpt'] ?: $markdownRenderer->excerpt($validated['markdown_body']),
            'html_body' => $markdownRenderer->toHtml($validated['markdown_body']),
        ]);

        if (! $wasLive && $this->isLiveArticle($article->fresh())) {
            $articleSubscriptionNotifier->send($article->fresh(['channel', 'author']));
        }

        $staticPageBuilder->buildAll();

        return response()->json(['article' => $article->fresh(['channel:id,name,slug', 'author:id,name'])]);
    }

    public function destroy(Article $article, StaticPageBuilder $staticPageBuilder): JsonResponse
    {
        $article->delete();

        $staticPageBuilder->buildAll();

        return response()->json(['ok' => true]);
    }

    private function validateArticle(Request $request, ?Article $article = null): array
    {
        $validated = $request->validate([
            'channel_id' => ['required', 'exists:channels,id'],
            'title' => ['required', 'string', 'max:120'],
            'slug' => ['nullable', 'string', 'max:140', Rule::unique('articles', 'slug')->ignore($article?->id)],
            'excerpt' => ['nullable', 'string', 'max:200'],
            'markdown_body' => ['required', 'string'],
            'cover_gradient' => ['nullable', 'string', 'max:128'],
            'published_at' => ['nullable', 'date'],
            'is_published' => ['nullable', 'boolean'],
        ]);

        $validated['slug'] = Str::slug($validated['slug'] ?: $validated['title']);
        $validated['is_published'] = (bool) ($validated['is_published'] ?? false);
        $validated['published_at'] = $validated['published_at'] ?? now();
        $validated['cover_gradient'] ??= 'from-violet-500 via-fuchsia-500 to-cyan-500';
        $validated['excerpt'] ??= '';

        return $validated;
    }

    private function isLiveArticle(Article $article): bool
    {
        return $article->is_published
            && $article->published_at !== null
            && ! $article->published_at->isFuture();
    }
}
