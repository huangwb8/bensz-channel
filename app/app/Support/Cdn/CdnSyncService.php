<?php

namespace App\Support\Cdn;

use App\Enums\CdnMode;
use App\Models\CdnSyncLog;
use App\Support\Cdn\Storage\StorageProvider;
use App\Support\Cdn\Storage\StorageProviderFactory;
use Carbon\CarbonImmutable;
use Illuminate\Filesystem\Filesystem;
use RuntimeException;
use Throwable;

class CdnSyncService
{
    public function __construct(
        private readonly CdnManager $cdnManager,
        private readonly StorageProviderFactory $storageProviderFactory,
        private readonly Filesystem $filesystem,
    ) {}

    public function shouldSyncOnBuild(): bool
    {
        return $this->cdnManager->getMode() === CdnMode::STORAGE
            && (bool) config('cdn.sync.enabled', false)
            && (bool) config('cdn.sync.on_build', true);
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function getDiff(): array
    {
        $localFiles = $this->localFiles();
        $manifest = $this->readManifest();
        $upload = [];
        $skip = [];
        $delete = [];

        foreach ($localFiles as $path => $metadata) {
            if (($manifest[$path]['hash'] ?? null) === $metadata['hash']) {
                $skip[] = $path;

                continue;
            }

            $upload[] = $path;
        }

        foreach (array_diff(array_keys($manifest), array_keys($localFiles)) as $path) {
            $delete[] = $path;
        }

        return [
            'upload' => array_values($upload),
            'skip' => array_values($skip),
            'delete' => array_values($delete),
        ];
    }

    public function syncAll(string $trigger = 'manual'): SyncResult
    {
        $startedAt = CarbonImmutable::now();
        $configuration = $this->cdnManager->configuration();
        $errors = $this->cdnManager->validateConfiguration();

        if (($configuration['mode'] ?? null) !== CdnMode::STORAGE->value) {
            $errors[] = '当前不是对象存储模式，无法执行同步。';
        }

        if (! (bool) ($configuration['sync_enabled'] ?? false)) {
            $errors[] = '当前未启用自动同步。';
        }

        if ($errors !== []) {
            $result = $this->makeResult($trigger, 'failed', $configuration, 0, 0, 0, 0, $errors, $startedAt, ['directories' => $this->configuredDirectories()]);
            $this->logResult($result);

            return $result;
        }

        $provider = $this->storageProvider();
        $diff = $this->getDiff();
        $localFiles = $this->localFiles();
        $manifest = $this->readManifest();

        try {
            foreach ($diff['upload'] as $path) {
                $provider->upload($localFiles[$path]['absolute_path'], $path);
                $manifest[$path] = [
                    'hash' => $localFiles[$path]['hash'],
                    'size' => $localFiles[$path]['size'],
                ];
            }

            foreach ($diff['delete'] as $path) {
                $provider->delete($path);
                unset($manifest[$path]);
            }

            $this->writeManifest($manifest);

            $result = $this->makeResult(
                $trigger,
                'success',
                $configuration,
                count($diff['upload']),
                count($diff['skip']),
                count($diff['delete']),
                count($localFiles),
                [],
                $startedAt,
                ['directories' => $this->configuredDirectories()],
            );
        } catch (Throwable $exception) {
            report($exception);

            $result = $this->makeResult(
                $trigger,
                'failed',
                $configuration,
                0,
                count($diff['skip']),
                0,
                count($localFiles),
                [$exception->getMessage()],
                $startedAt,
                ['directories' => $this->configuredDirectories()],
            );
        }

        $this->logResult($result);

        return $result;
    }

    public function clearRemote(string $trigger = 'manual-clear'): SyncResult
    {
        $startedAt = CarbonImmutable::now();
        $configuration = $this->cdnManager->configuration();

        try {
            $provider = $this->storageProvider();
            $remoteFiles = $provider->listFiles('');

            foreach ($remoteFiles as $path) {
                $provider->delete($path);
            }

            $this->writeManifest([]);

            $result = $this->makeResult($trigger, 'success', $configuration, 0, 0, count($remoteFiles), count($remoteFiles), [], $startedAt, []);
        } catch (Throwable $exception) {
            report($exception);
            $result = $this->makeResult($trigger, 'failed', $configuration, 0, 0, 0, 0, [$exception->getMessage()], $startedAt, []);
        }

        $this->logResult($result);

        return $result;
    }

    private function storageProvider(): StorageProvider
    {
        return $this->storageProviderFactory->make();
    }

    /**
     * @return array<string, array{absolute_path: string, hash: string, size: int}>
     */
    private function localFiles(): array
    {
        $files = [];

        foreach ($this->configuredDirectories() as $directory) {
            $absoluteDirectory = public_path($directory);

            if (! $this->filesystem->isDirectory($absoluteDirectory)) {
                continue;
            }

            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($absoluteDirectory, \FilesystemIterator::SKIP_DOTS | \FilesystemIterator::FOLLOW_SYMLINKS),
            );

            foreach ($iterator as $file) {
                if (! $file instanceof \SplFileInfo || ! $file->isFile()) {
                    continue;
                }

                $relativePath = ltrim(str_replace(public_path().DIRECTORY_SEPARATOR, '', $file->getPathname()), DIRECTORY_SEPARATOR);
                $relativePath = str_replace(DIRECTORY_SEPARATOR, '/', $relativePath);

                if ($this->shouldExclude($relativePath)) {
                    continue;
                }

                $files[$relativePath] = [
                    'absolute_path' => $file->getPathname(),
                    'hash' => sha1_file($file->getPathname()) ?: '',
                    'size' => $file->getSize(),
                ];
            }
        }

        ksort($files);

        return $files;
    }

