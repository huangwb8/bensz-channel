<?php

namespace Tests\Feature\Subscriptions;

use App\Models\Channel;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SubscriptionSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_new_user_has_default_email_subscription_preferences(): void
    {
        $user = User::factory()->create();

        $this->assertDatabaseHas('user_notification_preferences', [
            'user_id' => $user->id,
            'email_all_articles' => true,
            'email_mentions' => true,
            'email_comment_replies' => true,
        ]);
    }

    public function test_member_only_sees_subscription_controls(): void
    {
        $member = User::factory()->create(['role' => User::ROLE_MEMBER]);

        $this->actingAs($member)
            ->get(route('settings.subscriptions.edit'))
            ->assertOk()
            ->assertSee('SMTP 邮件提醒')
            ->assertSee('接收评论回复提醒')
            ->assertDontSee('管理员 SMTP 配置')
            ->assertDontSee('SMTP 服务器');
    }

    public function test_member_can_update_subscription_preferences(): void
    {
        $member = User::factory()->create(['role' => User::ROLE_MEMBER]);
        $engineering = Channel::factory()->create(['name' => '开发交流', 'slug' => 'engineering']);
        $feedback = Channel::factory()->create(['name' => '反馈建议', 'slug' => 'feedback']);

        $this->actingAs($member)
            ->put(route('settings.subscriptions.update'), [
                'email_all_articles' => false,
                'email_mentions' => false,
                'email_comment_replies' => false,
                'channel_ids' => [$engineering->id],
            ])
            ->assertRedirect(route('settings.subscriptions.edit'));

        $this->assertDatabaseHas('user_notification_preferences', [
            'user_id' => $member->id,
            'email_all_articles' => false,
            'email_mentions' => false,
            'email_comment_replies' => false,
        ]);

        $this->assertDatabaseHas('channel_email_subscriptions', [
            'user_id' => $member->id,
            'channel_id' => $engineering->id,
        ]);

        $this->assertDatabaseMissing('channel_email_subscriptions', [
            'user_id' => $member->id,
            'channel_id' => $feedback->id,
        ]);
    }
}
