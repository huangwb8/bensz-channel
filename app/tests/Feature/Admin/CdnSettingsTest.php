<?php

namespace Tests\Feature\Admin;

use App\Enums\CdnMode;
use App\Models\CdnSyncLog;
use App\Models\SiteSetting;
use App\Models\User;
use App\Support\Cdn\Storage\ConnectionTestResult;
use App\Support\Cdn\Storage\StorageProvider;
use App\Support\Cdn\Storage\StorageProviderFactory;
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
            ->assertSee('保存仅更新草稿')
            ->assertSee('应用 CDN')
            ->assertSee('停止 CDN')
            ->assertSee('工作日志');
    }

    public function test_admin_can_update_origin_cdn_settings_without_immediately_applying_them(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $this->actingAs($admin)
            ->put(route('admin.cdn-settings.update'), [
                'cdn_mode' => CdnMode::ORIGIN->value,
                'cdn_asset_url' => 'https://cdn.example.com/',
                'cdn_sync_enabled' => false,
                'cdn_sync_on_build' => true,
            ])
            ->assertRedirect(route('admin.cdn-settings.index'));

        $setting = SiteSetting::query()->firstOrFail();

        $this->assertNotNull($setting);
        $this->assertSame(CdnMode::ORIGIN, $setting->cdn_mode);
        $this->assertSame('https://cdn.example.com', $setting->cdn_asset_url);
        $this->assertFalse((bool) $setting->cdn_is_active);
        $this->assertNull($setting->cdn_applied_snapshot);

        $log = CdnSyncLog::query()->latest('id')->firstOrFail();
        $this->assertSame('save', $log->trigger);
        $this->assertSame('success', $log->status);
        $this->assertStringContainsString('尚未应用', (string) $log->message);
    }

    public function test_admin_can_update_storage_cdn_settings_and_preserve_existing_credentials_when_left_blank(): void
    {
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
        $this->assertFalse((bool) $setting->cdn_is_active);
        $this->assertNull($setting->cdn_applied_snapshot);
        $this->assertNotSame('existing-access-key', $rawSetting?->cdn_storage_access_key);
        $this->assertNotSame('existing-secret-key', $rawSetting?->cdn_storage_secret_key);
    }

    public function test_admin_can_apply_saved_cdn_configuration_manually(): void
    {
        config([
            'community.static.enabled' => false,
        ]);

        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        SiteSetting::query()->create([
            'cdn_mode' => CdnMode::ORIGIN,
            'cdn_asset_url' => 'https://cdn.example.com',
            'cdn_sync_enabled' => false,
            'cdn_sync_on_build' => true,
        ]);

        $this->actingAs($admin)
            ->postJson(route('admin.cdn-settings.apply'))
            ->assertOk()
            ->assertJson([
                'status' => 'success',
            ]);

        $setting = SiteSetting::query()->firstOrFail();
        $this->assertTrue((bool) $setting->cdn_is_active);
        $this->assertNotNull($setting->cdn_applied_snapshot);
        $this->assertStringContainsString('cdn.example.com', (string) $setting->cdn_applied_snapshot);

        $log = CdnSyncLog::query()->latest('id')->firstOrFail();
        $this->assertSame('apply', $log->trigger);
        $this->assertSame('success', $log->status);
    }

    public function test_admin_can_stop_active_cdn_without_losing_saved_draft(): void
    {
        config([
            'community.static.enabled' => false,
        ]);

        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        SiteSetting::query()->create([
            'cdn_mode' => CdnMode::ORIGIN,
            'cdn_asset_url' => 'https://cdn.example.com',
            'cdn_is_active' => true,
            'cdn_applied_snapshot' => json_encode([
                'mode' => CdnMode::ORIGIN->value,
                'asset_url' => 'https://cdn.example.com',
                'sync_enabled' => false,
                'sync_on_build' => true,
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR),
        ]);

        $this->actingAs($admin)
            ->postJson(route('admin.cdn-settings.stop'))
            ->assertOk()
            ->assertJson([
                'status' => 'success',
            ]);

        $setting = SiteSetting::query()->firstOrFail();
        $this->assertFalse((bool) $setting->cdn_is_active);
        $this->assertSame('https://cdn.example.com', $setting->cdn_asset_url);
        $this->assertNotNull($setting->cdn_applied_snapshot);

        $log = CdnSyncLog::query()->latest('id')->firstOrFail();
        $this->assertSame('stop', $log->trigger);
        $this->assertSame('success', $log->status);
    }

    public function test_failed_connection_test_is_written_to_work_log_with_reason(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $this->app->instance(StorageProviderFactory::class, new class extends StorageProviderFactory
        {
            public function __construct() {}

            public function make(): StorageProvider
            {
                return $this->makeFromConfiguration([]);
            }

            public function makeFromConfiguration(array $configuration): StorageProvider
            {
                return new class implements StorageProvider
                {
                    public function upload(string $localPath, string $remotePath): bool
                    {
                        return true;
                    }

                    public function delete(string $remotePath): bool
                    {
                        return true;
                    }

                    public function exists(string $remotePath): bool
                    {
                        return false;
                    }

                    public function listFiles(string $prefix = ''): array
                    {
                        return [];
                    }

                    public function getPublicUrl(string $remotePath): string
                    {
                        return 'https://assets.example.com/'.ltrim($remotePath, '/');
                    }

                    public function testConnection(): ConnectionTestResult
                    {
                        return new ConnectionTestResult(
                            false,
                            '对象存储连接测试失败：Class "League\\Flysystem\\AwsS3V3\\PortableVisibilityConverter" not found',
                            '异常原因：Class "League\\Flysystem\\AwsS3V3\\PortableVisibilityConverter" not found',
                        );
                    }
                };
            }
        });

        $this->actingAs($admin)
            ->postJson(route('admin.cdn-settings.test'), [
                'cdn_mode' => CdnMode::STORAGE->value,
                'cdn_asset_url' => 'https://assets.example.com',
                'cdn_storage_provider' => 'dogecloud',
                'cdn_storage_access_key' => 'access-key',
                'cdn_storage_secret_key' => 'secret-key',
                'cdn_storage_bucket' => 'bucket-name',
                'cdn_storage_region' => 'auto',
                'cdn_storage_endpoint' => 'https://oss.example.com',
                'cdn_sync_enabled' => true,
                'cdn_sync_on_build' => true,
            ])
            ->assertStatus(422)
            ->assertJson([
                'status' => 'failed',
            ]);

        $log = CdnSyncLog::query()->latest('id')->firstOrFail();
        $this->assertSame('test', $log->trigger);
        $this->assertSame('failed', $log->status);
        $this->assertStringContainsString('PortableVisibilityConverter', (string) $log->message);
        $this->assertStringContainsString('PortableVisibilityConverter', (string) $log->details);
    }
}
