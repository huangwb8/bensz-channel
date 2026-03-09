<?php

namespace Tests\Feature\Auth;

use App\Models\SocialAccount;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SocialOAuthLoginTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Config::set('services.wechat.client_id', 'wx-app-id');
        Config::set('services.wechat.client_secret', 'wx-secret');
        Config::set('services.wechat.redirect', 'http://localhost/auth/social/wechat/callback');
        Config::set('services.qq.client_id', 'qq-app-id');
        Config::set('services.qq.client_secret', 'qq-secret');
        Config::set('services.qq.redirect', 'http://localhost/auth/social/qq/callback');
        Config::set('community.auth.social_providers.wechat.mode', 'oauth');
        Config::set('community.auth.social_providers.qq.mode', 'oauth');
    }

    public function test_wechat_social_redirect_builds_official_authorize_url(): void
    {
        $response = $this->get(route('auth.social.redirect', 'wechat'));

        $response->assertRedirectContains('https://open.weixin.qq.com/connect/qrconnect');
        $response->assertRedirectContains('appid=wx-app-id');
        $response->assertRedirectContains('scope=snsapi_login');

        $this->assertNotEmpty(session('social_oauth_state.wechat'));
    }

    public function test_wechat_callback_logs_user_in_and_persists_social_account(): void
    {
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
                'nickname' => '微信测试用户',
                'headimgurl' => 'https://example.com/wx-avatar.png',
            ]),
        ]);

        $this->get(route('auth.social.redirect', 'wechat'));
        $state = (string) session('social_oauth_state.wechat');

        $this->get(route('auth.social.callback', [
            'provider' => 'wechat',
            'code' => 'wx-auth-code',
            'state' => $state,
        ]))
            ->assertRedirect(route('home'));

        $this->assertAuthenticated();
        $this->assertDatabaseHas(User::class, [
            'name' => '微信测试用户',
            'avatar_url' => 'https://example.com/wx-avatar.png',
            'user_id' => 101,
        ]);
        $this->assertDatabaseHas(SocialAccount::class, [
            'provider' => 'wechat',
            'provider_user_id' => 'wx-union-id',
        ]);
    }

    public function test_qq_social_redirect_builds_official_authorize_url(): void
    {
        $response = $this->get(route('auth.social.redirect', 'qq'));

        $response->assertRedirectContains('https://graph.qq.com/oauth2.0/authorize');
        $response->assertRedirectContains('client_id=qq-app-id');
        $response->assertRedirectContains('scope=get_user_info');

        $this->assertNotEmpty(session('social_oauth_state.qq'));
    }

    public function test_qq_callback_logs_user_in_and_persists_social_account(): void
    {
        Http::fake([
            'https://graph.qq.com/oauth2.0/token*' => Http::response([
                'access_token' => 'qq-access-token',
                'expires_in' => 7776000,
                'refresh_token' => 'qq-refresh-token',
            ]),
            'https://graph.qq.com/oauth2.0/me*' => Http::response([
                'client_id' => 'qq-app-id',
                'openid' => 'qq-open-id',
            ]),
            'https://graph.qq.com/user/get_user_info*' => Http::response([
                'ret' => 0,
                'nickname' => 'QQ测试用户',
                'figureurl_qq_2' => 'https://example.com/qq-avatar.png',
            ]),
        ]);

        $this->get(route('auth.social.redirect', 'qq'));
        $state = (string) session('social_oauth_state.qq');

        $this->get(route('auth.social.callback', [
            'provider' => 'qq',
            'code' => 'qq-auth-code',
            'state' => $state,
        ]))
            ->assertRedirect(route('home'));

        $this->assertAuthenticated();
        $this->assertDatabaseHas(User::class, [
            'name' => 'QQ测试用户',
            'avatar_url' => 'https://example.com/qq-avatar.png',
            'user_id' => 101,
        ]);
        $this->assertDatabaseHas(SocialAccount::class, [
            'provider' => 'qq',
            'provider_user_id' => 'qq-open-id',
        ]);
    }

    public function test_social_callback_rejects_invalid_state(): void
    {
        $this->get(route('auth.social.redirect', 'wechat'));

        $this->get(route('auth.social.callback', [
            'provider' => 'wechat',
            'code' => 'wx-auth-code',
            'state' => 'invalid-state',
        ]))
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors([
                'login_method' => '登录校验失败，请重新发起扫码登录。',
            ]);
    }
}
