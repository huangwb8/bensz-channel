<?php

namespace Tests\Feature\Subscriptions;

use App\Models\Article;
use App\Models\Channel;
use App\Models\User;
use App\Notifications\CommentMentionedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class CommentMentionNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_comment_mention_sends_email_to_mentioned_user(): void
    {
        Notification::fake();

        [$article] = $this->createArticleFixture();
        $author = User::factory()->create(['name' => 'Alice', 'role' => User::ROLE_MEMBER]);
        $mentioned = User::factory()->create(['name' => 'Bob', 'role' => User::ROLE_MEMBER]);

        $this->actingAs($author)
            ->post(route('articles.comments.store', $article), [
                'body' => '欢迎 @Bob 来看看这篇文章',
            ])
            ->assertRedirect();

        Notification::assertSentTo($mentioned, CommentMentionedNotification::class);
        Notification::assertNotSentTo($author, CommentMentionedNotification::class);
    }

    public function test_comment_mention_respects_preference_and_email_availability(): void
    {
        Notification::fake();

        [$article] = $this->createArticleFixture();
        $author = User::factory()->create(['name' => 'Alice', 'role' => User::ROLE_MEMBER]);
        $disabledUser = User::factory()->create(['name' => 'Bob', 'role' => User::ROLE_MEMBER]);
        $disabledUser->notificationPreference->update(['email_mentions' => false]);
        $noEmailUser = User::factory()->create([
            'name' => 'Charlie',
            'role' => User::ROLE_MEMBER,
            'email' => null,
            'email_verified_at' => null,
        ]);

        $this->actingAs($author)
            ->post(route('articles.comments.store', $article), [
                'body' => '欢迎 @Bob 和 @Charlie 一起看看',
            ])
            ->assertRedirect();

        Notification::assertNotSentTo($disabledUser, CommentMentionedNotification::class);
        Notification::assertNotSentTo($noEmailUser, CommentMentionedNotification::class);
    }

    private function createArticleFixture(): array
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $channel = Channel::factory()->create(['name' => '公告', 'slug' => 'notice']);
        $article = Article::query()->create([
            'channel_id' => $channel->id,
            'author_id' => $admin->id,
            'title' => '测试文章',
            'slug' => 'mention-fixture',
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
