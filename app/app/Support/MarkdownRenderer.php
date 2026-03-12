<?php

namespace App\Support;

use DOMDocument;
use DOMElement;
use DOMNode;
use DOMXPath;
use Illuminate\Support\Str;

class MarkdownRenderer
{
    public function toHtml(?string $markdown): string
    {
        if (blank($markdown)) {
            return '';
        }

        $html = trim((string) Str::markdown($markdown, [
            'html_input' => 'strip',
            'allow_unsafe_links' => false,
        ]));

        return $this->upgradeStandaloneVideoLinks($html);
    }

    public function excerpt(?string $markdown, int $limit = 120): string
    {
        $plainText = trim(preg_replace('/\s+/', ' ', strip_tags($this->toHtml($markdown))));

        return Str::limit($plainText, $limit);
    }

    private function upgradeStandaloneVideoLinks(string $html): string
    {
        if ($html === '') {
            return '';
        }

        $document = new DOMDocument('1.0', 'UTF-8');
        $wrapperId = 'markdown-video-embed-root';

        libxml_use_internal_errors(true);
        $document->loadHTML(
            '<?xml encoding="utf-8" ?><div id="'.$wrapperId.'">'.$html.'</div>',
            LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD,
        );
        libxml_clear_errors();

        $wrapper = $document->getElementById($wrapperId);

        if (! $wrapper instanceof DOMElement) {
            return $html;
        }

        $xpath = new DOMXPath($document);
        $paragraphNodes = $xpath->query(sprintf('//*[@id="%s"]/p', $wrapperId));

        if ($paragraphNodes === false || $paragraphNodes->length === 0) {
            return trim($html);
        }

        foreach ($paragraphNodes as $paragraphNode) {
            if (! $paragraphNode instanceof DOMElement) {
                continue;
            }

            $anchor = $this->standaloneAnchor($paragraphNode);

            if (! $anchor instanceof DOMElement) {
                continue;
            }

            $href = trim($anchor->getAttribute('href'));

            if (! $this->isVideoUrl($href)) {
                continue;
            }

            $paragraphNode->parentNode?->replaceChild(
                $this->buildVideoEmbedNode($document, $href, trim($anchor->textContent)),
                $paragraphNode,
            );
        }

        return $this->innerHtml($wrapper);
    }

    private function standaloneAnchor(DOMElement $paragraph): ?DOMElement
    {
        $anchor = null;

        foreach ($paragraph->childNodes as $child) {
            if ($child instanceof DOMElement && $child->tagName === 'a') {
                if ($anchor instanceof DOMElement) {
                    return null;
                }

                $anchor = $child;

                continue;
            }

            if ($child->nodeType === XML_TEXT_NODE && trim((string) $child->nodeValue) === '') {
                continue;
            }

            return null;
        }

        return $anchor;
    }

    private function isVideoUrl(string $url): bool
    {
        if ($url === '') {
            return false;
        }

        $path = parse_url($url, PHP_URL_PATH);

        if (! is_string($path) || $path === '') {
            return false;
        }

        return in_array(Str::lower(pathinfo($path, PATHINFO_EXTENSION)), ['mp4', 'webm', 'ogg', 'ogv'], true);
    }

    private function buildVideoEmbedNode(DOMDocument $document, string $url, string $label): DOMElement
    {
        $wrapper = $document->createElement('div');
        $wrapper->setAttribute('class', 'nextcloud-video');

        $video = $document->createElement('video');
        $video->setAttribute('controls', 'controls');
        $video->setAttribute('preload', 'metadata');
        $video->setAttribute('playsinline', 'playsinline');

        if ($label !== '') {
            $video->setAttribute('title', $label);
            $video->setAttribute('aria-label', $label);
        }

        $source = $document->createElement('source');
        $source->setAttribute('src', $url);
        $source->setAttribute('type', $this->resolveVideoMimeType($url));
        $video->appendChild($source);
        $wrapper->appendChild($video);

        return $wrapper;
    }

    private function resolveVideoMimeType(string $url): string
    {
        return match (Str::lower(pathinfo((string) parse_url($url, PHP_URL_PATH), PATHINFO_EXTENSION))) {
            'webm' => 'video/webm',
            'ogg', 'ogv' => 'video/ogg',
            default => 'video/mp4',
        };
    }

    private function innerHtml(DOMElement $element): string
    {
        $html = '';

        foreach ($element->childNodes as $child) {
            if (! $child instanceof DOMNode) {
                continue;
            }

            $html .= $element->ownerDocument?->saveHTML($child) ?? '';
        }

        return trim($html);
    }
}
