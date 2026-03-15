<?php

namespace Tests\Feature\Settings;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
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
            ->assertSee('两步验证')
            ->assertSee('默认头像风格')
            ->assertSee('上传 JPG 或 PNG 头像')
            ->assertSee('two-factor-setup-shell', false)
            ->assertSee('two-factor-secret-card', false)
            ->assertSee('two-factor-verify-card', false);
    }

    public function test_banned_user_is_redirected_from_account_settings(): void
    {
        $user = User::factory()->create([
            'banned_at' => now()->subHour(),
            'banned_until' => now()->addDay(),
        ]);

        $this->actingAs($user)
            ->get(route('settings.account.edit'))
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors(['login_method']);

        $this->assertGuest();
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
            'avatar_type' => 'external',
            'avatar_style' => 'classic_letter',
            'bio' => 'old bio',
            'email_verified_at' => now(),
            'phone_verified_at' => now(),
        ]);

        $this->actingAs($user)
            ->put(route('settings.account.profile.update'), [
                'name' => '新昵称',
                'email' => 'after@example.com',
                'phone' => '139-0000-0000',
                'avatar_type' => 'external',
                'avatar_style' => 'aurora_ring',
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
        $this->assertSame('external', $user->avatar_type);
        $this->assertSame('aurora_ring', $user->avatar_style);
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
                'avatar_type' => 'generated',
                'avatar_style' => 'pixel_patch',
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
                'avatar_type' => 'generated',
                'avatar_style' => 'classic_letter',
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

    public function test_user_can_switch_to_generated_avatar_style(): void
    {
        $user = User::factory()->create([
            'name' => '风格切换用户',
            'email' => 'member@example.com',
            'phone' => '13800000000',
            'avatar_url' => 'https://example.com/original.png',
            'avatar_type' => 'external',
            'avatar_style' => 'classic_letter',
        ]);

        $this->actingAs($user)
            ->put(route('settings.account.profile.update'), [
                'name' => '风格切换用户',
                'email' => 'member@example.com',
                'phone' => '13800000000',
                'avatar_type' => 'generated',
                'avatar_style' => 'pixel_patch',
                'avatar_url' => '',
                'bio' => '',
            ])
            ->assertRedirect(route('settings.account.edit'));

        $user->refresh();

        $this->assertSame('generated', $user->avatar_type);
        $this->assertSame('pixel_patch', $user->avatar_style);
        $this->assertNull($user->avatar_url);
    }

    public function test_user_can_upload_custom_avatar_image(): void
    {
        Storage::fake('public');

        $user = User::factory()->create([
            'name' => '上传头像用户',
            'email' => 'member@example.com',
            'phone' => '13800000000',
        ]);

        $this->actingAs($user)
            ->put(route('settings.account.profile.update'), [
                'name' => '上传头像用户',
                'email' => 'member@example.com',
                'phone' => '13800000000',
                'avatar_type' => 'uploaded',
                'avatar_style' => 'aurora_ring',
                'avatar_upload' => UploadedFile::fake()->image('avatar.png', 240, 240)->size(900),
                'avatar_url' => '',
                'bio' => '',
            ])
            ->assertRedirect(route('settings.account.edit'));

        $user->refresh();

        $this->assertSame('uploaded', $user->avatar_type);
        $this->assertSame('aurora_ring', $user->avatar_style);
        $this->assertIsString($user->avatar_url);
        $this->assertStringStartsWith('/storage/avatars/', $user->avatar_url);

        $storedPath = ltrim((string) preg_replace('#^/storage/#', '', $user->avatar_url), '/');
        Storage::disk('public')->assertExists($storedPath);
    }

    public function test_avatar_upload_must_be_jpg_or_png_and_smaller_than_one_megabyte(): void
    {
        Storage::fake('public');

        $user = User::factory()->create([
            'email' => 'member@example.com',
            'phone' => '13800000000',
        ]);

        $this->actingAs($user)
            ->from(route('settings.account.edit'))
            ->put(route('settings.account.profile.update'), [
                'name' => $user->name,
                'email' => 'member@example.com',
                'phone' => '13800000000',
                'avatar_type' => 'uploaded',
                'avatar_style' => 'classic_letter',
                'avatar_upload' => UploadedFile::fake()->create('avatar.gif', 1200, 'image/gif'),
                'avatar_url' => '',
                'bio' => '',
            ])
            ->assertRedirect(route('settings.account.edit'))
            ->assertSessionHasErrors('avatar_upload');
    }
}
