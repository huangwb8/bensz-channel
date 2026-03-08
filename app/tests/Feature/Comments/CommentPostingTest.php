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
