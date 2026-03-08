<?php

namespace Tests\Feature\Subscriptions;

use App\Models\Article;
use App\Models\Channel;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RssFeedTest extends TestCase
{
    use RefreshDatabase;

    public function test_global_rss_feed_is_public(): void
    {
        [$article] = $this->createArticleFixture('公告文章', 'notice-article');

        $response = $this->get(route('feeds.articles'));

        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/rss+xml; charset=UTF-8');
        $response->assertSee('公告文章', false);
        $response->assertSee(route('articles.show', [$article->channel, $article]), false);
    }

    public function test_channel_rss_feed_only_contains_articles_from_that_channel(): void
    {
        [$engineeringArticle, $engineeringChannel] = $this->createArticleFixture('开发文章', 'engineering-article', 'engineering', '开发交流');
        $this->createArticleFixture('公告文章', 'notice-article', 'notice', '公告');

        $response = $this->get(route('feeds.channels.show', $engineeringChannel));

        $response->assertOk();
        $response->assertSee('开发文章', false);
        $response->assertSee(route('articles.show', [$engineeringArticle->channel, $engineeringArticle]), false);
        $response->assertDontSee('公告文章', false);
    }

    private function createArticleFixture(
        string $title,
        string $slug,
        string $channelSlug = 'notice',
        string $channelName = '公告',
    ): array {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $channel = Channel::factory()->create(['name' => $channelName, 'slug' => $channelSlug]);
        $article = Article::query()->create([
            'channel_id' => $channel->id,
            'author_id' => $admin->id,
            'title' => $title,
            'slug' => $slug,
            'excerpt' => '摘要',
            'markdown_body' => '正文',
            'html_body' => '<p>正文</p>',
            'is_published' => true,
            'published_at' => now(),
            'cover_gradient' => 'from-violet-500 via-fuchsia-500 to-cyan-500',
        ]);

        return [$article, $channel, $admin];
    }
}
