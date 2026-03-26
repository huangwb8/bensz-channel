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
            ->assertSee('项目时区')
            ->assertSee('Asia/Shanghai')
            ->assertSee('文章图片上传上限')
            ->assertSee('视频上传上限')
            ->assertSee('主题模式')
            ->assertSee('允许用户使用的登录 / 注册方式')
            ->assertSee('CDN 已拆分为独立页面统一管理')
            ->assertDontSee('name="cdn_asset_url"', false);
    }

    public function test_admin_site_settings_page_renders_iconified_management_shortcuts(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $this->actingAs($admin)
            ->get(route('admin.site-settings.edit'))
            ->assertOk()
            ->assertSee('data-tooltip="文章管理"', false)
            ->assertSee('aria-label="文章管理"', false)
            ->assertSee('data-tooltip="频道管理"', false)
            ->assertSee('aria-label="频道管理"', false)
            ->assertSee('data-tooltip="用户管理"', false)
            ->assertSee('aria-label="用户管理"', false)
            ->assertDontSee('<a href="'.route('admin.articles.index').'" class="btn-secondary">文章管理</a>', false)
            ->assertDontSee('<a href="'.route('admin.channels.index').'" class="btn-secondary">频道管理</a>', false)
            ->assertDontSee('<a href="'.route('admin.users.index').'" class="btn-secondary">用户管理</a>', false);
    }

    public function test_admin_site_settings_page_renders_backup_and_restore_section(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $this->actingAs($admin)
            ->get(route('admin.site-settings.edit'))
            ->assertOk()
            ->assertSee('数据备份与恢复')
            ->assertSee('下载 tar.gz 备份')
            ->assertSee('上传 tar.gz 恢复')
            ->assertSee('该备份文件包含敏感数据')
            ->assertSee(route('admin.site-settings.backup.download'), false)
            ->assertSee(route('admin.site-settings.backup.restore'), false);
    }

    public function test_admin_can_download_site_backup_archive(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $response = $this->actingAs($admin)
            ->get(route('admin.site-settings.backup.download'));

        $response->assertOk();

        $contentDisposition = (string) $response->headers->get('content-disposition');

        $this->assertStringContainsString('attachment;', $contentDisposition);
        $this->assertStringContainsString('.tar.gz', $contentDisposition);
    }

    public function test_admin_can_update_site_settings(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        SiteSetting::query()->create([
            'cdn_asset_url' => 'https://cdn.example.com',
            'cdn_mode' => 'origin',
            'cdn_sync_enabled' => true,
            'cdn_sync_on_build' => false,
        ]);

        $this->actingAs($admin)
            ->put(route('admin.site-settings.update'), [
                'app_name' => 'Bensz Channel Admin',
                'site_name' => 'Bensz Community',
                'site_tagline' => '一个支持静态游客访问与成员互动的频道式社区。',
                'timezone' => 'America/New_York',
                'article_image_max_mb' => 32,
                'article_video_max_mb' => 1024,
                'theme_mode' => 'auto',
                'theme_day_start' => '06:30',
                'theme_night_start' => '20:30',
                'auth_enabled_methods' => ['email_code', 'qq_qr'],
            ])
            ->assertRedirect(route('admin.site-settings.edit'));

        $this->assertDatabaseHas(SiteSetting::class, [
            'app_name' => 'Bensz Channel Admin',
            'site_name' => 'Bensz Community',
            'site_tagline' => '一个支持静态游客访问与成员互动的频道式社区。',
            'timezone' => 'America/New_York',
            'theme_mode' => 'auto',
            'theme_day_start' => '06:30',
            'theme_night_start' => '20:30',
            'article_image_max_mb' => 32,
            'article_video_max_mb' => 1024,
        ]);

        $setting = SiteSetting::query()->first();
        $this->assertSame('https://cdn.example.com', $setting?->cdn_asset_url);
        $this->assertSame('origin', $setting?->cdn_mode?->value ?? $setting?->cdn_mode);
        $this->assertTrue((bool) $setting?->cdn_sync_enabled);
        $this->assertFalse((bool) $setting?->cdn_sync_on_build);
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
