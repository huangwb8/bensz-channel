<?php

namespace Tests\Feature\Subscriptions;

use App\Models\Article;
use App\Models\Channel;
use App\Models\Tag;
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

    public function test_featured_channel_rss_feed_aggregates_featured_articles(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $featuredChannel = Channel::factory()->create([
            'name' => '精华',
            'slug' => 'featured',
            'description' => '站内精选内容。',
        ]);
        $engineering = Channel::factory()->create(['name' => '开发交流', 'slug' => 'engineering']);
        $feedback = Channel::factory()->create(['name' => '反馈建议', 'slug' => 'feedback']);

        $featuredArticle = Article::query()->create([
            'channel_id' => $engineering->id,
            'author_id' => $admin->id,
            'title' => '精华开发文章',
            'slug' => 'featured-engineering-article',
            'excerpt' => '摘要',
            'markdown_body' => '正文',
            'html_body' => '<p>正文</p>',
            'is_published' => true,
            'is_featured' => true,
            'published_at' => now(),
            'cover_gradient' => 'from-violet-500 via-fuchsia-500 to-cyan-500',
        ]);

        Article::query()->create([
            'channel_id' => $feedback->id,
            'author_id' => $admin->id,
            'title' => '普通反馈文章',
            'slug' => 'normal-feedback-article',
            'excerpt' => '摘要',
            'markdown_body' => '正文',
            'html_body' => '<p>正文</p>',
            'is_published' => true,
            'is_featured' => false,
            'published_at' => now(),
            'cover_gradient' => 'from-violet-500 via-fuchsia-500 to-cyan-500',
        ]);

        $response = $this->get(route('feeds.channels.show', $featuredChannel));

        $response->assertOk();
        $response->assertSee('精华开发文章', false);
        $response->assertSee(route('articles.show', [$featuredArticle->channel, $featuredArticle]), false);
        $response->assertDontSee('普通反馈文章', false);
    }

    public function test_tag_rss_feed_only_contains_articles_with_that_tag(): void
    {
        $tag = Tag::query()->create([
            'name' => 'Laravel',
            'slug' => 'laravel',
            'description' => 'Laravel 相关文章',
        ]);
        [$taggedArticle] = $this->createArticleFixture('Laravel 文章', 'laravel-article');
        [$untaggedArticle] = $this->createArticleFixture('普通文章', 'plain-article', 'general', '综合');

        $taggedArticle->tags()->attach($tag);

        $response = $this->get(route('feeds.tags.show', $tag));

        $response->assertOk();
        $response->assertSee('Laravel 文章', false);
        $response->assertSee(route('articles.show', [$taggedArticle->channel, $taggedArticle]), false);
        $response->assertDontSee('普通文章', false);
        $response->assertDontSee(route('articles.show', [$untaggedArticle->channel, $untaggedArticle]), false);
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
