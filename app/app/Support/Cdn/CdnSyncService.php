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
        private readonly CdnWorkLogManager $workLogManager,
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

        return $this->diffFromState($localFiles, $manifest);
    }

    public function syncAll(string $trigger = 'manual'): SyncResult
    {
        $startedAt = CarbonImmutable::now();
        $configuration = $this->cdnManager->runtimeConfiguration();
        $errors = $this->cdnManager->validateRuntimeConfiguration();
        $details = [
            '工作类型：同步对象存储资源',
            '触发来源：'.$trigger,
            '同步目录：'.implode('、', $this->configuredDirectories()),
        ];

        if (($configuration['mode'] ?? null) !== CdnMode::STORAGE->value) {
            $errors[] = '当前未应用对象存储型 CDN，无法执行同步。';
        }

        if (! (bool) ($configuration['sync_enabled'] ?? false)) {
            $errors[] = '当前运行中的 CDN 未启用同步。';
        }

        if ($errors !== []) {
            $details[] = '失败原因：'.implode('；', $errors);
            $result = $this->makeResult(
                $trigger,
                'failed',
                $configuration,
                0,
                0,
                0,
                0,
                $errors,
                $startedAt,
                [
                    'directories' => $this->configuredDirectories(),
                ],
                $this->workLogManager->buildDetails($details),
            );
            $this->logResult($result);

            return $result;
        }

        $diff = ['upload' => [], 'skip' => [], 'delete' => []];
        $localFiles = [];

        try {
            $provider = $this->storageProvider();
            $localFiles = $this->localFiles();
            $manifest = $this->readManifest();
            $diff = $this->diffFromState($localFiles, $manifest);

            $details[] = sprintf('本地文件：共 %d 个。', count($localFiles));
            $details[] = sprintf('待上传 %d 个，已同步 %d 个，待删除 %d 个。', count($diff['upload']), count($diff['skip']), count($diff['delete']));
            $details[] = $this->workLogManager->summarizePaths('待上传文件', $diff['upload']);
            $details[] = $this->workLogManager->summarizePaths('待删除文件', $diff['delete']);

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
            $details[] = '同步完成，Manifest 已更新：'.$this->manifestPath();

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
                [
                    'directories' => $this->configuredDirectories(),
                    'diff' => $diff,
                ],
                $this->workLogManager->buildDetails($details),
            );
        } catch (Throwable $exception) {
            report($exception);
            $details[] = $this->workLogManager->describeException($exception);

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
                [
                    'directories' => $this->configuredDirectories(),
                    'diff' => $diff,
                    'exception' => [
                        'class' => $exception::class,
                        'message' => $exception->getMessage(),
                    ],
                ],
                $this->workLogManager->buildDetails($details),
            );
        }

        $this->logResult($result);

        return $result;
    }

    public function clearRemote(string $trigger = 'manual-clear'): SyncResult
    {
        $startedAt = CarbonImmutable::now();
        $configuration = $this->cdnManager->runtimeConfiguration();
        $details = [
            '工作类型：清理远程对象存储文件',
            '触发来源：'.$trigger,
        ];

        try {
            $provider = $this->storageProvider();
            $remoteFiles = $provider->listFiles('');
            $details[] = sprintf('准备删除远程文件 %d 个。', count($remoteFiles));
            $details[] = $this->workLogManager->summarizePaths('待删除远程文件', $remoteFiles);

            foreach ($remoteFiles as $path) {
                $provider->delete($path);
            }

            $this->writeManifest([]);
            $details[] = '远程清理完成，本地 Manifest 已重置。';

            $result = $this->makeResult(
                $trigger,
                'success',
                $configuration,
                0,
                0,
                count($remoteFiles),
                count($remoteFiles),
                [],
                $startedAt,
                [
                    'remote_files' => $remoteFiles,
                ],
                $this->workLogManager->buildDetails($details),
            );
        } catch (Throwable $exception) {
            report($exception);
            $details[] = $this->workLogManager->describeException($exception);
            $result = $this->makeResult(
                $trigger,
                'failed',
                $configuration,
                0,
                0,
                0,
                0,
                [$exception->getMessage()],
                $startedAt,
                [
                    'exception' => [
                        'class' => $exception::class,
                        'message' => $exception->getMessage(),
                    ],
                ],
                $this->workLogManager->buildDetails($details),
            );
        }

        $this->logResult($result);

        return $result;
    }

    private function storageProvider(): StorageProvider
    {
        return $this->storageProviderFactory->make();
    }

    /**
     * @param  array<string, array{absolute_path: string, hash: string, size: int}>  $localFiles
     * @param  array<string, array{hash: string, size: int}>  $manifest
     * @return array<string, array<int, string>>
     */
    private function diffFromState(array $localFiles, array $manifest): array
    {
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
        string $details,
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
            $details,
            $context,
            $startedAt,
            $finishedAt,
        );
    }

    private function logResult(SyncResult $result): void
    {
        $this->workLogManager->record($result->toLogPayload());
    }
}
