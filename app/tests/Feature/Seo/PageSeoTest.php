<?php

namespace Tests\Feature\Seo;

use App\Models\Article;
use App\Models\Channel;
use App\Models\Tag;
use App\Models\User;
use App\Support\CanonicalUrlManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PageSeoTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_page_renders_default_seo_metadata(): void
    {
        $this->configurePublicUrl();

        $response = $this->get('/');

        $response->assertOk();
        $response->assertSee('<link rel="canonical" href="https://community.example.com">', false);
        $response->assertSee('<meta property="og:type" content="website">', false);
        $response->assertSee('<meta name="twitter:card" content="summary">', false);
        $response->assertSee('"@type":"WebSite"', false);
        $response->assertSee('"url":"https://community.example.com"', false);
    }

    public function test_channel_page_renders_collection_seo_metadata_and_rss_alternate_link(): void
    {
        [$channel] = $this->createFixture();
        $this->configurePublicUrl();

        $response = $this->get(route('channels.show', $channel));

        $response->assertOk();
        $response->assertSee('<link rel="canonical" href="'.route('channels.show', $channel).'">', false);
        $response->assertSee('<link rel="alternate" type="application/rss+xml" title="'.$channel->name.' RSS" href="'.route('feeds.channels.show', $channel).'">', false);
        $response->assertSee('<meta property="og:type" content="website">', false);
        $response->assertSee('"@type":"CollectionPage"', false);
        $response->assertSee('"name":"'.$channel->name.'"', false);
        $response->assertSee('"@type":"BreadcrumbList"', false);
    }

    public function test_article_page_renders_article_specific_seo_metadata(): void
    {
        [$channel, $article] = $this->createFixture();
        $tag = Tag::query()->create([
            'name' => 'Laravel',
            'slug' => 'laravel',
            'description' => 'Laravel 相关文章',
        ]);
        $article->tags()->attach($tag);

        $this->configurePublicUrl();

        $response = $this->get(route('articles.show', [$channel, $article]));

        $response->assertOk();
        $response->assertSee('<link rel="canonical" href="'.route('articles.show', [$channel, $article]).'">', false);
        $response->assertSee('<meta property="og:type" content="article">', false);
        $response->assertSee('<meta property="article:published_time" content="'.$article->published_at?->toIso8601String().'">', false);
        $response->assertSee('<meta name="description" content="这是一段适合搜索引擎与社交分享的文章摘要。">', false);
        $response->assertSee('"@type":"Article"', false);
        $response->assertSee('"headline":"'.$article->title.'"', false);
        $response->assertSee('"keywords":"Laravel"', false);
    }

    public function test_login_page_is_marked_noindex(): void
    {
        $this->configurePublicUrl();

        $response = $this->get(route('login'));

        $response->assertOk();
        $response->assertSee('<meta name="robots" content="noindex, nofollow">', false);
        $response->assertDontSee('<link rel="canonical"', false);
    }

    public function test_admin_preview_of_unpublished_article_is_marked_noindex(): void
    {
        [$channel, $article, $admin] = $this->createFixture([
            'is_published' => false,
            'published_at' => now()->addHour(),
        ]);

        $this->configurePublicUrl();

        $response = $this->actingAs($admin)->get(route('articles.show', [$channel, $article]));

        $response->assertOk();
        $response->assertSee('<meta name="robots" content="noindex, nofollow">', false);
        $response->assertDontSee('<link rel="canonical"', false);
        $response->assertDontSee('"@type":"Article"', false);
    }

    private function createFixture(array $articleOverrides = []): array
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'name' => '站长',
        ]);

        $channel = Channel::query()->create([
            'name' => '开发交流',
            'slug' => 'engineering',
            'description' => '讨论产品开发、架构设计与上线经验。',
            'accent_color' => '#2563eb',
            'icon' => '🛠️',
            'sort_order' => 1,
            'is_public' => true,
            'show_in_top_nav' => true,
        ]);

        $article = Article::query()->create([
            'channel_id' => $channel->id,
            'author_id' => $admin->id,
            'title' => 'Laravel SEO 最佳实践',
            'slug' => 'laravel-seo-best-practices',
            'excerpt' => '这是一段适合搜索引擎与社交分享的文章摘要。',
            'markdown_body' => "## 引言\n\n正文",
            'html_body' => '<h2>引言</h2><p>正文</p>',
            'is_published' => true,
            'published_at' => now()->startOfMinute(),
            'cover_gradient' => 'from-blue-500 via-cyan-500 to-emerald-500',
            'comment_count' => 3,
            ...$articleOverrides,
        ]);

        return [$channel, $article, $admin];
    }

    private function configurePublicUrl(): void
    {
        config([
            'app.url' => 'https://community.example.com',
            'app.asset_url' => 'https://cdn.example.com',
            'community.site.name' => 'Bensz Channel',
            'community.site.tagline' => '面向知识社区的现代化 Web 平台',
        ]);

        app(CanonicalUrlManager::class)->apply();
    }
}
