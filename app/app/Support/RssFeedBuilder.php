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

        $channelElement->appendChild($this->createTextElement($dom, 'title', $title));
        $channelElement->appendChild($this->createTextElement($dom, 'link', $siteUrl));
        $channelElement->appendChild($this->createTextElement($dom, 'description', $description));
        $channelElement->appendChild($this->createTextElement($dom, 'language', 'zh-CN'));
        $channelElement->appendChild($this->createTextElement($dom, 'lastBuildDate', now()->toRssString()));

        foreach ($articles as $article) {
            $item = $dom->createElement('item');
            $item->appendChild($this->createTextElement($dom, 'title', $article->title));
            $item->appendChild($this->createTextElement($dom, 'link', route('articles.show', [$article->channel, $article])));
            $item->appendChild($this->createTextElement($dom, 'guid', route('articles.show', [$article->channel, $article])));
            $item->appendChild($this->createTextElement($dom, 'pubDate', optional($article->published_at)->toRssString() ?: now()->toRssString()));
            $item->appendChild($this->createTextElement($dom, 'description', $article->excerpt ?: strip_tags($article->html_body)));

            foreach ($article->tags ?? [] as $tag) {
                $item->appendChild($this->createTextElement($dom, 'category', $tag->name));
            }

            $channelElement->appendChild($item);
        }

        return $dom->saveXML() ?: '';
    }

    private function createTextElement(DOMDocument $dom, string $name, string $value): \DOMElement
    {
        $element = $dom->createElement($name);
        $element->appendChild($dom->createTextNode($value));

        return $element;
    }
}
