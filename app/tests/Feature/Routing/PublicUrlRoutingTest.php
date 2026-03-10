<?php

namespace Tests\Feature\Routing;

use App\Models\Article;
use App\Models\Channel;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicUrlRoutingTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_routes_use_fixed_length_hash_like_ids(): void
    {
        [$channel, $article] = $this->createFixture();

        $this->assertMatchesRegularExpression('#/channels/[a-f0-9]{16}$#', route('channels.show', $channel));
        $this->assertMatchesRegularExpression('#/channels/[a-f0-9]{16}/articles/[a-f0-9]{16}$#', route('articles.show', [$channel, $article]));
        $this->assertMatchesRegularExpression('#/feeds/channels/[a-f0-9]{16}\.xml$#', route('feeds.channels.show', $channel));
        $this->assertNotSame($channel->slug, $channel->public_id);
        $this->assertNotSame($article->slug, $article->public_id);
    }

    public function test_legacy_slug_channel_url_redirects_to_canonical_public_id_url(): void
    {
        [$channel] = $this->createFixture();

        $this->get('/channels/'.$channel->slug)
            ->assertRedirect(route('channels.show', $channel));
    }

    public function test_legacy_slug_article_url_redirects_to_canonical_public_id_url(): void
    {
        [$channel, $article] = $this->createFixture();

        $this->get('/channels/'.$channel->slug.'/articles/'.$article->slug)
            ->assertRedirect(route('articles.show', [$channel, $article]));
    }

    public function test_legacy_slug_rss_url_redirects_to_canonical_public_id_url(): void
    {
        [$channel] = $this->createFixture();

        $this->get('/feeds/channels/'.$channel->slug.'.xml')
            ->assertRedirect(route('feeds.channels.show', $channel));
    }

    private function createFixture(): array
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
            'show_in_top_nav' => true,
        ]);

        $article = Article::query()->create([
            'channel_id' => $channel->id,
            'author_id' => $admin->id,
            'title' => '测试文章',
            'slug' => 'test-article',
            'excerpt' => '摘要',
            'markdown_body' => '正文',
            'html_body' => '<p>正文</p>',
            'is_published' => true,
            'published_at' => now(),
            'cover_gradient' => 'from-violet-500 via-fuchsia-500 to-cyan-500',
        ]);

        return [$channel, $article, $admin];
    }
}
