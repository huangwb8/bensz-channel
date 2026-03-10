<?php

namespace App\Console\Commands;

use App\Models\Article;
use App\Models\Channel;
use App\Support\StaticPageBuilder;
use Illuminate\Console\Command;

class BuildStaticSite extends Command
{
    protected $signature = 'site:build-static
        {--async : 将静态构建任务加入队列}
        {--full : 强制执行全量构建}
        {--article= : 仅重建指定文章及其依赖页面（ID、固定哈希标识或 slug）}
        {--channel= : 仅重建指定频道及其依赖页面（ID、固定哈希标识或 slug）}';

    protected $description = '重新构建游客可访问的静态 HTML 页面';

    public function handle(StaticPageBuilder $staticPageBuilder): int
    {
        if ($this->option('async')) {
            $staticPageBuilder->queuePayload($this->resolvePayload($staticPageBuilder));
            $this->info('静态页面构建任务已加入队列。');

            return self::SUCCESS;
        }

        $payload = $this->resolvePayload($staticPageBuilder);
        $summary = $staticPageBuilder->buildPayload($payload, fn (string $message) => $this->line($message));

        $this->info(sprintf(
            '静态页面已重建完成（新增/更新 %d，跳过 %d，删除 %d）。',
            $summary['built'] ?? 0,
            $summary['skipped'] ?? 0,
            $summary['deleted'] ?? 0,
        ));

        return self::SUCCESS;
    }

    private function resolvePayload(StaticPageBuilder $staticPageBuilder): array
    {
        if ($this->option('full')) {
            return ['full' => true];
        }

        $articleIdentifier = trim((string) $this->option('article'));
        if ($articleIdentifier !== '') {
            $article = Article::query()
                ->with('channel')
                ->wherePublicReference($articleIdentifier)
                ->firstOrFail();

            return $staticPageBuilder->payloadForArticle($article, $staticPageBuilder->captureArticleState($article));
        }

        $channelIdentifier = trim((string) $this->option('channel'));
        if ($channelIdentifier !== '') {
            $channel = Channel::query()
                ->wherePublicReference($channelIdentifier)
                ->firstOrFail();

            return $staticPageBuilder->payloadForChannel($channel);
        }

        return ['full' => true];
    }
}
