<?php

namespace Tests\Feature\Articles;

use App\Models\Article;
use App\Models\Channel;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ArticleTocTest extends TestCase
{
    use RefreshDatabase;

    public function test_article_page_renders_toc_and_numbered_headings(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $channel = Channel::query()->create([
            'name' => '公告',
            'slug' => 'notice',
            'description' => '公告频道',
            'accent_color' => '#8b5cf6',
            'icon' => '📢',
            'sort_order' => 1,
            'is_public' => true,
        ]);
        $article = Article::query()->create([
            'channel_id' => $channel->id,
            'author_id' => $admin->id,
            'title' => '目录文章',
            'slug' => 'toc-article',
            'excerpt' => '摘要',
            'markdown_body' => "## Overview\n\nBody\n\n### Details\n\nMore\n\n## Summary",
            'html_body' => '<h2>Overview</h2><p>Body</p><h3>Details</h3><p>More</p><h2>Summary</h2>',
            'is_published' => true,
            'published_at' => now(),
            'cover_gradient' => 'from-violet-500 via-fuchsia-500 to-cyan-500',
        ]);

        $this->get(route('articles.show', [$channel, $article]))
            ->assertOk()
            ->assertSee('文章目录')
            ->assertSee('article-toc-mobile', false)
            ->assertSee('article-toc-desktop', false)
            ->assertSee('href="#overview"', false)
            ->assertSee('href="#details"', false)
            ->assertSeeInOrder(['1', 'Overview'], false)
            ->assertSeeInOrder(['1.1', 'Details'], false)
            ->assertSeeInOrder(['2', 'Summary'], false);
    }
}
