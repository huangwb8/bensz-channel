<?php

namespace Tests\Unit\Support;

use App\Support\ArticleBodyFormatter;
use Tests\TestCase;

class ArticleBodyFormatterTest extends TestCase
{
    public function test_it_adds_heading_numbers_and_builds_toc(): void
    {
        $formatter = app(ArticleBodyFormatter::class);

        $result = $formatter->format(<<<'HTML'
<h2>Overview</h2>
<p>Body</p>
<h3>Details</h3>
<h2>Summary</h2>
HTML);

        $this->assertCount(3, $result['toc']);
        $this->assertSame('1', $result['toc'][0]['number']);
        $this->assertSame('1.1', $result['toc'][1]['number']);
        $this->assertSame('2', $result['toc'][2]['number']);
        $this->assertSame('overview', $result['toc'][0]['id']);
        $this->assertCount(2, $result['tocTree']);
        $this->assertSame('overview', $result['tocTree'][0]['id']);
        $this->assertCount(1, $result['tocTree'][0]['children']);
        $this->assertSame('details', $result['tocTree'][0]['children'][0]['id']);
        $this->assertStringContainsString('class="markdown-heading-number"', $result['html']);
        $this->assertStringContainsString('id="overview"', $result['html']);
        $this->assertStringContainsString('id="details"', $result['html']);
        $this->assertStringContainsString('id="summary"', $result['html']);
    }

    public function test_it_generates_stable_fallback_ids_for_non_latin_headings(): void
    {
        $formatter = app(ArticleBodyFormatter::class);

        $result = $formatter->format(<<<'HTML'
<h2>背景介绍</h2>
<h2>背景介绍</h2>
HTML);

        $this->assertSame('section-1', $result['toc'][0]['id']);
        $this->assertSame('section-2', $result['toc'][1]['id']);
        $this->assertStringContainsString('id="section-1"', $result['html']);
        $this->assertStringContainsString('id="section-2"', $result['html']);
    }

    public function test_it_strips_manual_heading_numbering_before_applying_auto_numbering(): void
    {
        $formatter = app(ArticleBodyFormatter::class);

        $result = $formatter->format('<h3>1. DevTools API 增强</h3>');

        $this->assertSame('DevTools API 增强', $result['toc'][0]['text']);
        $this->assertStringContainsString('>1 </span>DevTools API 增强</h3>', $result['html']);
    }
}
