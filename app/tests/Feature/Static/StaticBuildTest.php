<?php

namespace Tests\Feature\Static;

use App\Models\Article;
use App\Models\Channel;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StaticBuildTest extends TestCase
{
    use RefreshDatabase;

    public function test_static_build_command_generates_guest_pages(): void
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
            'title' => '静态站点测试',
            'slug' => 'static-build-test',
            'excerpt' => '摘要',
            'markdown_body' => "## Overview\n\n正文\n\n### Details\n\n更多内容",
            'html_body' => '<h2>Overview</h2><p>正文</p><h3>Details</h3><p>更多内容</p>',
            'is_published' => true,
            'published_at' => now(),
            'cover_gradient' => 'from-violet-500 via-fuchsia-500 to-cyan-500',
        ]);

        $this->artisan('site:build-static')->assertSuccessful();

        $this->assertFileExists(public_path('static/index.html'));
        $this->assertFileExists(public_path('static/channels/notice/articles/static-build-test/index.html'));
        $this->assertFileExists(public_path('static/channels/notice/articles/static-build-test/index.html.gz'));
        $this->assertStringContainsString('文章目录', file_get_contents(public_path('static/channels/notice/articles/static-build-test/index.html')));
        $this->assertStringContainsString('href="#overview"', file_get_contents(public_path('static/channels/notice/articles/static-build-test/index.html')));
    }
}
