<?php

namespace Tests\Feature\Subscriptions;

use App\Models\Channel;
use App\Models\User;
use App\Notifications\ArticlePublishedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class ArticleEmailSubscriptionTest extends TestCase
{
    use RefreshDatabase;

    public function test_published_article_notifies_members_subscribed_to_all_channels(): void
    {
        Notification::fake();

        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $subscriber = User::factory()->create(['role' => User::ROLE_MEMBER]);
        $channel = Channel::factory()->create(['name' => '公告', 'slug' => 'notice']);

        $this->actingAs($admin)
            ->post(route('admin.articles.store'), [
                'channel_id' => $channel->id,
                'title' => '订阅通知测试',
                'slug' => 'subscription-mail-test',
                'excerpt' => '摘要',
                'markdown_body' => '正文',
                'cover_gradient' => 'from-violet-500 via-fuchsia-500 to-cyan-500',
                'published_at' => now()->format('Y-m-d H:i:s'),
                'is_published' => 1,
            ])
            ->assertRedirect(route('admin.articles.index'));

        Notification::assertSentTo($subscriber, ArticlePublishedNotification::class);
        Notification::assertNotSentTo($admin, ArticlePublishedNotification::class);
    }

    public function test_only_explicit_channel_subscribers_receive_article_email_when_all_channels_disabled(): void
    {
        Notification::fake();

        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $channel = Channel::factory()->create(['name' => '公告', 'slug' => 'notice']);
        $matchedSubscriber = User::factory()->create(['role' => User::ROLE_MEMBER]);
        $unmatchedSubscriber = User::factory()->create(['role' => User::ROLE_MEMBER]);

        $matchedSubscriber->notificationPreference->update(['email_all_articles' => false]);
        $matchedSubscriber->emailChannelSubscriptions()->create(['channel_id' => $channel->id]);
        $unmatchedSubscriber->notificationPreference->update(['email_all_articles' => false]);

        $this->actingAs($admin)
            ->post(route('admin.articles.store'), [
                'channel_id' => $channel->id,
                'title' => '定向通知测试',
                'slug' => 'explicit-channel-mail-test',
                'excerpt' => '摘要',
                'markdown_body' => '正文',
                'cover_gradient' => 'from-violet-500 via-fuchsia-500 to-cyan-500',
                'published_at' => now()->format('Y-m-d H:i:s'),
                'is_published' => 1,
            ])
            ->assertRedirect(route('admin.articles.index'));

        Notification::assertSentTo($matchedSubscriber, ArticlePublishedNotification::class);
        Notification::assertNotSentTo($unmatchedSubscriber, ArticlePublishedNotification::class);
    }

    public function test_updating_existing_published_article_does_not_send_duplicate_notifications(): void
    {
        Notification::fake();

        [$article, $channel, $admin] = $this->createArticleFixture();
        $subscriber = User::factory()->create(['role' => User::ROLE_MEMBER]);

        Notification::assertNothingSent();

        $this->actingAs($admin)
            ->put(route('admin.articles.update', $article), [
                'channel_id' => $channel->id,
                'title' => '更新后的标题',
                'slug' => $article->slug,
                'excerpt' => '摘要',
                'markdown_body' => '更新后的正文',
                'cover_gradient' => 'from-violet-500 via-fuchsia-500 to-cyan-500',
                'published_at' => now()->format('Y-m-d H:i:s'),
                'is_published' => 1,
            ])
            ->assertRedirect(route('admin.articles.index'));

        Notification::assertNotSentTo($subscriber, ArticlePublishedNotification::class);
    }

    private function createArticleFixture(): array
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $channel = Channel::factory()->create(['name' => '公告', 'slug' => 'notice']);
        $article = $admin->articles()->create([
            'channel_id' => $channel->id,
            'title' => '已发布文章',
            'slug' => 'published-article',
            'excerpt' => '摘要',
            'markdown_body' => '正文',
            'html_body' => '<p>正文</p>',
            'is_published' => true,
            'published_at' => now()->subMinute(),
            'cover_gradient' => 'from-violet-500 via-fuchsia-500 to-cyan-500',
        ]);

        return [$article, $channel, $admin];
    }
}
