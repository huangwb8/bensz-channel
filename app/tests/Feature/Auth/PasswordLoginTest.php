<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PasswordLoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_login_with_email_and_password(): void
    {
        $user = User::factory()->create([
            'email' => 'member@example.com',
            'password' => 'secret123456',
        ]);

        $this->post(route('auth.password.login'), [
            'login_method' => 'email-password',
            'email' => 'member@example.com',
            'password' => 'secret123456',
        ])->assertRedirect(route('home'));

        $this->assertAuthenticatedAs($user);
    }

    public function test_password_login_returns_generic_error_for_invalid_credentials(): void
    {
        User::factory()->create([
            'email' => 'member@example.com',
            'password' => 'secret123456',
        ]);

        $this->from(route('login'))
            ->post(route('auth.password.login'), [
                'login_method' => 'email-password',
                'email' => 'member@example.com',
                'password' => 'wrong-password',
            ])
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors([
                'email' => '邮箱或密码不正确。',
            ]);

        $this->assertGuest();
    }
}
