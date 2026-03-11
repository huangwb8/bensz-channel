<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class OtpLoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_request_and_consume_email_login_code(): void
    {
        Config::set('community.auth.driver', 'better_auth');
        Config::set('community.auth.preview_codes', true);
        Config::set('services.better_auth.base_url', 'http://auth:3001');
        Config::set('services.better_auth.internal_secret', 'test-secret');

        Http::fake([
            'http://auth:3001/internal/otp/send' => Http::response([
                'status' => 'sent',
                'previewCode' => '123456',
            ]),
            'http://auth:3001/internal/otp/verify' => Http::response([
                'user' => [
                    'id' => 'auth-user-1',
                    'email' => 'member@example.com',
                    'phone' => null,
                    'name' => '测试成员',
                    'image' => null,
                    'emailVerified' => true,
                    'phoneVerified' => false,
                ],
            ]),
        ]);

        $this->post(route('auth.code.send'), [
            'channel' => 'email',
            'target' => 'member@example.com',
        ])->assertRedirect()
            ->assertSessionHas('otp_preview', '123456');

        $this->post(route('auth.code.verify'), [
            'channel' => 'email',
            'target' => 'member@example.com',
            'code' => '123456',
            'name' => '测试成员',
        ])->assertRedirect(route('home'));

        $this->assertAuthenticated();
        $this->assertDatabaseHas(User::class, [
            'email' => 'member@example.com',
            'name' => '测试成员',
            'user_id' => 101,
        ]);

        Http::assertSentCount(2);
        Http::assertSent(function ($request): bool {
            return $request->url() === 'http://auth:3001/internal/otp/verify'
                && $request['channel'] === 'email'
                && $request['target'] === 'member@example.com'
                && $request['code'] === '123456';
        });
    }

    public function test_banned_user_cannot_login_with_email_code(): void
    {
        Config::set('community.auth.driver', 'better_auth');
        Config::set('community.auth.preview_codes', true);
        Config::set('services.better_auth.base_url', 'http://auth:3001');
        Config::set('services.better_auth.internal_secret', 'test-secret');

        User::factory()->create([
            'email' => 'banned@example.com',
            'banned_at' => now()->subHour(),
            'banned_until' => now()->addDays(3),
        ]);

        Http::fake([
            'http://auth:3001/internal/otp/verify' => Http::response([
                'user' => [
                    'id' => 'auth-user-banned',
                    'email' => 'banned@example.com',
                    'phone' => null,
                    'name' => '封禁成员',
                    'image' => null,
                    'emailVerified' => true,
                    'phoneVerified' => false,
                ],
            ]),
        ]);

        $this->from(route('login'))
            ->post(route('auth.code.verify'), [
                'channel' => 'email',
                'target' => 'banned@example.com',
                'code' => '123456',
                'name' => '封禁成员',
            ])
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors(['login_method']);

        $this->assertGuest();
    }
}
