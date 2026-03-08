<?php

namespace Tests\Feature\Auth;

use App\Models\LoginCode;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OtpLoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_request_and_consume_email_login_code(): void
    {
        $this->post(route('auth.code.send'), [
            'channel' => LoginCode::CHANNEL_EMAIL,
            'target' => 'member@example.com',
        ])->assertRedirect();

        $code = LoginCode::query()->where('target', 'member@example.com')->firstOrFail();

        $this->post(route('auth.code.verify'), [
            'channel' => LoginCode::CHANNEL_EMAIL,
            'target' => 'member@example.com',
            'code' => $code->code,
            'name' => '测试成员',
        ])->assertRedirect(route('home'));

        $this->assertAuthenticated();
    }
}
