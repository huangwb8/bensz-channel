<?php

namespace Tests\Unit\Support;

use App\Support\MarkdownRenderer;
use Tests\TestCase;

class MarkdownRendererTest extends TestCase
{
    public function test_it_converts_standalone_video_links_into_embeds(): void
    {
        $renderer = app(MarkdownRenderer::class);

        $html = $renderer->toHtml('[视频：演示](/storage/media/article/2026/03/demo.mp4)');

        $this->assertStringContainsString('class="nextcloud-video"', $html);
        $this->assertStringContainsString('<video controls="controls" preload="metadata" playsinline="playsinline"', $html);
        $this->assertStringContainsString('<source src="/storage/media/article/2026/03/demo.mp4" type="video/mp4">', $html);
        $this->assertStringNotContainsString('<a href="/storage/media/article/2026/03/demo.mp4">', $html);
    }

    public function test_it_keeps_inline_video_links_as_regular_links(): void
    {
        $renderer = app(MarkdownRenderer::class);

        $html = $renderer->toHtml('参考[视频：演示](/storage/media/article/2026/03/demo.mp4)了解细节。');

        $this->assertStringNotContainsString('class="nextcloud-video"', $html);
        $this->assertStringContainsString('<a href="/storage/media/article/2026/03/demo.mp4">视频：演示</a>', $html);
    }
}
