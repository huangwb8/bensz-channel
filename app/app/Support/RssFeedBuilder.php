<?php

namespace App\Support;

use App\Models\Channel;
use App\Models\Tag;
use DOMDocument;
use Illuminate\Support\Collection;

class RssFeedBuilder
{
    public function buildForAllChannels(Collection $articles): string
    {
        return $this->build(
            title: config('community.site.name').' · 全部版块',
            description: config('community.site.tagline'),
            siteUrl: route('home'),
            articles: $articles,
        );
    }

    public function buildForChannel(Channel $channel, Collection $articles): string
    {
        return $this->build(
            title: config('community.site.name').' · '.$channel->name,
            description: $channel->description ?: '订阅 '.$channel->name.' 的最新文章',
            siteUrl: route('channels.show', $channel),
            articles: $articles,
        );
    }

    public function buildForTag(Tag $tag, Collection $articles): string
    {
        return $this->build(
            title: config('community.site.name').' · #'.$tag->name,
            description: $tag->description ?: '订阅标签 '.$tag->name.' 的最新文章',
            siteUrl: route('home'),
            articles: $articles,
        );
    }

    private function build(string $title, string $description, string $siteUrl, Collection $articles): string
    {
        $dom = new DOMDocument('1.0', 'UTF-8');
        $dom->formatOutput = true;

        $rss = $dom->createElement('rss');
        $rss->setAttribute('version', '2.0');
        $dom->appendChild($rss);

        $channelElement = $dom->createElement('channel');
        $rss->appendChild($channelElement);

        $channelElement->appendChild($dom->createElement('title', $title));
        $channelElement->appendChild($dom->createElement('link', $siteUrl));
        $channelElement->appendChild($dom->createElement('description', $description));
        $channelElement->appendChild($dom->createElement('language', 'zh-CN'));
        $channelElement->appendChild($dom->createElement('lastBuildDate', now()->toRssString()));

        foreach ($articles as $article) {
            $item = $dom->createElement('item');
            $item->appendChild($dom->createElement('title', $article->title));
            $item->appendChild($dom->createElement('link', route('articles.show', [$article->channel, $article])));
            $item->appendChild($dom->createElement('guid', route('articles.show', [$article->channel, $article])));
            $item->appendChild($dom->createElement('pubDate', optional($article->published_at)->toRssString() ?: now()->toRssString()));
            $item->appendChild($dom->createElement('description', $article->excerpt ?: strip_tags($article->html_body)));

            foreach ($article->tags ?? [] as $tag) {
                $item->appendChild($dom->createElement('category', $tag->name));
            }

            $channelElement->appendChild($item);
        }

        return $dom->saveXML() ?: '';
    }
}
