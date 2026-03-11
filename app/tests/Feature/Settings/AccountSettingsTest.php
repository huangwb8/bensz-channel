<?php

namespace Tests\Feature\Settings;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AccountSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_view_account_settings_page(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('settings.account.edit'))
            ->assertOk()
            ->assertSee('账户设置')
            ->assertSee('基本资料')
            ->assertSee('密码设置')
            ->assertSee('两步验证');
    }

    public function test_home_menu_exposes_account_settings_entry_for_authenticated_user(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('home'))
            ->assertOk()
            ->assertSee('账户设置');
    }

    public function test_user_can_update_own_account_profile(): void
    {
        $user = User::factory()->create([
            'name' => '旧昵称',
            'email' => 'before@example.com',
            'phone' => '13800000000',
            'avatar_url' => 'https://example.com/old.png',
            'bio' => 'old bio',
            'email_verified_at' => now(),
            'phone_verified_at' => now(),
        ]);

        $this->actingAs($user)
            ->put(route('settings.account.profile.update'), [
                'name' => '新昵称',
                'email' => 'after@example.com',
                'phone' => '139-0000-0000',
                'avatar_url' => 'https://example.com/avatar.png',
                'bio' => '新的个人简介',
            ])
            ->assertRedirect(route('settings.account.edit'));

        $user->refresh();

        $this->assertSame(101, $user->user_id);
        $this->assertSame('新昵称', $user->name);
        $this->assertSame('after@example.com', $user->email);
        $this->assertSame('13900000000', $user->phone);
        $this->assertSame('https://example.com/avatar.png', $user->avatar_url);
        $this->assertSame('新的个人简介', $user->bio);
        $this->assertNull($user->email_verified_at);
        $this->assertNull($user->phone_verified_at);
    }

    public function test_profile_updates_do_not_change_stable_user_id(): void
    {
        $user = User::factory()->create([
            'email' => 'member@example.com',
            'phone' => '13800000000',
        ]);

        $originalUserId = $user->user_id;

        $this->actingAs($user)
            ->put(route('settings.account.profile.update'), [
                'name' => '新昵称',
                'email' => 'after@example.com',
                'phone' => '13900000000',
                'avatar_url' => '',
                'bio' => '新的个人简介',
            ])
            ->assertRedirect(route('settings.account.edit'));

        $this->assertSame($originalUserId, $user->fresh()->user_id);
    }

    public function test_user_must_keep_at_least_one_login_identifier(): void
    {
        $user = User::factory()->create([
            'email' => 'member@example.com',
            'phone' => '13800000000',
        ]);

        $this->actingAs($user)
            ->from(route('settings.account.edit'))
            ->put(route('settings.account.profile.update'), [
                'name' => $user->name,
                'email' => '',
                'phone' => '',
                'avatar_url' => '',
                'bio' => '',
            ])
            ->assertRedirect(route('settings.account.edit'));

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'email' => 'member@example.com',
            'phone' => '13800000000',
        ]);
    }

    public function test_user_can_change_password_with_current_password(): void
    {
        $user = User::factory()->create([
            'email' => 'member@example.com',
            'password' => 'old-password',
        ]);

        $this->actingAs($user)
            ->put(route('settings.account.password.update'), [
                'current_password' => 'old-password',
                'password' => 'new-password-123',
                'password_confirmation' => 'new-password-123',
            ])
            ->assertRedirect(route('settings.account.edit'));

        $this->assertTrue(Hash::check('new-password-123', $user->fresh()->password));
    }

    public function test_user_without_existing_password_can_set_password_after_binding_email(): void
    {
        $user = User::factory()->create([
            'email' => 'member@example.com',
            'password' => null,
        ]);

        $this->actingAs($user)
            ->put(route('settings.account.password.update'), [
                'password' => 'first-password-123',
                'password_confirmation' => 'first-password-123',
            ])
            ->assertRedirect(route('settings.account.edit'));

        $this->assertTrue(Hash::check('first-password-123', $user->fresh()->password));
    }

    public function test_user_without_email_cannot_set_password_login(): void
    {
        $user = User::factory()->create([
            'email' => null,
            'phone' => '13800000000',
            'password' => null,
        ]);

        $this->actingAs($user)
            ->from(route('settings.account.edit'))
            ->put(route('settings.account.password.update'), [
                'password' => 'first-password-123',
                'password_confirmation' => 'first-password-123',
            ])
            ->assertRedirect(route('settings.account.edit'));

        $this->assertNull($user->fresh()->password);
    }
}
