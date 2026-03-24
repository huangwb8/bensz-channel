<?php

namespace App\Support\Seo;

use App\Models\Article;
use App\Models\Channel;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;

class SiteDiscoveryService
{
    public function robotsTxt(): string
    {
        return implode("\n", [
            'User-agent: *',
            'Allow: /',
            'Disallow: /admin',
            'Disallow: /login',
            'Disallow: /auth/',
            'Disallow: /settings/',
            'Disallow: /scan/',
            'Sitemap: '.route('sitemap'),
            '',
        ]);
    }

    public function sitemapXml(): string
    {
        $chunks = [
            '<?xml version="1.0" encoding="UTF-8"?>',
            '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">',
        ];

        $latestArticleTimestamp = $this->latestPublicArticleTimestamp();

        $chunks[] = $this->renderUrlNode(route('home'), $latestArticleTimestamp, 'daily', 1.0);
        $chunks[] = $this->renderUrlNode(route('feeds.articles'), $latestArticleTimestamp, 'daily', 0.6);

        Channel::query()
            ->where('is_public', true)
            ->withMax(['articles as latest_article_published_at' => fn ($query) => $query->published()], 'published_at')
            ->ordered()
            ->each(function (Channel $channel) use (&$chunks): void {
                $channelLastmod = $this->normalizeTimestamp($channel->latest_article_published_at);

                $chunks[] = $this->renderUrlNode(route('channels.show', $channel), $channelLastmod, 'daily', 0.8);
                $chunks[] = $this->renderUrlNode(route('feeds.channels.show', $channel), $channelLastmod, 'daily', 0.5);
            });

        Article::query()
            ->published()
            ->whereHas('channel', fn ($query) => $query->where('is_public', true))
            ->with('channel')
            ->orderBy('id')
            ->chunkById(500, function ($articles) use (&$chunks): void {
                foreach ($articles as $article) {
                    $chunks[] = $this->renderUrlNode(
                        route('articles.show', [$article->channel, $article]),
                        $this->lastModifiedAt($article->updated_at, $article->published_at),
                        'weekly',
                        0.7,
                    );
                }
            });

        $chunks[] = '</urlset>';

        return implode('', $chunks);
    }

    /**
     * @return list<array{loc: string, lastmod?: CarbonInterface|null, changefreq?: string, priority?: float}>
     */
    public function sitemapEntries(): array
    {
        $channels = Channel::query()
            ->where('is_public', true)
            ->withMax(['articles as latest_article_published_at' => fn ($query) => $query->published()], 'published_at')
            ->ordered()
            ->get();

        $articles = Article::query()
            ->published()
            ->whereHas('channel', fn ($query) => $query->where('is_public', true))
            ->with('channel')
            ->latestPublished()
            ->get();

        $latestArticleTimestamp = $articles
            ->map(fn (Article $article) => $this->lastModifiedAt($article->updated_at, $article->published_at))
            ->filter()
            ->sortDesc()
            ->first();

        $entries = [
            [
                'loc' => route('home'),
                'lastmod' => $latestArticleTimestamp,
                'changefreq' => 'daily',
                'priority' => 1.0,
            ],
            [
                'loc' => route('feeds.articles'),
                'lastmod' => $latestArticleTimestamp,
                'changefreq' => 'daily',
                'priority' => 0.6,
            ],
        ];

        foreach ($channels as $channel) {
            $channelLastmod = $this->normalizeTimestamp($channel->latest_article_published_at);

            $entries[] = [
                'loc' => route('channels.show', $channel),
                'lastmod' => $channelLastmod,
                'changefreq' => 'daily',
                'priority' => 0.8,
            ];

            $entries[] = [
                'loc' => route('feeds.channels.show', $channel),
                'lastmod' => $channelLastmod,
                'changefreq' => 'daily',
                'priority' => 0.5,
            ];
        }

        foreach ($articles as $article) {
            $entries[] = [
                'loc' => route('articles.show', [$article->channel, $article]),
                'lastmod' => $this->lastModifiedAt($article->updated_at, $article->published_at),
                'changefreq' => 'weekly',
                'priority' => 0.7,
            ];
        }

        return $entries;
    }

    private function latestPublicArticleTimestamp(): ?CarbonInterface
    {
        $publishedAt = $this->normalizeTimestamp(
            Article::query()
                ->published()
                ->whereHas('channel', fn ($query) => $query->where('is_public', true))
                ->max('published_at')
        );
        $updatedAt = $this->normalizeTimestamp(
            Article::query()
                ->published()
                ->whereHas('channel', fn ($query) => $query->where('is_public', true))
                ->max('updated_at')
        );

        return $this->lastModifiedAt($updatedAt, $publishedAt);
    }

    private function renderUrlNode(string $loc, ?CarbonInterface $lastmod = null, ?string $changefreq = null, ?float $priority = null): string
    {
        $xml = '<url><loc>'.$this->escapeXml($loc).'</loc>';

        if ($lastmod instanceof CarbonInterface) {
            $xml .= '<lastmod>'.$this->escapeXml($lastmod->toAtomString()).'</lastmod>';
        }

        if (filled($changefreq)) {
            $xml .= '<changefreq>'.$this->escapeXml((string) $changefreq).'</changefreq>';
        }

        if ($priority !== null) {
            $xml .= '<priority>'.$this->escapeXml(number_format($priority, 1, '.', '')).'</priority>';
        }

        return $xml.'</url>';
    }

    private function escapeXml(string $value): string
    {
        return htmlspecialchars($value, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }

    private function lastModifiedAt(?CarbonInterface $updatedAt, ?CarbonInterface $publishedAt): ?CarbonInterface
    {
        if ($updatedAt instanceof CarbonInterface && $publishedAt instanceof CarbonInterface) {
            return $updatedAt->greaterThan($publishedAt) ? $updatedAt : $publishedAt;
        }

        return $updatedAt ?? $publishedAt;
    }

    private function normalizeTimestamp(mixed $value): ?CarbonInterface
    {
        if ($value instanceof CarbonInterface) {
            return $value;
        }

        if (is_string($value) && $value !== '') {
            return CarbonImmutable::parse($value);
        }

        return null;
    }
}
