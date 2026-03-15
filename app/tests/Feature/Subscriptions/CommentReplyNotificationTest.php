<?php

namespace Tests\Feature\Subscriptions;

use App\Models\Article;
use App\Models\Channel;
use App\Models\Comment;
use App\Models\User;
use App\Notifications\CommentReplyNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class CommentReplyNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_reply_to_comment_sends_email_to_subscribed_comment_author(): void
    {
        Notification::fake();

        [$article] = $this->createArticleFixture();
        $originalAuthor = User::factory()->create([
            'name' => 'Alice',
            'role' => User::ROLE_MEMBER,
        ]);
        $replier = User::factory()->create([
            'name' => 'Bob',
            'role' => User::ROLE_MEMBER,
        ]);

        $comment = Comment::query()->create([
            'article_id' => $article->id,
            'user_id' => $originalAuthor->id,
            'markdown_body' => '欢迎来讨论',
            'html_body' => '<p>欢迎来讨论</p>',
            'is_visible' => true,
        ]);

        $this->actingAs($replier)
            ->post(route('articles.comments.store', $article), [
                'body' => '这是第一条回复',
                'parent_id' => $comment->id,
            ])
            ->assertRedirect();

        Notification::assertSentTo($originalAuthor, CommentReplyNotification::class);
        Notification::assertNotSentTo($replier, CommentReplyNotification::class);
    }

    public function test_comment_reply_notifications_respect_global_preference_and_per_comment_subscription_state(): void
    {
        Notification::fake();

        [$article] = $this->createArticleFixture();
        $originalAuthor = User::factory()->create([
            'name' => 'Alice',
            'role' => User::ROLE_MEMBER,
        ]);
        $originalAuthor->notificationPreference->update([
            'email_comment_replies' => false,
        ]);

        $replier = User::factory()->create([
            'name' => 'Bob',
            'role' => User::ROLE_MEMBER,
        ]);

        $comment = Comment::query()->create([
            'article_id' => $article->id,
            'user_id' => $originalAuthor->id,
            'markdown_body' => '欢迎来讨论',
            'html_body' => '<p>欢迎来讨论</p>',
            'is_visible' => true,
        ]);

        $this->actingAs($replier)
            ->post(route('articles.comments.store', $article), [
                'body' => '关闭全局提醒时不发邮件',
                'parent_id' => $comment->id,
            ])
            ->assertRedirect();

        Notification::assertNotSentTo($originalAuthor, CommentReplyNotification::class);

        $originalAuthor->notificationPreference->update([
            'email_comment_replies' => true,
        ]);

        $this->actingAs($originalAuthor)
            ->delete(route('comments.subscriptions.destroy', $comment))
            ->assertRedirect();

        $this->actingAs($replier)
            ->post(route('articles.comments.store', $article), [
                'body' => '单条评论取消订阅时不发邮件',
                'parent_id' => $comment->id,
            ])
            ->assertRedirect();

        Notification::assertNotSentTo($originalAuthor, CommentReplyNotification::class);

        $this->actingAs($originalAuthor)
            ->post(route('comments.subscriptions.store', $comment))
            ->assertRedirect();

        $this->actingAs($replier)
            ->post(route('articles.comments.store', $article), [
                'body' => '重新订阅后继续收到邮件',
                'parent_id' => $comment->id,
            ])
            ->assertRedirect();

        Notification::assertSentTo($originalAuthor, CommentReplyNotification::class);
    }

    private function createArticleFixture(): array
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $channel = Channel::factory()->create(['name' => '公告', 'slug' => 'notice']);
        $article = Article::query()->create([
            'channel_id' => $channel->id,
            'author_id' => $admin->id,
            'title' => '测试文章',
            'slug' => 'reply-notification-fixture',
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