    /**
     * @return array<int, string>
     */
    private function configuredDirectories(): array
    {
        $directories = config('cdn.sync.directories', []);

        if (! is_array($directories)) {
            return [];
        }

        return array_values(array_unique(array_filter(array_map(static fn (mixed $directory): ?string => is_string($directory) ? trim($directory, '/') : null, $directories))));
    }

    private function shouldExclude(string $relativePath): bool
    {
        $patterns = config('cdn.sync.exclude_patterns', []);

        if (! is_array($patterns)) {
            return false;
        }

        foreach ($patterns as $pattern) {
            if (is_string($pattern) && fnmatch($pattern, basename($relativePath))) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return array<string, array{hash: string, size: int}>
     */
    private function readManifest(): array
    {
        $path = $this->manifestPath();

        if (! $this->filesystem->exists($path)) {
            return [];
        }

        $decoded = json_decode((string) $this->filesystem->get($path), true);

        return is_array($decoded) ? $decoded : [];
    }

    /**
     * @param  array<string, array{hash: string, size: int}>  $manifest
     */
    private function writeManifest(array $manifest): void
    {
        $path = $this->manifestPath();
        $this->filesystem->ensureDirectoryExists(dirname($path));
        $this->filesystem->put($path, json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));
    }

    private function manifestPath(): string
    {
        return storage_path('app/cdn-sync-manifest.json');
    }

    /**
     * @param  array<string, mixed>  $configuration
     * @param  array<int, string>  $errors
     * @param  array<string, mixed>  $context
     */
    private function makeResult(
        string $trigger,
        string $status,
        array $configuration,
        int $uploadedCount,
        int $skippedCount,
        int $deletedCount,
        int $totalCount,
        array $errors,
        CarbonImmutable $startedAt,
        array $context,
    ): SyncResult {
        $finishedAt = CarbonImmutable::now();
        $durationMs = max(0, $finishedAt->diffInMilliseconds($startedAt));

        return new SyncResult(
            $trigger,
            $status,
            (string) ($configuration['mode'] ?? CdnMode::ORIGIN->value),
            $configuration['storage_provider'] ?? null,
            $uploadedCount,
            $skippedCount,
            $deletedCount,
            $totalCount,
            $durationMs,
            $errors,
            $context,
            $startedAt,
            $finishedAt,
        );
    }

    private function logResult(SyncResult $result): void
    {
        if (! class_exists(CdnSyncLog::class)) {
            return;
        }

        CdnSyncLog::query()->create($result->toLogPayload());
    }
}
