<?php

namespace App\Support;

use App\Jobs\SyncCdnFilesJob;
use App\Jobs\ProcessStaticSiteBuildJob;
use App\Models\Article;
use App\Models\Channel;
use App\Support\Cdn\CdnWorkLogManager;
use App\Support\CanonicalUrlManager;
use App\Support\Seo\SiteDiscoveryService;
use Carbon\CarbonImmutable;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Queue;
use RuntimeException;
use Throwable;

class StaticPageBuilder
{
    private const PREVIEW_ARTICLE_LIMIT = 6;

    public function __construct(
        private readonly CommunityViewData $viewData,
        private readonly Filesystem $filesystem,
        private readonly \App\Support\Cdn\CdnSyncService $cdnSyncService,
        private readonly CdnWorkLogManager $cdnWorkLogManager,
        private readonly SiteDiscoveryService $siteDiscoveryService,
        private readonly CanonicalUrlManager $canonicalUrlManager,
    ) {}

    public function buildAll(?callable $progress = null): array
    {
        return $this->buildPayload(['full' => true], $progress);
    }

    public function buildPayload(array $payload, ?callable $progress = null): array
    {
        $startedAt = CarbonImmutable::now();
        $this->canonicalUrlManager->apply();
        $this->markBuildPending();

        if (! config('community.static.enabled')) {
            $this->cdnWorkLogManager->record([
                'trigger' => 'build:skipped',
                'status' => 'skipped',
                'mode' => (string) config('cdn.mode', 'origin'),
                'provider' => config('cdn.storage.provider'),
                'message' => '静态构建已跳过：静态站点功能未启用。',
                'details' => $this->cdnWorkLogManager->buildDetails([
                    '工作类型：静态页面构建',
                    '构建方式：跳过',
                    '原因：community.static.enabled=false',
                ]),
                'started_at' => $startedAt,
                'finished_at' => CarbonImmutable::now(),
            ]);

            $this->clearBuildPendingIfSafe();

            return $this->makeSummary();
        }

        $normalized = $this->normalizePayload($payload);

        try {
            $summary = $normalized['full']
                ? $this->buildFull($progress)
                : $this->buildIncremental($normalized, $progress);

            $this->cdnWorkLogManager->record([
                'trigger' => $normalized['full'] ? 'build:full' : 'build:incremental',
                'status' => 'success',
                'mode' => (string) config('cdn.mode', 'origin'),
                'provider' => config('cdn.storage.provider'),
                'total_count' => (int) (($summary['built'] ?? 0) + ($summary['skipped'] ?? 0) + ($summary['deleted'] ?? 0)),
                'uploaded_count' => (int) ($summary['built'] ?? 0),
                'skipped_count' => (int) ($summary['skipped'] ?? 0),
                'deleted_count' => (int) ($summary['deleted'] ?? 0),
                'duration_ms' => max(0, CarbonImmutable::now()->diffInMilliseconds($startedAt)),
                'message' => $this->summaryMessage($summary),
                'details' => $this->cdnWorkLogManager->buildDetails([
                    '工作类型：静态页面构建',
                    '构建方式：'.($normalized['full'] ? '全量构建' : '增量构建'),
                    '构建结果：'.$this->summaryMessage($summary),
                    '输出目录：'.$this->outputRoot(),
                    '请求载荷：'.json_encode($normalized, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                ]),
                'context' => [
                    'build_payload' => $normalized,
                    'summary' => $summary,
                ],
                'started_at' => $startedAt,
                'finished_at' => CarbonImmutable::now(),
            ]);

            $this->clearBuildPendingIfSafe();

            return $summary;
        } catch (Throwable $exception) {
            report($exception);

            $this->cdnWorkLogManager->record([
                'trigger' => $normalized['full'] ? 'build:full' : 'build:incremental',
                'status' => 'failed',
                'mode' => (string) config('cdn.mode', 'origin'),
                'provider' => config('cdn.storage.provider'),
                'duration_ms' => max(0, CarbonImmutable::now()->diffInMilliseconds($startedAt)),
                'message' => '静态构建失败：'.$exception->getMessage(),
                'details' => $this->cdnWorkLogManager->buildDetails([
                    '工作类型：静态页面构建',
                    '构建方式：'.($normalized['full'] ? '全量构建' : '增量构建'),
                    $this->cdnWorkLogManager->describeException($exception),
                ]),
                'context' => [
                    'build_payload' => $normalized,
                    'exception' => [
                        'class' => $exception::class,
                        'message' => $exception->getMessage(),
                    ],
                ],
                'started_at' => $startedAt,
                'finished_at' => CarbonImmutable::now(),
            ]);

            throw $exception;
        }
    }

    public function rebuildAll(): void
    {
        $this->dispatchOrBuild(['full' => true]);
    }

    public function queuePayload(array $payload): void
    {
        if (! config('community.static.enabled')) {
            return;
        }

        $normalized = $this->normalizePayload($payload);
        $this->markBuildPending();
        ProcessStaticSiteBuildJob::dispatch($normalized)->onQueue($this->queueName());

        $this->cdnWorkLogManager->record([
            'trigger' => 'build:queued',
            'status' => 'queued',
            'mode' => (string) config('cdn.mode', 'origin'),
            'provider' => config('cdn.storage.provider'),
            'message' => '静态构建任务已加入队列。',
            'details' => $this->cdnWorkLogManager->buildDetails([
                '工作类型：静态页面构建',
                '构建方式：排队执行',
                '队列名称：'.$this->queueName(),
                '请求载荷：'.json_encode($normalized, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            ]),
            'context' => [
                'build_payload' => $normalized,
                'queue' => $this->queueName(),
            ],
        ]);
    }

    public function captureArticleState(?Article $article): array
    {
        if (! $article instanceof Article) {
            return [];
        }

        $article->loadMissing('channel');

        return [
            'id' => $article->id,
            'channel_id' => $article->channel_id,
            'channel_public_id' => $article->channel?->public_id,
            'public_id' => $article->public_id,
            'was_live' => $this->isBuildableArticle($article),
            'is_featured' => (bool) $article->is_featured,
        ];
    }

    public function rebuildArticle(Article $article, array $before = []): void
    {
        $this->dispatchOrBuild($this->payloadForArticle($article, $before));
    }

    public function rebuildDeletedArticle(array $before): void
    {
        $this->dispatchOrBuild($this->payloadForDeletedArticle($before));
    }

    /**
     * @param  array<int, array<string, mixed>>  $beforeStates
     */
    public function rebuildDeletedArticles(array $beforeStates): void
    {
        $this->dispatchOrBuild($this->payloadForDeletedArticles($beforeStates));
    }

    public function rebuildAfterComment(Article $article): void
    {
        $this->dispatchOrBuild($this->payloadForCommentedArticle($article));
    }

    public function payloadForArticle(Article $article, array $before = []): array
    {
        $article->loadMissing('channel');

        $channelIds = array_filter([
            $article->channel_id,
            $before['channel_id'] ?? null,
        ]);

        if (($before['is_featured'] ?? false) || $article->is_featured) {
            $channelIds[] = $this->featuredChannelId();
        }

        $deletePaths = [];
        $beforeWasLive = (bool) ($before['was_live'] ?? false);
        $currentIsLive = $this->isBuildableArticle($article);

        if ($beforeWasLive) {
            $oldChannelPublicId = $before['channel_public_id'] ?? null;
            $oldPublicId = $before['public_id'] ?? null;

            if (
                ! $currentIsLive
                || $oldChannelPublicId !== $article->channel?->public_id
                || $oldPublicId !== $article->public_id
            ) {
                if (filled($oldChannelPublicId) && filled($oldPublicId)) {
                    $deletePaths[] = $this->articlePathFromPublicIds((string) $oldChannelPublicId, (string) $oldPublicId);
                }
            }
        }

        return [
            'home' => true,
            'channel_ids' => array_values(array_unique(array_filter(array_map(fn ($value) => $value === null ? null : (int) $value, $channelIds)))),
            'article_ids' => $currentIsLive ? [$article->id] : [],
            'preview_channel_ids' => array_values(array_unique(array_filter(array_map(fn ($value) => $value === null ? null : (int) $value, [
                $article->channel_id,
                $before['channel_id'] ?? null,
            ])))),
            'delete_paths' => array_values(array_unique(array_filter($deletePaths))),
        ];
    }

    public function payloadForDeletedArticle(array $before): array
    {
        $channelIds = array_filter([
            $before['channel_id'] ?? null,
            ($before['is_featured'] ?? false) ? $this->featuredChannelId() : null,
        ]);

        $deletePaths = [];

        if (($before['was_live'] ?? false) && filled($before['channel_public_id'] ?? null) && filled($before['public_id'] ?? null)) {
            $deletePaths[] = $this->articlePathFromPublicIds((string) $before['channel_public_id'], (string) $before['public_id']);
        }

        return [
            'home' => true,
            'channel_ids' => array_values(array_unique(array_filter(array_map(fn ($value) => $value === null ? null : (int) $value, $channelIds)))),
            'preview_channel_ids' => array_values(array_unique(array_filter(array_map(fn ($value) => $value === null ? null : (int) $value, [
                $before['channel_id'] ?? null,
            ])))),
            'delete_paths' => array_values(array_unique(array_filter($deletePaths))),
        ];
    }

    /**
     * @param  array<int, array<string, mixed>>  $beforeStates
     */
    public function payloadForDeletedArticles(array $beforeStates): array
    {
        $channelIds = [];
        $previewChannelIds = [];
        $deletePaths = [];

        foreach ($beforeStates as $before) {
            if (! is_array($before) || $before === []) {
                continue;
            }

            $payload = $this->payloadForDeletedArticle($before);
            $channelIds = [...$channelIds, ...($payload['channel_ids'] ?? [])];
            $previewChannelIds = [...$previewChannelIds, ...($payload['preview_channel_ids'] ?? [])];
            $deletePaths = [...$deletePaths, ...($payload['delete_paths'] ?? [])];
        }

        return [
            'home' => $beforeStates !== [],
            'channel_ids' => array_values(array_unique(array_filter(array_map(fn ($value) => $value === null ? null : (int) $value, $channelIds)))),
            'preview_channel_ids' => array_values(array_unique(array_filter(array_map(fn ($value) => $value === null ? null : (int) $value, $previewChannelIds)))),
            'delete_paths' => array_values(array_unique(array_filter($deletePaths))),
        ];
    }

    public function payloadForCommentedArticle(Article $article): array
    {
        $article->loadMissing('channel');

        $channelIds = [$article->channel_id];

        if ($article->is_featured) {
            $channelIds[] = $this->featuredChannelId();
        }

        return [
            'home' => true,
            'channel_ids' => array_values(array_unique(array_filter(array_map(fn ($value) => $value === null ? null : (int) $value, $channelIds)))),
            'article_ids' => $this->isBuildableArticle($article) ? [$article->id] : [],
            'preview_channel_ids' => [$article->channel_id],
        ];
    }

    public function payloadForChannel(Channel $channel): array
    {
        return [
            'channel_ids' => [$channel->id],
            'preview_channel_ids' => [$channel->id],
        ];
    }

    private function dispatchOrBuild(array $payload): void
    {
        if (! config('community.static.enabled')) {
            return;
        }

        $normalized = $this->normalizePayload($payload);

        if (! $this->shouldQueue()) {
            $this->buildPayload($normalized);

            return;
        }

        $this->queuePayload($normalized);
    }

    private function shouldQueue(): bool
    {
        return (bool) config('community.static.async', true);
    }

    private function queueName(): string
    {
        return (string) config('community.static.queue', 'static-builds');
    }

    private function markBuildPending(): void
    {
        $path = $this->pendingMarkerPath();
        $this->filesystem->ensureDirectoryExists(dirname($path));
        $this->filesystem->put($path, CarbonImmutable::now()->toIso8601String());
    }

    private function clearBuildPendingIfSafe(): void
    {
        if ($this->hasQueuedBuilds()) {
            return;
        }

        $path = $this->pendingMarkerPath();

        if ($this->filesystem->exists($path)) {
            $this->filesystem->delete($path);
        }
    }

    private function hasQueuedBuilds(): bool
    {
        try {
            return Queue::size($this->queueName()) > 0;
        } catch (Throwable) {
            // 不确定队列是否还有积压时，保守地继续回源动态页，避免访客读到旧静态内容。
            return true;
        }
    }

    private function buildFull(?callable $progress = null): array
    {
        $summary = $this->makeSummary();
        $outputRoot = $this->outputRoot();
        $manifest = $this->loadManifest();
        $hasManifest = $manifest !== [];
        $touched = [];

        if ($this->filesystem->exists($outputRoot) && ! $hasManifest) {
            $this->filesystem->cleanDirectory($outputRoot);
        }

        $this->filesystem->ensureDirectoryExists($outputRoot);

        $this->notify($progress, '正在构建首页...');
        $this->storeRenderedPage(
            'index.html',
            view('home', [...$this->viewData->home(), 'staticPage' => true])->render(),
            $manifest,
            $summary,
            $touched,
        );
        $this->storeTextFile('robots.txt', $this->siteDiscoveryService->robotsTxt(), $manifest, $summary, $touched);
        $this->storeTextFile('sitemap.xml', $this->siteDiscoveryService->sitemapXml(), $manifest, $summary, $touched);

        $channelTotal = Channel::query()->count();
        $this->notify($progress, "正在构建频道页面（共 {$channelTotal} 个）...");

        Channel::query()->chunkById($this->channelChunkSize(), function ($channels) use (&$manifest, &$summary, &$touched): void {
            foreach ($channels as $channel) {
                $this->storeRenderedPage(
                    $this->channelPath($channel),
                    view('channels.show', [...$this->viewData->channel($channel), 'staticPage' => true])->render(),
                    $manifest,
                    $summary,
                    $touched,
                );
            }
        });

        $articleTotal = Article::query()->published()->count();
        $this->notify($progress, "正在构建文章页面（共 {$articleTotal} 篇）...");

        Article::query()
            ->published()
            ->with('channel')
            ->chunkById($this->articleChunkSize(), function ($articles) use (&$manifest, &$summary, &$touched): void {
                foreach ($articles as $article) {
                    $this->storeRenderedPage(
                        $this->articlePath($article),
                        view('articles.show', [...$this->viewData->article($article), 'staticPage' => true])->render(),
                        $manifest,
                        $summary,
                        $touched,
                    );
                }
            });

        foreach (array_diff(array_keys($manifest), array_keys($touched)) as $stalePath) {
            $this->deletePage($stalePath, $manifest, $summary);
        }

        $this->saveManifest($manifest);
        $syncQueued = $this->dispatchCdnSyncIfNeeded();
        $summary['cdn_sync_queued'] = $syncQueued;
        $this->notify($progress, $this->summaryMessage($summary));

        return $summary;
    }

    private function buildIncremental(array $payload, ?callable $progress = null): array
    {
        $summary = $this->makeSummary();
        $manifest = $this->loadManifest();
        $touched = [];

        if ($payload['home']) {
            $this->notify($progress, '正在增量构建首页...');
            $this->storeRenderedPage(
                'index.html',
                view('home', [...$this->viewData->home(), 'staticPage' => true])->render(),
                $manifest,
                $summary,
                $touched,
            );
        }

        $this->storeTextFile('robots.txt', $this->siteDiscoveryService->robotsTxt(), $manifest, $summary, $touched);
        $this->storeTextFile('sitemap.xml', $this->siteDiscoveryService->sitemapXml(), $manifest, $summary, $touched);

        if ($payload['channel_ids'] !== []) {
            $channels = Channel::query()->whereKey($payload['channel_ids'])->get()->keyBy('id');

            foreach ($payload['channel_ids'] as $channelId) {
                $channel = $channels->get($channelId);

                if (! $channel instanceof Channel) {
                    continue;
                }

                $this->storeRenderedPage(
                    $this->channelPath($channel),
                    view('channels.show', [...$this->viewData->channel($channel), 'staticPage' => true])->render(),
                    $manifest,
                    $summary,
                    $touched,
                );
            }
        }

        if ($payload['article_ids'] !== []) {
            $articles = Article::query()
                ->published()
                ->with('channel')
                ->whereKey($payload['article_ids'])
                ->get()
                ->keyBy('id');

            foreach ($payload['article_ids'] as $articleId) {
                $article = $articles->get($articleId);

                if (! $article instanceof Article) {
                    continue;
                }

                $this->storeRenderedPage(
                    $this->articlePath($article),
                    view('articles.show', [...$this->viewData->article($article), 'staticPage' => true])->render(),
                    $manifest,
                    $summary,
                    $touched,
                );
            }
        }

        foreach ($payload['preview_channel_ids'] as $channelId) {
            Article::query()
                ->published()
                ->with('channel')
                ->where('channel_id', $channelId)
                ->latestPublished()
                ->limit(self::PREVIEW_ARTICLE_LIMIT)
                ->get()
                ->each(function (Article $article) use (&$manifest, &$summary, &$touched): void {
                    $this->storeRenderedPage(
                        $this->articlePath($article),
                        view('articles.show', [...$this->viewData->article($article), 'staticPage' => true])->render(),
                        $manifest,
                        $summary,
                        $touched,
                    );
                });
        }

        foreach ($payload['delete_paths'] as $deletePath) {
            $this->deletePage($deletePath, $manifest, $summary);
        }

        $this->saveManifest($manifest);
        $syncQueued = $this->dispatchCdnSyncIfNeeded();
        $summary['cdn_sync_queued'] = $syncQueued;
        $this->notify($progress, $this->summaryMessage($summary));

        return $summary;
    }

    private function normalizePayload(array $payload): array
    {
        $normalized = [
            'full' => (bool) ($payload['full'] ?? false),
            'home' => (bool) ($payload['home'] ?? false),
            'channel_ids' => $this->normalizeIntegerList($payload['channel_ids'] ?? []),
            'article_ids' => $this->normalizeIntegerList($payload['article_ids'] ?? []),
            'preview_channel_ids' => $this->normalizeIntegerList($payload['preview_channel_ids'] ?? []),
            'delete_paths' => array_values(array_unique(array_filter(array_map(
                fn ($path) => is_string($path) ? ltrim(trim($path), '/') : null,
                is_array($payload['delete_paths'] ?? null) ? $payload['delete_paths'] : [],
            )))),
        ];

        if ($normalized['full']) {
            $normalized['home'] = true;
        }

        return $normalized;
    }

    private function dispatchCdnSyncIfNeeded(): bool
    {
        if (! $this->cdnSyncService->shouldSyncOnBuild()) {
            return false;
        }

        SyncCdnFilesJob::dispatch('build');

        return true;
    }

    private function normalizeIntegerList(mixed $values): array
    {
        if (! is_array($values)) {
            return [];
        }

        return array_values(array_unique(array_filter(array_map(function ($value): ?int {
            if ($value === null || $value === '') {
                return null;
            }

            return (int) $value;
        }, $values))));
    }

    private function storeRenderedPage(string $relativePath, string $html, array &$manifest, array &$summary, array &$touched): void
    {
        $relativePath = ltrim($relativePath, '/');
        $path = $this->absolutePath($relativePath);
        $minified = $this->minify($html);
        $hash = sha1($minified);

        $touched[$relativePath] = true;

        if (
            ($manifest[$relativePath] ?? null) === $hash
            && $this->filesystem->exists($path)
            && $this->filesystem->exists($path.'.gz')
        ) {
            $summary['skipped']++;

            return;
        }

        $this->filesystem->ensureDirectoryExists(dirname($path));

        $gzipped = gzencode($minified, $this->gzipLevel());

        if ($gzipped === false) {
            throw new RuntimeException('静态页面 Gzip 压缩失败。');
        }

        $this->filesystem->put($path, $minified);
        $this->filesystem->put($path.'.gz', $gzipped);
        $manifest[$relativePath] = $hash;
        $summary['built']++;
    }

    private function deletePage(string $relativePath, array &$manifest, array &$summary): void
    {
        $relativePath = ltrim($relativePath, '/');
        $path = $this->absolutePath($relativePath);

        if ($this->filesystem->exists($path)) {
            $this->filesystem->delete($path);
        }

        if ($this->filesystem->exists($path.'.gz')) {
            $this->filesystem->delete($path.'.gz');
        }

        unset($manifest[$relativePath]);
        $summary['deleted']++;

        $this->cleanupEmptyDirectories(dirname($path));
    }

    private function cleanupEmptyDirectories(string $directory): void
    {
        $outputRoot = rtrim($this->outputRoot(), DIRECTORY_SEPARATOR);
        $directory = rtrim($directory, DIRECTORY_SEPARATOR);

        while (
            $directory !== ''
            && str_starts_with($directory, $outputRoot)
            && $directory !== $outputRoot
        ) {
            if (! $this->filesystem->isDirectory($directory)) {
                break;
            }

            try {
                $files = $this->filesystem->files($directory);
                $directories = $this->filesystem->directories($directory);
            } catch (Throwable) {
                break;
            }

            if ($files !== [] || $directories !== []) {
                break;
            }

            $this->filesystem->deleteDirectory($directory);
            $directory = dirname($directory);
        }
    }

    private function minify(string $html): string
    {
        // 保护 <script> 和 <style> 标签内的内容
        $protected = [];
        $html = preg_replace_callback(
            '/<(script|style)[^>]*>.*?<\/\1>/is',
            function ($matches) use (&$protected) {
                $placeholder = '___PROTECTED_' . count($protected) . '___';
                $protected[$placeholder] = $matches[0];
                return $placeholder;
            },
            $html
        );

        // 移除标签之间的多余空白（但保留单个空格，避免破坏内联元素）
        $html = preg_replace('/>\\s+</', '> <', $html);

        // 移除行首行尾空白
        $html = preg_replace('/^\\s+|\\s+$/m', '', $html);

        // 将多个空白字符压缩为单个空格
        $html = preg_replace('/\\s{2,}/', ' ', $html);

        // 恢复被保护的内容
        foreach ($protected as $placeholder => $content) {
            $html = str_replace($placeholder, $content, $html);
        }

        return trim($html);
    }

    private function storeTextFile(string $relativePath, string $contents, array &$manifest, array &$summary, array &$touched): void
    {
        $this->storeFile($relativePath, $contents, $manifest, $summary, $touched);
    }

    private function storeFile(string $relativePath, string $contents, array &$manifest, array &$summary, array &$touched): void
    {
        $relativePath = ltrim($relativePath, '/');
        $path = $this->absolutePath($relativePath);
        $hash = sha1($contents);

        $touched[$relativePath] = true;

        if (
            ($manifest[$relativePath] ?? null) === $hash
            && $this->filesystem->exists($path)
            && $this->filesystem->exists($path.'.gz')
        ) {
            $summary['skipped']++;

            return;
        }

        $this->filesystem->ensureDirectoryExists(dirname($path));

        $gzipped = gzencode($contents, $this->gzipLevel());

        if ($gzipped === false) {
            throw new RuntimeException('静态文件 Gzip 压缩失败。');
        }

        $this->filesystem->put($path, $contents);
        $this->filesystem->put($path.'.gz', $gzipped);
        $manifest[$relativePath] = $hash;
        $summary['built']++;
    }

    private function loadManifest(): array
    {
        $path = $this->manifestPath();

        if (! $this->filesystem->exists($path)) {
            return [];
        }

        $decoded = json_decode((string) $this->filesystem->get($path), true);

        return is_array($decoded) ? $decoded : [];
    }

    private function saveManifest(array $manifest): void
    {
        $path = $this->manifestPath();
        $this->filesystem->ensureDirectoryExists(dirname($path));
        ksort($manifest);
        $this->filesystem->put($path, json_encode($manifest, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
    }

    private function manifestPath(): string
    {
        return storage_path('app/static-build-manifest.json');
    }

    private function pendingMarkerPath(): string
    {
        return storage_path('app/static-build.pending');
    }

    private function outputRoot(): string
    {
        return public_path(trim((string) config('community.static.output_dir', 'static'), '/'));
    }

    private function absolutePath(string $relativePath): string
    {
        return $this->outputRoot().DIRECTORY_SEPARATOR.ltrim($relativePath, DIRECTORY_SEPARATOR);
    }

    private function channelPath(Channel $channel): string
    {
        return 'channels/'.$channel->public_id.'/index.html';
    }

    private function articlePath(Article $article): string
    {
        $article->loadMissing('channel');

        return $this->articlePathFromPublicIds($article->channel->public_id, $article->public_id);
    }

    private function articlePathFromPublicIds(string $channelPublicId, string $articlePublicId): string
    {
        return 'channels/'.$channelPublicId.'/articles/'.$articlePublicId.'/index.html';
    }

    private function featuredChannelId(): ?int
    {
        return Channel::query()->where('slug', Channel::SLUG_FEATURED)->value('id');
    }

    private function isBuildableArticle(Article $article): bool
    {
        return $article->is_published
            && $article->published_at !== null
            && ! $article->published_at->isFuture()
            && $article->channel_id !== null;
    }

    private function gzipLevel(): int
    {
        return max(1, min(9, (int) config('community.static.gzip_level', 6)));
    }

    private function channelChunkSize(): int
    {
        return max(1, (int) config('community.static.channel_chunk_size', 50));
    }

    private function articleChunkSize(): int
    {
        return max(1, (int) config('community.static.article_chunk_size', 100));
    }

    private function notify(?callable $progress, string $message): void
    {
        if ($progress !== null) {
            $progress($message);
        }
    }

    private function makeSummary(): array
    {
        return [
            'built' => 0,
            'skipped' => 0,
            'deleted' => 0,
        ];
    }

    private function summaryMessage(array $summary): string
    {
        return sprintf(
            '静态页面构建完成：新增/更新 %d，跳过 %d，删除 %d。',
            $summary['built'] ?? 0,
            $summary['skipped'] ?? 0,
            $summary['deleted'] ?? 0,
        );
    }
}
