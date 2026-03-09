<?php

namespace Tests\Feature\Channels;

use App\Models\Article;
use App\Models\Channel;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FeaturedChannelTest extends TestCase
{
    use RefreshDatabase;

    public function test_featured_channel_lists_featured_articles_across_source_channels(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $featuredChannel = Channel::query()->create([
            'name' => '精华',
            'slug' => 'featured',
            'description' => '站内精选内容。',
            'accent_color' => '#f59e0b',
            'icon' => '⭐',
            'sort_order' => 1,
            'is_public' => true,
            'show_in_top_nav' => true,
        ]);
        $engineering = Channel::query()->create([
            'name' => '开发交流',
            'slug' => 'engineering',
            'description' => '开发讨论',
            'accent_color' => '#06b6d4',
            'icon' => '🛠️',
            'sort_order' => 2,
            'is_public' => true,
            'show_in_top_nav' => true,
        ]);
        $feedback = Channel::query()->create([
            'name' => '反馈建议',
            'slug' => 'feedback',
            'description' => '反馈讨论',
            'accent_color' => '#10b981',
            'icon' => '💬',
            'sort_order' => 3,
            'is_public' => true,
            'show_in_top_nav' => true,
        ]);

        Article::query()->create([
            'channel_id' => $engineering->id,
            'author_id' => $admin->id,
            'title' => '精华开发文章',
            'slug' => 'featured-engineering-article',
            'excerpt' => '开发精选摘要',
            'markdown_body' => '正文',
            'html_body' => '<p>正文</p>',
            'is_published' => true,
            'published_at' => now(),
            'cover_gradient' => 'from-violet-500 via-fuchsia-500 to-cyan-500',
            'is_featured' => true,
            'is_pinned' => false,
        ]);

        Article::query()->create([
            'channel_id' => $feedback->id,
            'author_id' => $admin->id,
            'title' => '精华反馈文章',
            'slug' => 'featured-feedback-article',
            'excerpt' => '反馈精选摘要',
            'markdown_body' => '正文',
            'html_body' => '<p>正文</p>',
            'is_published' => true,
            'published_at' => now()->subMinute(),
            'cover_gradient' => 'from-violet-500 via-fuchsia-500 to-cyan-500',
            'is_featured' => true,
            'is_pinned' => false,
        ]);

        Article::query()->create([
            'channel_id' => $engineering->id,
            'author_id' => $admin->id,
            'title' => '普通开发文章',
            'slug' => 'normal-engineering-article',
            'excerpt' => '普通摘要',
            'markdown_body' => '正文',
            'html_body' => '<p>正文</p>',
            'is_published' => true,
            'published_at' => now()->subMinutes(2),
            'cover_gradient' => 'from-violet-500 via-fuchsia-500 to-cyan-500',
            'is_featured' => false,
            'is_pinned' => false,
        ]);

        $this->get(route('channels.show', $featuredChannel))
            ->assertOk()
            ->assertSee('精华开发文章')
            ->assertSee('精华反馈文章')
            ->assertSee('开发交流')
            ->assertSee('反馈建议')
            ->assertDontSee('普通开发文章');
    }
}
