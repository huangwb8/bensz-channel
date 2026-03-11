<?php

namespace Tests\Feature\Admin;

use App\Enums\CdnMode;
use App\Models\SiteSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class CdnSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_cdn_settings_page(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $this->actingAs($admin)
            ->get(route('admin.cdn-settings.index'))
            ->assertOk()
            ->assertSee('CDN 设置')
            ->assertSee('回源型 CDN')
            ->assertSee('对象存储型 CDN')
            ->assertSee('测试连接')
            ->assertSee('立即同步');
    }

    public function test_admin_can_update_origin_cdn_settings(): void
    {
        config(['community.static.enabled' => false]);

        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $this->actingAs($admin)
            ->put(route('admin.cdn-settings.update'), [
                'cdn_mode' => CdnMode::ORIGIN->value,
                'cdn_asset_url' => 'https://cdn.example.com/',
                'cdn_sync_enabled' => false,
                'cdn_sync_on_build' => true,
            ])
            ->assertRedirect(route('admin.cdn-settings.index'));

        $setting = SiteSetting::query()->first();

        $this->assertNotNull($setting);
        $this->assertSame(CdnMode::ORIGIN, $setting->cdn_mode);
        $this->assertSame('https://cdn.example.com', $setting->cdn_asset_url);
        $this->assertFalse((bool) $setting->cdn_sync_enabled);
        $this->assertTrue((bool) $setting->cdn_sync_on_build);
        $this->assertSame((int) config('community.uploads.article_image_max_mb', 50), $setting->article_image_max_mb);
    }

    public function test_admin_can_update_storage_cdn_settings_and_preserve_existing_credentials_when_left_blank(): void
    {
        config(['community.static.enabled' => false]);

        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        SiteSetting::query()->create([
            'cdn_mode' => CdnMode::STORAGE,
            'cdn_asset_url' => 'https://assets.old.example.com',
            'cdn_storage_provider' => 'dogecloud',
            'cdn_storage_access_key' => 'existing-access-key',
            'cdn_storage_secret_key' => 'existing-secret-key',
            'cdn_storage_bucket' => 'old-bucket',
            'cdn_storage_region' => 'cn-east-1',
            'cdn_storage_endpoint' => 'https://oss.old.example.com',
            'cdn_sync_enabled' => true,
            'cdn_sync_on_build' => true,
        ]);

        $this->actingAs($admin)
            ->put(route('admin.cdn-settings.update'), [
                'cdn_mode' => CdnMode::STORAGE->value,
                'cdn_asset_url' => 'https://assets.example.com',
                'cdn_storage_provider' => 'dogecloud',
                'cdn_storage_access_key' => '',
                'cdn_storage_secret_key' => '',
                'cdn_storage_bucket' => 'new-bucket',
                'cdn_storage_region' => 'auto',
                'cdn_storage_endpoint' => 'https://oss.example.com',
                'cdn_sync_enabled' => true,
                'cdn_sync_on_build' => false,
            ])
            ->assertRedirect(route('admin.cdn-settings.index'));

        $setting = SiteSetting::query()->firstOrFail();
        $rawSetting = DB::table('site_settings')->first();

        $this->assertSame(CdnMode::STORAGE, $setting->cdn_mode);
        $this->assertSame('https://assets.example.com', $setting->cdn_asset_url);
        $this->assertSame('dogecloud', $setting->cdn_storage_provider);
        $this->assertSame('existing-access-key', $setting->cdn_storage_access_key);
        $this->assertSame('existing-secret-key', $setting->cdn_storage_secret_key);
        $this->assertSame('new-bucket', $setting->cdn_storage_bucket);
        $this->assertSame('auto', $setting->cdn_storage_region);
        $this->assertSame('https://oss.example.com', $setting->cdn_storage_endpoint);
        $this->assertTrue((bool) $setting->cdn_sync_enabled);
        $this->assertFalse((bool) $setting->cdn_sync_on_build);
        $this->assertNotSame('existing-access-key', $rawSetting?->cdn_storage_access_key);
        $this->assertNotSame('existing-secret-key', $rawSetting?->cdn_storage_secret_key);
    }
}
