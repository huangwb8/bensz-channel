<?php

namespace Tests\Feature\Api\Vibe;

use App\Models\Article;
use App\Models\Channel;
use App\Models\Comment;
use App\Models\DevtoolsApiKey;
use App\Models\User;
use App\Support\ArticleSubscriptionNotifier;
use App\Support\MarkdownRenderer;
use App\Support\StaticPageBuilder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class DevtoolsApiTest extends TestCase
{
    use RefreshDatabase;

    private string $rawKey;

    protected function setUp(): void
    {
        parent::setUp();

        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'email' => 'admin@example.com',
            'phone' => '13800138001',
        ]);

        [, $this->rawKey] = DevtoolsApiKey::generate($admin->id, 'phpunit');

        $staticPageBuilder = Mockery::mock(StaticPageBuilder::class);
        $staticPageBuilder->shouldReceive('buildAll')->andReturnNull();
        $this->app->instance(StaticPageBuilder::class, $staticPageBuilder);

        $markdownRenderer = Mockery::mock(MarkdownRenderer::class);
        $markdownRenderer->shouldReceive('excerpt')->andReturnUsing(
            fn (string $markdown): string => mb_substr(trim($markdown), 0, 60),
        );
        $markdownRenderer->shouldReceive('toHtml')->andReturnUsing(
            fn (string $markdown): string => '<p>' . e($markdown) . '</p>',
        );
        $this->app->instance(MarkdownRenderer::class, $markdownRenderer);

        $notifier = Mockery::mock(ArticleSubscriptionNotifier::class);
        $notifier->shouldReceive('send')->andReturnNull();
        $this->app->instance(ArticleSubscriptionNotifier::class, $notifier);
    }

    public function test_channel_can_be_created_without_slug_and_managed_by_numeric_id(): void
    {
        $create = $this->withHeaders($this->headers())->postJson('/api/vibe/channels', [
            'name' => 'Codex 测试频道',
            'description' => '用于验证 DevTools 频道接口。',
            'accent_color' => '#123abc',
            'icon' => '🧪',
            'sort_order' => 12,
            'show_in_top_nav' => false,
        ]);

        $create->assertCreated();

        $this->assertNotSame('', (string) $create->json('channel.slug'));
        $create->assertJsonPath('channel.show_in_top_nav', false);

        $channelId = $create->json('channel.id');

        $this->withHeaders($this->headers())
            ->putJson("/api/vibe/channels/{$channelId}", [
                'name' => 'Codex 更新频道',
                'show_in_top_nav' => true,
            ])
            ->assertOk()
            ->assertJsonPath('channel.name', 'Codex 更新频道')
            ->assertJsonPath('channel.show_in_top_nav', true);

        $this->withHeaders($this->headers())
            ->deleteJson("/api/vibe/channels/{$channelId}")
            ->assertOk()
            ->assertJsonPath('ok', true);

        $this->assertDatabaseMissing('channels', ['id' => $channelId]);
    }

    public function test_article_endpoints_accept_numeric_id_and_partial_updates(): void
    {
        $channel = $this->createChannel();
        $article = $this->createArticle($channel);

        $this->withHeaders($this->headers())
            ->getJson("/api/vibe/articles/{$article->id}")
            ->assertOk()
            ->assertJsonPath('article.id', $article->id);

        $this->withHeaders($this->headers())
            ->putJson("/api/vibe/articles/{$article->id}", [
                'title' => '仅修改标题',
            ])
            ->assertOk()
            ->assertJsonPath('article.title', '仅修改标题')
            ->assertJsonPath('article.slug', 'test-article');

        $this->withHeaders($this->headers())
            ->deleteJson("/api/vibe/articles/{$article->id}")
            ->assertOk()
            ->assertJsonPath('ok', true);

        $this->assertDatabaseMissing('articles', ['id' => $article->id]);
    }

    public function test_articles_list_honors_false_published_filter(): void
    {
        $channel = $this->createChannel();
        $this->createArticle($channel, ['title' => '已发布文章', 'is_published' => true]);
        $hidden = $this->createArticle($channel, [
            'title' => '未发布文章',
            'slug' => 'draft-article',
            'is_published' => false,
            'published_at' => null,
        ]);

        $response = $this->withHeaders($this->headers())
            ->getJson('/api/vibe/articles?published=false');

        $response->assertOk();
        $response->assertJsonCount(1, 'data');
        $response->assertJsonPath('data.0.id', $hidden->id);
        $response->assertJsonPath('data.0.is_published', false);
    }

    public function test_comments_can_be_filtered_updated_and_deleted(): void
    {
        $channel = $this->createChannel();
        $article = $this->createArticle($channel);
        $member = User::factory()->create();

        Comment::query()->create([
            'article_id' => $article->id,
            'user_id' => $member->id,
            'markdown_body' => '可见评论',
            'html_body' => '<p>可见评论</p>',
            'is_visible' => true,
        ]);

        $hidden = Comment::query()->create([
            'article_id' => $article->id,
            'user_id' => $member->id,
            'markdown_body' => '隐藏评论',
            'html_body' => '<p>隐藏评论</p>',
            'is_visible' => false,
        ]);

        $this->withHeaders($this->headers())
            ->getJson('/api/vibe/comments?visible=false')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $hidden->id)
            ->assertJsonPath('data.0.is_visible', false);

        $this->withHeaders($this->headers())
            ->patchJson("/api/vibe/comments/{$hidden->id}", [
                'is_visible' => true,
            ])
            ->assertOk()
            ->assertJsonPath('comment.is_visible', true);

        $this->withHeaders($this->headers())
            ->deleteJson("/api/vibe/comments/{$hidden->id}")
            ->assertOk()
            ->assertJsonPath('ok', true);

        $this->assertDatabaseMissing('comments', ['id' => $hidden->id]);
    }

    public function test_users_update_accepts_partial_payloads(): void
    {
        $member = User::factory()->create([
            'name' => '待更新成员',
            'email' => 'member@example.com',
            'phone' => '13800138002',
            'role' => User::ROLE_MEMBER,
        ]);

        $this->withHeaders($this->headers())
            ->putJson("/api/vibe/users/{$member->id}", [
                'role' => User::ROLE_ADMIN,
            ])
            ->assertOk()
            ->assertJsonPath('user.user_id', $member->user_id)
            ->assertJsonPath('user.role', User::ROLE_ADMIN)
            ->assertJsonPath('user.name', '待更新成员')
            ->assertJsonPath('user.email', 'member@example.com');

        $member->refresh();

        $this->assertSame(101, $member->user_id);
        $this->assertSame(User::ROLE_ADMIN, $member->role);
        $this->assertSame('待更新成员', $member->name);
        $this->assertSame('member@example.com', $member->email);
        $this->assertSame('13800138002', $member->phone);
    }

    private function headers(): array
    {
        return [
            'Accept' => 'application/json',
            'X-Devtools-Key' => $this->rawKey,
        ];
    }

    private function createChannel(array $overrides = []): Channel
    {
        return Channel::query()->create(array_merge([
            'name' => '测试频道',
            'slug' => 'test-channel',
            'description' => '测试频道描述',
            'accent_color' => '#8b5cf6',
            'icon' => '📢',
            'sort_order' => 1,
            'is_public' => true,
            'show_in_top_nav' => true,
        ], $overrides));
    }

    private function createArticle(Channel $channel, array $overrides = []): Article
    {
        $admin = User::query()->firstOrFail();

        return Article::query()->create(array_merge([
            'channel_id' => $channel->id,
            'author_id' => $admin->id,
            'title' => '测试文章',
            'slug' => 'test-article',
            'excerpt' => '测试摘要',
            'markdown_body' => '测试正文',
            'html_body' => '<p>测试正文</p>',
            'is_published' => true,
            'published_at' => now(),
            'cover_gradient' => 'from-violet-500 via-fuchsia-500 to-cyan-500',
            'comment_count' => 0,
        ], $overrides));
    }
}
