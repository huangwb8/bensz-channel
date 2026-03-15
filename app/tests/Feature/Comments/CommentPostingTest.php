<?php

namespace Tests\Feature\Comments;

use App\Models\Article;
use App\Models\Channel;
use App\Models\Comment;
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

    public function test_member_can_reply_to_existing_comment(): void
    {
        [$article] = $this->createArticleFixture();
        $originalAuthor = User::factory()->create([
            'name' => '原评论作者',
            'role' => User::ROLE_MEMBER,
        ]);
        $replier = User::factory()->create([
            'name' => '回复者',
            'role' => User::ROLE_MEMBER,
        ]);

        $comment = Comment::query()->create([
            'article_id' => $article->id,
            'user_id' => $originalAuthor->id,
            'markdown_body' => '第一条评论',
            'html_body' => '<p>第一条评论</p>',
            'is_visible' => true,
        ]);

        $this->actingAs($replier)
            ->post(route('articles.comments.store', $article), [
                'body' => '这是对第一条评论的回复',
                'parent_id' => $comment->id,
            ])
            ->assertRedirect();

        $reply = Comment::query()
            ->where('article_id', $article->id)
            ->where('user_id', $replier->id)
            ->latest('id')
            ->first();

        $this->assertNotNull($reply);
        $this->assertSame($comment->id, $reply->parent_id);
        $this->assertSame($comment->id, $reply->root_id);
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

    public function test_article_page_renders_reply_actions_and_comment_subscription_controls(): void
    {
        [$article] = $this->createArticleFixture();
        $member = User::factory()->create([
            'name' => '评论用户',
            'role' => User::ROLE_MEMBER,
        ]);

        Comment::query()->create([
            'article_id' => $article->id,
            'user_id' => $member->id,
            'markdown_body' => '可以继续讨论',
            'html_body' => '<p>可以继续讨论</p>',
            'is_visible' => true,
        ]);

        $this->actingAs($member)
            ->get(route('articles.show', [$article->channel, $article]))
            ->assertOk()
            ->assertSee('回复这条评论')
            ->assertSee('暂停此评论后续提醒');
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
