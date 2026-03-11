<?php

namespace Tests\Unit\Support;

use App\Enums\CdnMode;
use App\Models\SiteSetting;
use App\Support\Cdn\CdnSyncService;
use App\Support\Cdn\Storage\StorageProvider;
use App\Support\Cdn\Storage\StorageProviderFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class CdnSyncServiceTest extends TestCase
{
    use RefreshDatabase;

    private string $testDirectory = 'test-cdn-sync';

    protected function tearDown(): void
    {
        File::deleteDirectory(public_path($this->testDirectory));
        File::delete(storage_path('app/cdn-sync-manifest.json'));

        parent::tearDown();
    }

    public function test_sync_service_uploads_changed_files_and_deletes_removed_files(): void
    {
        SiteSetting::query()->create([
            'cdn_mode' => CdnMode::STORAGE,
            'cdn_asset_url' => 'https://assets.example.com',
            'cdn_storage_provider' => 'dogecloud',
            'cdn_storage_access_key' => 'access-key',
            'cdn_storage_secret_key' => 'secret-key',
            'cdn_storage_bucket' => 'bucket-name',
            'cdn_storage_region' => 'auto',
            'cdn_storage_endpoint' => 'https://oss.example.com',
            'cdn_sync_enabled' => true,
            'cdn_sync_on_build' => true,
        ]);

        config([
            'cdn.mode' => CdnMode::STORAGE->value,
            'cdn.sync.enabled' => true,
            'cdn.sync.directories' => [$this->testDirectory],
            'cdn.sync.exclude_patterns' => [],
        ]);

        File::ensureDirectoryExists(public_path($this->testDirectory.'/assets'));
        File::put(public_path($this->testDirectory.'/assets/app.js'), 'console.log("v1");');

        $provider = new class implements StorageProvider
        {
            public array $uploaded = [];

            public array $deleted = [];

            public array $remote = [];

            public function upload(string $localPath, string $remotePath): bool
            {
                $this->uploaded[] = $remotePath;
                $this->remote[$remotePath] = file_get_contents($localPath) ?: '';

                return true;
            }

            public function delete(string $remotePath): bool
            {
                $this->deleted[] = $remotePath;
                unset($this->remote[$remotePath]);

                return true;
            }

            public function exists(string $remotePath): bool
            {
                return array_key_exists($remotePath, $this->remote);
            }

            public function listFiles(string $prefix = ''): array
            {
                return array_values(array_filter(array_keys($this->remote), static fn (string $path): bool => str_starts_with($path, $prefix)));
            }

            public function getPublicUrl(string $remotePath): string
            {
                return 'https://assets.example.com/'.ltrim($remotePath, '/');
            }

            public function validateCredentials(): bool
            {
                return true;
            }
        };

        $this->app->instance(StorageProviderFactory::class, new class($provider) extends StorageProviderFactory
        {
            public function __construct(private readonly StorageProvider $provider) {}

            public function make(): StorageProvider
            {
                return $this->provider;
            }
        });

        $service = app(CdnSyncService::class);

        $first = $service->syncAll('manual');

        $this->assertTrue($first->successful());
        $this->assertSame(1, $first->uploadedCount);
        $this->assertSame(0, $first->deletedCount);
        $this->assertSame([$this->testDirectory.'/assets/app.js'], $provider->uploaded);

        $second = $service->syncAll('manual');

        $this->assertTrue($second->successful());
        $this->assertSame(0, $second->uploadedCount);
        $this->assertSame(0, $second->deletedCount);

        File::delete(public_path($this->testDirectory.'/assets/app.js'));

        $third = $service->syncAll('manual');

        $this->assertTrue($third->successful());
        $this->assertSame(0, $third->uploadedCount);
        $this->assertSame(1, $third->deletedCount);
        $this->assertSame([$this->testDirectory.'/assets/app.js'], $provider->deleted);
    }
}
