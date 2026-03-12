<?php

namespace Tests\Feature\Comments;

use App\Models\Article;
use App\Models\Channel;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CommentPostingTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_post_comment(): void
    {
        [$article] = $this->createArticleFixture();

        $this->post(route('articles.comments.store', $article), [
            'body' => 'guest comment',
        ])->assertRedirect(route('login'));
    }

    public function test_member_can_post_comment(): void
    {
        [$article] = $this->createArticleFixture();
        $member = User::factory()->create(['role' => User::ROLE_MEMBER]);

        $this->actingAs($member)
            ->post(route('articles.comments.store', $article), [
                'body' => '这是一条 **Markdown** 评论',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('comments', [
            'article_id' => $article->id,
            'user_id' => $member->id,
        ]);
    }

    public function test_article_page_does_not_render_channel_subscription_buttons(): void
    {
        [$article] = $this->createArticleFixture();
        $member = User::factory()->create(['role' => User::ROLE_MEMBER]);

        $this->actingAs($member)
            ->get(route('articles.show', [$article->channel, $article]))
            ->assertOk()
            ->assertDontSee('RSS 订阅本版块')
            ->assertDontSee('管理 SMTP 订阅');
    }

    public function test_article_page_renders_clipboard_image_upload_hint_for_logged_in_member(): void
    {
        [$article] = $this->createArticleFixture();
        $member = User::factory()->create(['role' => User::ROLE_MEMBER]);

        $this->actingAs($member)
            ->get(route('articles.show', [$article->channel, $article]))
            ->assertOk()
            ->assertSee(route('uploads.images.store'), false)
            ->assertSee(route('uploads.videos.store'), false)
            ->assertSee('Ctrl', false)
            ->assertSee('粘贴图片或不大于 500MB 的视频', false);
    }

    private function createArticleFixture(): array
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
            'title' => '测试文章',
            'slug' => 'comment-fixture',
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
