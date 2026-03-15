<?php

namespace Tests\Feature\Admin;

use App\Models\Article;
use App\Models\Channel;
use App\Models\Comment;
use App\Models\User;
use App\Support\StaticPageBuilder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class AdminCommentManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_comment_management_page_and_filter_comments(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        [$article] = $this->createArticleFixture();
        $author = User::factory()->create([
            'name' => '评论作者',
            'role' => User::ROLE_MEMBER,
        ]);

        Comment::query()->create([
            'article_id' => $article->id,
            'user_id' => $author->id,
            'markdown_body' => '第一条可见评论',
            'html_body' => '<p>第一条可见评论</p>',
            'is_visible' => true,
        ]);

        Comment::query()->create([
            'article_id' => $article->id,
            'user_id' => $author->id,
            'markdown_body' => '第二条隐藏评论',
            'html_body' => '<p>第二条隐藏评论</p>',
            'is_visible' => false,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.comments.index', [
                'q' => '隐藏',
                'visibility' => 'hidden',
            ]))
            ->assertOk()
            ->assertSee('评论管理')
            ->assertSee('第二条隐藏评论')
            ->assertDontSee('第一条可见评论')
            ->assertSee('仅隐藏')
            ->assertSee('评论作者')
            ->assertSee($article->title)
            ->assertSee('回复评论');
    }

    public function test_admin_can_reply_from_comment_management_page_and_return_back(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        [$article] = $this->createArticleFixture();
        $member = User::factory()->create([
            'name' => '普通成员',
            'role' => User::ROLE_MEMBER,
        ]);

        $comment = Comment::query()->create([
            'article_id' => $article->id,
            'user_id' => $member->id,
            'markdown_body' => '需要后台回复的评论',
            'html_body' => '<p>需要后台回复的评论</p>',
            'is_visible' => true,
        ]);

        $this->actingAs($admin)
            ->from(route('admin.comments.index'))
            ->post(route('articles.comments.store', $article), [
                'body' => '管理员在后台直接回复',
                'parent_id' => $comment->id,
                'redirect_back' => '1',
            ])
            ->assertRedirect(route('admin.comments.index'));

        $this->assertDatabaseHas('comments', [
            'article_id' => $article->id,
            'user_id' => $admin->id,
            'parent_id' => $comment->id,
            'markdown_body' => '管理员在后台直接回复',
        ]);
    }

    public function test_admin_can_toggle_comment_visibility_and_refresh_visible_comment_count(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        [$article] = $this->createArticleFixture();
        $commentAuthor = User::factory()->create(['role' => User::ROLE_MEMBER]);

        $comment = Comment::query()->create([
            'article_id' => $article->id,
            'user_id' => $commentAuthor->id,
            'markdown_body' => '需要隐藏的评论',
            'html_body' => '<p>需要隐藏的评论</p>',
            'is_visible' => true,
        ]);

        $article->update(['comment_count' => 1]);

        $staticPageBuilder = Mockery::mock(StaticPageBuilder::class);
        $staticPageBuilder->shouldReceive('rebuildAfterComment')
            ->once()
            ->withArgs(fn (Article $rebuiltArticle): bool => $rebuiltArticle->is($article));
        $this->app->instance(StaticPageBuilder::class, $staticPageBuilder);

        $this->actingAs($admin)
            ->patch(route('admin.comments.visibility', $comment), [
                'is_visible' => '0',
            ])
            ->assertRedirect(route('admin.comments.index'));

        $this->assertDatabaseHas('comments', [
            'id' => $comment->id,
            'is_visible' => false,
        ]);
        $this->assertSame(0, $article->fresh()->comment_count);
    }

    public function test_admin_can_delete_comment_and_refresh_visible_comment_count(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        [$article] = $this->createArticleFixture();
        $commentAuthor = User::factory()->create(['role' => User::ROLE_MEMBER]);

        $comment = Comment::query()->create([
            'article_id' => $article->id,
            'user_id' => $commentAuthor->id,
            'markdown_body' => '待删除评论',
            'html_body' => '<p>待删除评论</p>',
            'is_visible' => true,
        ]);

        $article->update(['comment_count' => 1]);

        $staticPageBuilder = Mockery::mock(StaticPageBuilder::class);
        $staticPageBuilder->shouldReceive('rebuildAfterComment')
            ->once()
            ->withArgs(fn (Article $rebuiltArticle): bool => $rebuiltArticle->is($article));
        $this->app->instance(StaticPageBuilder::class, $staticPageBuilder);

        $this->actingAs($admin)
            ->delete(route('admin.comments.destroy', $comment))
            ->assertRedirect(route('admin.comments.index'));

        $this->assertDatabaseMissing('comments', [
            'id' => $comment->id,
        ]);
        $this->assertSame(0, $article->fresh()->comment_count);
    }

    private function createArticleFixture(): array
    {
        $author = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $channel = Channel::factory()->create([
            'name' => '公告',
            'slug' => 'notice',
        ]);
        $article = Article::query()->create([
            'channel_id' => $channel->id,
            'author_id' => $author->id,
            'title' => '评论管理测试文章',
            'slug' => 'admin-comment-management-fixture',
            'excerpt' => '摘要',
            'markdown_body' => '正文',
            'html_body' => '<p>正文</p>',
            'is_published' => true,
            'published_at' => now(),
            'cover_gradient' => 'from-violet-500 via-fuchsia-500 to-cyan-500',
            'comment_count' => 0,
        ]);

        return [$article, $channel, $author];
    }
}
