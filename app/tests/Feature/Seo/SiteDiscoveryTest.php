<?php

namespace Tests\Feature\Seo;

use App\Models\Article;
use App\Models\Channel;
use App\Models\User;
use App\Support\CanonicalUrlManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SiteDiscoveryTest extends TestCase
{
    use RefreshDatabase;

    public function test_robots_txt_announces_sitemap_and_allows_crawling_public_content(): void
    {
        $this->configurePublicUrl();

        $response = $this->get('/robots.txt');

        $response->assertOk();
        $response->assertHeader('Content-Type', 'text/plain; charset=UTF-8');
        $response->assertSee('User-agent: *', false);
        $response->assertSee('Disallow:', false);
        $response->assertSee('Sitemap: https://community.example.com/sitemap.xml', false);
    }

    public function test_sitemap_xml_contains_public_pages_channels_articles_and_feeds_only(): void
    {
        [$publicChannel, $publishedArticle] = $this->createPublicFixture();
        [$privateChannel, $draftArticle] = $this->createPrivateFixture();

        $this->configurePublicUrl();

        $response = $this->get('/sitemap.xml');

        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/xml; charset=UTF-8');
        $response->assertSee(route('home'), false);
        $response->assertSee(route('channels.show', $publicChannel), false);
        $response->assertSee(route('articles.show', [$publicChannel, $publishedArticle]), false);
        $response->assertSee(route('feeds.articles'), false);
        $response->assertSee(route('feeds.channels.show', $publicChannel), false);
        $response->assertDontSee(route('channels.show', $privateChannel), false);
        $response->assertDontSee(route('articles.show', [$privateChannel, $draftArticle]), false);
    }

    private function createPublicFixture(): array
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $channel = Channel::query()->create([
            'name' => '公告',
            'slug' => 'notice',
            'description' => '公开公告频道',
            'accent_color' => '#8b5cf6',
            'icon' => '📢',
            'sort_order' => 1,
            'is_public' => true,
            'show_in_top_nav' => true,
        ]);

        $article = Article::query()->create([
            'channel_id' => $channel->id,
            'author_id' => $admin->id,
            'title' => '公开文章',
            'slug' => 'public-article',
            'excerpt' => '摘要',
            'markdown_body' => '正文',
            'html_body' => '<p>正文</p>',
            'is_published' => true,
            'published_at' => now(),
            'cover_gradient' => 'from-violet-500 via-fuchsia-500 to-cyan-500',
        ]);

        return [$channel, $article];
    }

    private function createPrivateFixture(): array
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $channel = Channel::query()->create([
            'name' => '内部讨论',
            'slug' => 'internal',
            'description' => '内部频道',
            'accent_color' => '#0f172a',
            'icon' => '🔒',
            'sort_order' => 2,
            'is_public' => false,
            'show_in_top_nav' => false,
        ]);

        $article = Article::query()->create([
            'channel_id' => $channel->id,
            'author_id' => $admin->id,
            'title' => '草稿文章',
            'slug' => 'draft-article',
            'excerpt' => '摘要',
            'markdown_body' => '正文',
            'html_body' => '<p>正文</p>',
            'is_published' => false,
            'published_at' => now()->addDay(),
            'cover_gradient' => 'from-slate-500 via-slate-600 to-slate-700',
        ]);

        return [$channel, $article];
    }

    private function configurePublicUrl(): void
    {
        config([
            'app.url' => 'https://community.example.com',
        ]);

        app(CanonicalUrlManager::class)->apply();
    }
}
