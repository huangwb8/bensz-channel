<?php

namespace Tests\Feature\Admin;

use App\Models\SiteSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminSiteSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_site_settings_page(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $this->actingAs($admin)
            ->get(route('admin.site-settings.edit'))
            ->assertOk()
            ->assertSee('站点设置')
            ->assertSee('APP_NAME')
            ->assertSee('SITE_NAME')
            ->assertSee('SITE_TAGLINE')
            ->assertSee('允许用户使用的登录 / 注册方式');
    }

    public function test_admin_can_update_site_settings(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $this->actingAs($admin)
            ->put(route('admin.site-settings.update'), [
                'app_name' => 'Bensz Channel Admin',
                'site_name' => 'Bensz Community',
                'site_tagline' => '一个支持静态游客访问与成员互动的频道式社区。',
                'cdn_asset_url' => 'https://cdn.example.com',
                'auth_enabled_methods' => ['email_code', 'qq_qr'],
            ])
            ->assertRedirect(route('admin.site-settings.edit'));

        $this->assertDatabaseHas(SiteSetting::class, [
            'app_name' => 'Bensz Channel Admin',
            'site_name' => 'Bensz Community',
            'site_tagline' => '一个支持静态游客访问与成员互动的频道式社区。',
        ]);

        $setting = SiteSetting::query()->first();
        $this->assertSame('https://cdn.example.com', $setting?->cdn_asset_url);
        $this->assertSame(['email_code', 'qq_qr'], $setting?->auth_enabled_methods);
    }

    public function test_member_cannot_access_site_settings_page(): void
    {
        $member = User::factory()->create(['role' => User::ROLE_MEMBER]);

        $this->actingAs($member)
            ->get(route('admin.site-settings.edit'))
            ->assertForbidden();
    }
}
