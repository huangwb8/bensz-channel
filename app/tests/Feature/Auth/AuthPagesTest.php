<?php

namespace Tests\Feature\Auth;

use App\Models\QrLoginRequest;
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
            ->assertSee('邮箱或手机号登录');
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
}
