<?php

namespace Tests\Feature\Auth;

use App\Models\QrLoginRequest;
use App\Models\SiteSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthPagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_page_can_be_rendered(): void
    {
        $this->get(route('login'))
            ->assertOk()
            ->assertSee('登录 / 注册')
            ->assertSee('欢迎回来')
            ->assertSee('Better Auth')
            ->assertSee('选择登录方式')
            ->assertSeeInOrder(['邮箱 + 验证码', '邮箱 + 密码', '微信扫码', 'QQ扫码'])
            ->assertSee('生成微信演示二维码')
            ->assertSee('生成 QQ 演示二维码')
            ->assertDontSee('测试账号')
            ->assertDontSee(config('community.admin.email'))
            ->assertDontSee(config('community.admin.password'))
            ->assertDontSee('member@example.com')
            ->assertDontSee('member123456');
    }

    public function test_login_page_only_shows_enabled_methods_from_site_settings(): void
    {
        SiteSetting::query()->create([
            'auth_enabled_methods' => ['email_code', 'qq_qr'],
        ]);

        $this->get(route('login'))
            ->assertOk()
            ->assertSee('邮箱 + 验证码')
            ->assertSee('QQ扫码')
            ->assertDontSee('邮箱 + 密码')
            ->assertDontSee('微信扫码');
    }

    public function test_qr_login_pages_can_be_rendered(): void
    {
        $this->post(route('auth.qr.start', 'wechat'))
            ->assertRedirect();

        $request = QrLoginRequest::query()->firstOrFail();

        $this->get(route('auth.qr.show', $request))
            ->assertOk()
            ->assertSee('微信扫码登录');

        $this->get(route('auth.qr.approve.show', ['wechat', $request]))
            ->assertOk()
            ->assertSee('微信授权确认');
    }

    public function test_social_redirect_route_uses_demo_flow_by_default(): void
    {
        $response = $this->get(route('auth.social.redirect', 'wechat'));

        $response->assertRedirect();

        $request = QrLoginRequest::query()->firstOrFail();

        $this->assertSame('wechat', $request->provider);
        $response->assertRedirect(route('auth.qr.show', $request));
    }
}
