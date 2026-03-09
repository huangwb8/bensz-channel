<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class BetterAuthLoginTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Config::set('community.auth.driver', 'better_auth');
        Config::set('community.auth.preview_codes', true);
        Config::set('services.better_auth.base_url', 'http://auth:3001');
        Config::set('services.better_auth.internal_secret', 'test-secret');
        Config::set('services.better_auth.timeout', 5);
    }

    public function test_phone_login_uses_better_auth_and_logs_user_in(): void
    {
        Http::fake([
            'http://auth:3001/internal/otp/verify' => Http::response([
                'user' => [
                    'id' => 'auth-user-phone',
                    'email' => null,
                    'phone' => '13800138000',
                    'name' => '手机用户',
                    'image' => null,
                    'emailVerified' => false,
                    'phoneVerified' => true,
                ],
            ]),
        ]);

        $this->post(route('auth.code.verify'), [
            'channel' => 'phone',
            'target' => '138-0013-8000',
            'code' => '654321',
            'name' => '手机用户',
        ])->assertRedirect(route('home'));

        $this->assertAuthenticated();
        $this->assertDatabaseHas(User::class, [
            'phone' => '13800138000',
            'name' => '手机用户',
            'user_id' => 101,
        ]);
    }

    public function test_better_auth_validation_errors_are_exposed_to_user(): void
    {
        Http::fake([
            'http://auth:3001/internal/otp/verify' => Http::response([
                'message' => '验证码不正确或已过期。',
                'errors' => [
                    'code' => ['验证码不正确或已过期。'],
                ],
            ], 422),
        ]);

        $this->from(route('login'))
            ->post(route('auth.code.verify'), [
                'channel' => 'email',
                'target' => 'member@example.com',
                'code' => '123456',
                'name' => '测试成员',
            ])
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors([
                'code' => '验证码不正确或已过期。',
            ]);

        $this->assertGuest();
    }

    public function test_better_auth_connection_failures_are_handled_gracefully(): void
    {
        Http::fake(function () {
            throw new ConnectionException('auth service unavailable');
        });

        $this->from(route('login'))
            ->post(route('auth.code.send'), [
                'channel' => 'email',
                'target' => 'member@example.com',
            ])
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors([
                'target' => '登录服务暂时不可用，请稍后重试。',
            ]);
    }
}
