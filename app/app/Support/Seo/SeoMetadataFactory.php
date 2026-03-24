<?php

namespace App\Support\Seo;

use App\Models\Article;
use App\Models\Channel;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class SeoMetadataFactory
{
    public function forCurrentRequest(array $data, string $viewName): array
    {
        if (($data['seo'] ?? null) !== null) {
            return is_array($data['seo']) ? $data['seo'] : [];
        }

        $article = $data['article'] ?? $data['currentArticle'] ?? null;
        $channel = $data['currentChannel'] ?? null;
        $routeName = (string) request()->route()?->getName();

        if ($routeName === 'articles.show' && $article instanceof Article) {
            return $this->forArticle($article);
        }

        if ($routeName === 'channels.show' && $channel instanceof Channel) {
            return $this->forChannel($channel);
        }

        if ($routeName === 'home') {
            return $this->forHome();
        }

        if ($viewName === 'layouts.auth' || $this->shouldNoIndex($routeName)) {
            return $this->forNoIndex(Arr::get($data, 'pageTitle'));
        }

        return [];
    }

    public function forHome(): array
    {
        $canonical = route('home');
        $siteName = $this->siteName();
        $description = $this->siteTagline();

        return [
            'title' => $siteName,
            'description' => $description,
            'canonical' => $canonical,
            'robots' => 'index, follow, max-image-preview:large',
            'og_type' => 'website',
            'url' => $canonical,
            'site_name' => $siteName,
            'twitter_card' => 'summary',
            'alternate_links' => [
                [
                    'type' => 'application/rss+xml',
                    'title' => $siteName.' RSS',
                    'href' => route('feeds.articles'),
                ],
            ],
            'json_ld' => [
                [
                    '@context' => 'https://schema.org',
                    '@type' => 'WebSite',
                    'name' => $siteName,
                    'description' => $description,
                    'url' => $canonical,
                    'inLanguage' => 'zh-CN',
                ],
            ],
        ];
    }

    public function forChannel(Channel $channel): array
    {
        $canonical = route('channels.show', $channel);
        $siteName = $this->siteName();
        $description = $this->normalizeDescription($channel->description ?: $this->siteTagline());
        $fullTitle = $this->pageTitle($channel->name);

        return [
            'title' => $fullTitle,
            'description' => $description,
            'canonical' => $canonical,
            'robots' => 'index, follow, max-image-preview:large',
            'og_type' => 'website',
            'url' => $canonical,
            'site_name' => $siteName,
            'twitter_card' => 'summary',
            'alternate_links' => [
                [
                    'type' => 'application/rss+xml',
                    'title' => $channel->name.' RSS',
                    'href' => route('feeds.channels.show', $channel),
                ],
            ],
            'json_ld' => [
                [
                    '@context' => 'https://schema.org',
                    '@type' => 'CollectionPage',
                    'name' => $channel->name,
                    'description' => $description,
                    'url' => $canonical,
                    'isPartOf' => [
                        '@type' => 'WebSite',
                        'name' => $siteName,
                        'url' => route('home'),
                    ],
                ],
                $this->breadcrumbSchema([
                    ['name' => '首页', 'url' => route('home')],
                    ['name' => $channel->name, 'url' => $canonical],
                ]),
            ],
        ];
    }

    public function forArticle(Article $article, bool $indexable = true): array
    {
        $article->loadMissing(['channel', 'author', 'tags']);

        $canonical = route('articles.show', [$article->channel, $article]);
        $description = $this->normalizeDescription($article->excerpt ?: $this->excerptFromHtml($article->html_body));
        $fullTitle = $this->pageTitle($article->title);
        $modifiedTime = ($article->updated_at ?? $article->published_at)?->toIso8601String();
        $keywords = $article->tags
            ->pluck('name')
            ->filter(fn (mixed $value): bool => filled($value))
            ->implode(', ');

        $articleSchema = [
            '@context' => 'https://schema.org',
            '@type' => 'Article',
            'headline' => $article->title,
            'description' => $description,
            'url' => $canonical,
            'mainEntityOfPage' => $canonical,
            'datePublished' => $article->published_at?->toIso8601String(),
            'dateModified' => $modifiedTime,
            'author' => [
                '@type' => 'Person',
                'name' => $article->author?->name ?? $this->siteName(),
            ],
            'publisher' => [
                '@type' => 'Organization',
                'name' => $this->siteName(),
            ],
            'articleSection' => $article->channel?->name,
            'inLanguage' => 'zh-CN',
        ];

        if ($keywords !== '') {
            $articleSchema['keywords'] = $keywords;
        }

        if (! $indexable) {
            return [
                'title' => $fullTitle,
                'description' => $description,
                'robots' => 'noindex, nofollow',
                'site_name' => $this->siteName(),
                'twitter_card' => 'summary',
                'author' => $article->author?->name,
            ];
        }

        return [
            'title' => $fullTitle,
            'description' => $description,
            'canonical' => $canonical,
            'robots' => 'index, follow, max-image-preview:large',
            'og_type' => 'article',
            'url' => $canonical,
            'site_name' => $this->siteName(),
            'twitter_card' => 'summary',
            'author' => $article->author?->name,
            'keywords' => $keywords !== '' ? $keywords : null,
            'published_time' => $article->published_at?->toIso8601String(),
            'modified_time' => $modifiedTime,
            'alternate_links' => [
                [
                    'type' => 'application/rss+xml',
                    'title' => $article->channel->name.' RSS',
                    'href' => route('feeds.channels.show', $article->channel),
                ],
            ],
            'json_ld' => [
                $articleSchema,
                $this->breadcrumbSchema([
                    ['name' => '首页', 'url' => route('home')],
                    ['name' => $article->channel->name, 'url' => route('channels.show', $article->channel)],
                    ['name' => $article->title, 'url' => $canonical],
                ]),
            ],
        ];
    }

    public function forNoIndex(?string $pageTitle = null): array
    {
        return [
            'title' => filled($pageTitle) ? $this->pageTitle($pageTitle) : $this->siteName(),
            'description' => $this->siteTagline(),
            'robots' => 'noindex, nofollow',
            'site_name' => $this->siteName(),
            'twitter_card' => 'summary',
        ];
    }

    /**
     * @param  list<array{name: string, url: string}>  $items
     * @return array<string, mixed>
     */
    private function breadcrumbSchema(array $items): array
    {
        return [
            '@context' => 'https://schema.org',
            '@type' => 'BreadcrumbList',
            'itemListElement' => collect($items)
                ->values()
                ->map(fn (array $item, int $index): array => [
                    '@type' => 'ListItem',
                    'position' => $index + 1,
                    'name' => $item['name'],
                    'item' => $item['url'],
                ])
                ->all(),
        ];
    }

    private function pageTitle(string $title): string
    {
        return trim($title).' · '.$this->siteName();
    }

    private function siteName(): string
    {
        return trim((string) config('community.site.name', 'Bensz Channel'));
    }

    private function siteTagline(): string
    {
        return $this->normalizeDescription((string) config('community.site.tagline', ''));
    }

    private function normalizeDescription(string $description): string
    {
        $normalized = preg_replace('/\s+/u', ' ', trim($description));

        return Str::limit($normalized ?: $this->siteName(), 160, '');
    }

    private function excerptFromHtml(?string $html): string
    {
        $text = html_entity_decode(strip_tags((string) $html), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = preg_replace('/\s+/u', ' ', trim($text));

        return Str::limit($text ?: $this->siteTagline(), 160, '');
    }

    private function shouldNoIndex(string $routeName): bool
    {
        if ($routeName === '') {
            return true;
        }

        return ! in_array($routeName, ['home', 'channels.show', 'articles.show'], true);
    }
}
