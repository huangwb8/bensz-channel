<?php

namespace Tests\Feature\Notifications;

use App\Models\Article;
use App\Models\Channel;
use App\Models\User;
use App\Notifications\AdminCommentPostedNotification;
use App\Notifications\AdminNewUserRegisteredNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class AdminActivityNotificationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Config::set('community.auth.driver', 'better_auth');
        Config::set('community.auth.preview_codes', true);
        Config::set('services.better_auth.base_url', 'http://auth:3001');
        Config::set('services.better_auth.internal_secret', 'test-secret');
        Config::set('community.admin.email', 'ops@example.com');
        Config::set('community.admin.name', '运营管理员');
    }

    public function test_new_user_registration_via_email_code_notifies_admin(): void
    {
        Notification::fake();

        Http::fake([
            'http://auth:3001/internal/otp/verify' => Http::response([
                'user' => [
                    'id' => 'auth-new-user',
                    'email' => 'new-member@example.com',
                    'phone' => null,
                    'name' => '新成员',
                    'image' => null,
                    'emailVerified' => true,
                    'phoneVerified' => false,
                ],
            ]),
        ]);

        $this->post(route('auth.code.verify'), [
            'channel' => 'email',
            'target' => 'new-member@example.com',
            'code' => '123456',
            'name' => '新成员',
        ])->assertRedirect(route('home'));

        Notification::assertSentOnDemand(AdminNewUserRegisteredNotification::class, function (
            AdminNewUserRegisteredNotification $notification,
            array $channels,
            object $notifiable,
        ): bool {
            return $notifiable->routeNotificationFor('mail', $notification) === 'ops@example.com';
        });
    }

    public function test_existing_user_login_does_not_repeat_admin_registration_notification(): void
    {
        Notification::fake();

        User::factory()->create([
            'email' => 'existing@example.com',
            'name' => '老成员',
            'role' => User::ROLE_MEMBER,
        ]);

        Http::fake([
            'http://auth:3001/internal/otp/verify' => Http::response([
                'user' => [
                    'id' => 'auth-existing-user',
                    'email' => 'existing@example.com',
                    'phone' => null,
                    'name' => '老成员',
                    'image' => null,
                    'emailVerified' => true,
                    'phoneVerified' => false,
                ],
            ]),
        ]);

        $this->post(route('auth.code.verify'), [
            'channel' => 'email',
            'target' => 'existing@example.com',
            'code' => '123456',
            'name' => '老成员',
        ])->assertRedirect(route('home'));

        Notification::assertSentOnDemandTimes(AdminNewUserRegisteredNotification::class, 0);
    }

    public function test_new_social_user_registration_notifies_admin(): void
    {
        Notification::fake();

        Config::set('services.wechat.client_id', 'wx-app-id');
        Config::set('services.wechat.client_secret', 'wx-secret');
        Config::set('services.wechat.redirect', 'http://localhost/auth/social/wechat/callback');
        Config::set('community.auth.social_providers.wechat.mode', 'oauth');

        Http::fake([
            'https://api.weixin.qq.com/sns/oauth2/access_token*' => Http::response([
                'access_token' => 'wx-access-token',
                'expires_in' => 7200,
                'refresh_token' => 'wx-refresh-token',
                'openid' => 'wx-open-id',
                'scope' => 'snsapi_login',
                'unionid' => 'wx-union-id',
            ]),
            'https://api.weixin.qq.com/sns/userinfo*' => Http::response([
                'openid' => 'wx-open-id',
                'unionid' => 'wx-union-id',
                'nickname' => '微信新成员',
                'headimgurl' => 'https://example.com/wx-avatar.png',
            ]),
        ]);

        $this->get(route('auth.social.redirect', 'wechat'));
        $state = (string) session('social_oauth_state.wechat');

        $this->get(route('auth.social.callback', [
            'provider' => 'wechat',
            'code' => 'wx-auth-code',
            'state' => $state,
        ]))->assertRedirect(route('home'));

        Notification::assertSentOnDemand(AdminNewUserRegisteredNotification::class, function (
            AdminNewUserRegisteredNotification $notification,
            array $channels,
            object $notifiable,
        ): bool {
            return $notifiable->routeNotificationFor('mail', $notification) === 'ops@example.com';
        });
    }

    public function test_posting_comment_notifies_admin(): void
    {
        Notification::fake();

        [$article] = $this->createArticleFixture();
        $member = User::factory()->create([
            'name' => '评论成员',
            'role' => User::ROLE_MEMBER,
        ]);

        $this->actingAs($member)
            ->post(route('articles.comments.store', $article), [
                'body' => '管理员应该收到这条评论通知',
            ])
            ->assertRedirect();

        Notification::assertSentOnDemand(AdminCommentPostedNotification::class, function (
            AdminCommentPostedNotification $notification,
            array $channels,
            object $notifiable,
        ): bool {
            return $notifiable->routeNotificationFor('mail', $notification) === 'ops@example.com';
        });
    }

    private function createArticleFixture(): array
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $channel = Channel::factory()->create(['name' => '公告', 'slug' => 'notice']);
        $article = Article::query()->create([
            'channel_id' => $channel->id,
            'author_id' => $admin->id,
            'title' => '测试文章',
            'slug' => 'admin-activity-comment-fixture',
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
