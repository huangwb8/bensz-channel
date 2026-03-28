<?php

namespace App\Support;

use DOMDocument;
use DOMElement;
use DOMNode;
use DOMXPath;
use Illuminate\Support\Str;

class ArticleBodyFormatter
{
    /**
     * @return array{
     *     html:string,
     *     toc:array<int, array{id:string,text:string,number:string,level:int}>,
     *     tocTree:array<int, array{id:string,text:string,number:string,level:int,children:array}>
     * }
     */
    public function format(?string $html): array
    {
        if (blank($html)) {
            return [
                'html' => '',
                'toc' => [],
                'tocTree' => [],
            ];
        }

        $document = new DOMDocument('1.0', 'UTF-8');
        $wrapperId = 'article-markdown-root';

        libxml_use_internal_errors(true);
        $document->loadHTML(
            '<?xml encoding="utf-8" ?><div id="'.$wrapperId.'">'.$html.'</div>',
            LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD,
        );
        libxml_clear_errors();

        $wrapper = $document->getElementById($wrapperId);

        if (! $wrapper instanceof DOMElement) {
            return [
                'html' => trim((string) $html),
                'toc' => [],
                'tocTree' => [],
            ];
        }

        $xpath = new DOMXPath($document);
        $headingNodes = $xpath->query(sprintf(
            '//*[@id="%s"]//*[self::h1 or self::h2 or self::h3 or self::h4 or self::h5 or self::h6]',
            $wrapperId,
        ));

        if ($headingNodes === false || $headingNodes->length === 0) {
            return [
                'html' => $this->innerHtml($wrapper),
                'toc' => [],
                'tocTree' => [],
            ];
        }

        $headings = [];
        $minimumLevel = 6;

        foreach ($headingNodes as $headingNode) {
            if (! $headingNode instanceof DOMElement) {
                continue;
            }

            $level = (int) substr($headingNode->tagName, 1);
            $minimumLevel = min($minimumLevel, $level);
            $headings[] = $headingNode;
        }

        $usedIds = [];
        $counters = array_fill(0, 6, 0);
        $toc = [];

        foreach ($headings as $index => $heading) {
            $text = trim((string) preg_replace('/\s+/u', ' ', $heading->textContent));

            if ($text === '') {
                continue;
            }

            $displayText = $this->stripManualNumbering($text);
            $this->stripManualNumberingFromHeading($heading, $text, $displayText);

            $level = (int) substr($heading->tagName, 1);
            $number = $this->buildNumber($counters, $level);
            $id = $this->resolveHeadingId($heading, $displayText, $usedIds, $index + 1);

            $heading->setAttribute('id', $id);
            $heading->setAttribute('data-heading-number', $number);

            if (! str_contains(' '.$heading->getAttribute('class').' ', ' article-heading ')) {
                $heading->setAttribute('class', trim($heading->getAttribute('class').' article-heading'));
            }

            $numberNode = $document->createElement('span', $number.' ');
            $numberNode->setAttribute('class', 'markdown-heading-number');
            $numberNode->setAttribute('aria-hidden', 'true');
            $heading->insertBefore($numberNode, $heading->firstChild);

            $toc[] = [
                'id' => $id,
                'text' => $displayText,
                'number' => $number,
                'level' => max(1, $level - $minimumLevel + 1),
            ];
        }

        return [
            'html' => $this->innerHtml($wrapper),
            'toc' => $toc,
            'tocTree' => $this->buildTocTree($toc),
        ];
    }

    /**
     * @param  array<int, int>  $counters
     */
    private function buildNumber(array &$counters, int $level): string
    {
        $index = max(1, min(6, $level)) - 1;

        $counters[$index]++;

        for ($position = $index + 1; $position < 6; $position++) {
            $counters[$position] = 0;
        }

        $segments = array_values(array_filter(
            array_slice($counters, 0, $index + 1),
            static fn (int $counter): bool => $counter > 0,
        ));

        return implode('.', $segments);
    }

    /**
     * @param  array<int, string>  $usedIds
     */
    private function resolveHeadingId(DOMElement $heading, string $text, array &$usedIds, int $position): string
    {
        $existingId = trim($heading->getAttribute('id'));
        $baseId = $existingId !== '' ? $existingId : Str::slug($text);

        if ($baseId === '') {
            $baseId = 'section-'.$position;
        }

        $candidate = $baseId;
        $suffix = 2;

        while (in_array($candidate, $usedIds, true)) {
            $candidate = $baseId.'-'.$suffix;
            $suffix++;
        }

        $usedIds[] = $candidate;

        return $candidate;
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

    private function stripManualNumbering(string $text): string
    {
        return preg_replace('/^\d+(?:\.\d+)*(?:[.)、]\s*)/u', '', $text) ?: $text;
    }

    private function stripManualNumberingFromHeading(DOMElement $heading, string $originalText, string $displayText): void
    {
        if ($originalText === $displayText) {
            return;
        }

        foreach ($heading->childNodes as $child) {
            if ($child->nodeType !== XML_TEXT_NODE || ! is_string($child->nodeValue)) {
                continue;
            }

            $child->nodeValue = preg_replace(
                '/^\s*\d+(?:\.\d+)*(?:[.)、]\s*)/u',
                '',
                $child->nodeValue,
            ) ?: $child->nodeValue;

            return;
        }
    }

    /**
     * @param  array<int, array{id:string,text:string,number:string,level:int}>  $toc
     * @return array<int, array{id:string,text:string,number:string,level:int,children:array}>
     */
    private function buildTocTree(array $toc): array
    {
        $tree = [];
        $stack = [];

        foreach ($toc as $item) {
            $node = [
                ...$item,
                'children' => [],
            ];

            while ($stack !== [] && $stack[array_key_last($stack)]['level'] >= $item['level']) {
                array_pop($stack);
            }

            if ($stack === []) {
                $tree[] = $node;
                $stack[] = &$tree[array_key_last($tree)];

                continue;
            }

            $parentIndex = array_key_last($stack);
            $stack[$parentIndex]['children'][] = $node;
            $stack[] = &$stack[$parentIndex]['children'][array_key_last($stack[$parentIndex]['children'])];
        }

        return $tree;
    }
}
