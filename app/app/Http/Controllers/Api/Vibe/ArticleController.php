<?php

namespace App\Http\Controllers\Api\Vibe;

use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Models\Channel;
use App\Models\Tag;
use App\Support\ArticleSubscriptionNotifier;
use App\Support\DevtoolsIdempotencyManager;
use App\Support\MarkdownRenderer;
use App\Support\StaticPageBuilder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Throwable;
use Illuminate\Validation\Rule;

class ArticleController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Article::query()->with(['channel:id,public_id,name,slug', 'author:id,name', 'tags:id,public_id,name,slug']);

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

        if ($request->filled('tag_id')) {
            $query->whereHas('tags', fn ($tagQuery) => $tagQuery->whereKey($request->integer('tag_id')));
        }

        $articles = $query->latest()->paginate(20);

        return response()->json($articles);
    }

    public function show(string $article): JsonResponse
    {
        $article = $this->resolveArticle($article);

        return response()->json(['article' => $article->load(['channel:id,public_id,name,slug', 'author:id,name', 'tags:id,public_id,name,slug'])]);
    }

    public function store(
        Request $request,
        MarkdownRenderer $markdownRenderer,
        ArticleSubscriptionNotifier $articleSubscriptionNotifier,
        StaticPageBuilder $staticPageBuilder,
        DevtoolsIdempotencyManager $devtoolsIdempotencyManager,
    ): JsonResponse {
        $apiKey = $request->attributes->get('devtools_api_key');
        $idempotencyKey = trim((string) $request->header('X-Idempotency-Key', ''));
        $requestFingerprint = $idempotencyKey !== ''
            ? $devtoolsIdempotencyManager->fingerprint($request->all())
            : null;
        $idempotencyRecord = null;

        if ($idempotencyKey !== '' && $requestFingerprint !== null) {
            $idempotencyRecord = $devtoolsIdempotencyManager->reserve($apiKey, 'articles.create', $idempotencyKey, $requestFingerprint);

            if (! $idempotencyRecord->wasRecentlyCreated) {
                if ($idempotencyRecord->request_fingerprint !== $requestFingerprint) {
                    return $devtoolsIdempotencyManager->conflictResponse($idempotencyKey);
                }

                $completedRecord = $idempotencyRecord->isCompleted()
                    ? $idempotencyRecord
                    : $devtoolsIdempotencyManager->waitForCompletion($idempotencyRecord);

                if ($completedRecord !== null) {
                    return $devtoolsIdempotencyManager->replayResponse($completedRecord, $idempotencyKey);
                }

                return $devtoolsIdempotencyManager->inProgressResponse($idempotencyKey);
            }
        }

        $validated = $this->validateArticle($request);

        try {
            [$article, $responseBody] = DB::transaction(function () use (
                $validated,
                $apiKey,
                $markdownRenderer,
                $devtoolsIdempotencyManager,
                $idempotencyRecord,
                $requestFingerprint,
            ): array {
                $article = Article::query()->create([
                    ...$this->articleAttributes($validated),
                    'author_id' => $apiKey->user_id,
                    'excerpt' => $validated['excerpt'] ?: $markdownRenderer->excerpt($validated['markdown_body']),
                    'html_body' => $markdownRenderer->toHtml($validated['markdown_body']),
                ]);
                $article->tags()->sync($validated['tag_ids'] ?? []);
                $article = $article->fresh(['channel:id,public_id,name,slug', 'author:id,name', 'tags:id,public_id,name,slug']);

                $responseBody = ['article' => $article?->toArray() ?? []];

                if ($idempotencyRecord !== null && $requestFingerprint !== null) {
                    $devtoolsIdempotencyManager->complete(
                        $idempotencyRecord,
                        $requestFingerprint,
                        201,
                        $responseBody,
                        'article',
                        $article?->id,
                    );
                }

                return [$article, $responseBody];
            });
        } catch (Throwable $exception) {
            if ($idempotencyRecord !== null && $idempotencyRecord->wasRecentlyCreated) {
                $devtoolsIdempotencyManager->abandon($idempotencyRecord);
            }

            throw $exception;
        }

        if ($article instanceof Article && $this->isLiveArticle($article)) {
            $articleSubscriptionNotifier->send($article);
        }

        if ($article instanceof Article) {
            $staticPageBuilder->rebuildArticle($article->fresh(['channel', 'tags']));
        }

        $response = response()->json($responseBody, 201);

        if ($idempotencyKey !== '') {
            $response->headers->set('X-Idempotency-Key', $idempotencyKey);
            $response->headers->set('X-Idempotency-Replayed', 'false');
        }

        return $response;
    }

    public function update(
        Request $request,
        string $article,
        MarkdownRenderer $markdownRenderer,
        ArticleSubscriptionNotifier $articleSubscriptionNotifier,
        StaticPageBuilder $staticPageBuilder,
    ): JsonResponse {
        $article = $this->resolveArticle($article);
        $before = $staticPageBuilder->captureArticleState($article->loadMissing('channel'));
        $wasLive = $this->isLiveArticle($article);
        $validated = $this->validateArticle($request, $article);

        $article = DB::transaction(function () use ($article, $validated, $markdownRenderer): Article {
            $payload = $validated;
            unset($payload['tag_ids']);

            if (array_key_exists('markdown_body', $validated)) {
                $payload['html_body'] = $markdownRenderer->toHtml($validated['markdown_body']);

                if (! array_key_exists('excerpt', $validated) || $validated['excerpt'] === '') {
                    $payload['excerpt'] = $markdownRenderer->excerpt($validated['markdown_body']);
                }
            }

            $article->update($payload);

            if (array_key_exists('tag_ids', $validated)) {
                $article->tags()->sync($validated['tag_ids'] ?? []);
            }

            return $article->fresh(['channel:id,public_id,name,slug', 'author:id,name', 'tags:id,public_id,name,slug']);
        });

        if (! $wasLive && $this->isLiveArticle($article)) {
            $articleSubscriptionNotifier->send($article->fresh(['channel', 'author', 'tags']));
        }

        $staticPageBuilder->rebuildArticle($article->fresh(['channel', 'tags']), $before);

        return response()->json(['article' => $article]);
    }

    public function destroy(string $article, StaticPageBuilder $staticPageBuilder): JsonResponse
    {
        $article = $this->resolveArticle($article);
        $before = $staticPageBuilder->captureArticleState($article->loadMissing('channel'));

        $article->delete();

        $staticPageBuilder->rebuildDeletedArticle($before);

        return response()->json(['ok' => true]);
    }

    private function validateArticle(Request $request, ?Article $article = null): array
    {
        $isUpdate = $article !== null;

        $validated = $request->validate([
            'channel_id' => [
                $isUpdate ? 'sometimes' : 'required',
                'exists:channels,id',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    $channel = Channel::query()->find($value);

                    if ($channel instanceof Channel && ! $channel->canOwnArticlesDirectly()) {
                        $fail('精华频道只负责聚合展示，不能作为文章主频道。');
                    }
                },
            ],
            'title' => [$isUpdate ? 'sometimes' : 'required', 'string', 'max:120'],
            'slug' => [$isUpdate ? 'sometimes' : 'nullable', 'nullable', 'string', 'max:140', Rule::unique('articles', 'slug')->ignore($article?->id)],
            'excerpt' => [$isUpdate ? 'sometimes' : 'nullable', 'nullable', 'string', 'max:200'],
            'markdown_body' => [$isUpdate ? 'sometimes' : 'required', 'string'],
            'cover_gradient' => [$isUpdate ? 'sometimes' : 'nullable', 'nullable', 'string', 'max:128'],
            'published_at' => [$isUpdate ? 'sometimes' : 'nullable', 'nullable', 'date'],
            'is_published' => [$isUpdate ? 'sometimes' : 'nullable', 'nullable', 'boolean'],
            'is_pinned' => [$isUpdate ? 'sometimes' : 'nullable', 'nullable', 'boolean'],
            'is_featured' => [$isUpdate ? 'sometimes' : 'nullable', 'nullable', 'boolean'],
            'tag_ids' => [$isUpdate ? 'sometimes' : 'nullable', 'nullable', 'array'],
            'tag_ids.*' => ['integer', 'exists:tags,id'],
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

    private function articleAttributes(array $validated): array
    {
        return collect($validated)
            ->except('tag_ids')
            ->all();
    }

    private function resolveArticle(string $identifier): Article
    {
        return Article::query()
            ->wherePublicReference($identifier)
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

        return 'article-'.Str::lower(Str::random(8));
    }

    private function isLiveArticle(Article $article): bool
    {
        return $article->is_published
            && $article->published_at !== null
            && ! $article->published_at->isFuture();
    }
}
