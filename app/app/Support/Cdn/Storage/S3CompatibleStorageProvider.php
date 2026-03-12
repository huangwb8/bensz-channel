<?php

namespace App\Support\Cdn\Storage;

use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Filesystem\FilesystemManager;
use RuntimeException;
use Throwable;

class S3CompatibleStorageProvider implements StorageProvider
{
    private ?Filesystem $disk = null;

    public function __construct(
        private readonly FilesystemManager $filesystemManager,
        private readonly array $configuration,
    ) {}

    public function upload(string $localPath, string $remotePath): bool
    {
        $stream = fopen($localPath, 'r');

        if ($stream === false) {
            throw new RuntimeException('无法读取待同步文件：'.$localPath);
        }

        try {
            return $this->disk()->writeStream(ltrim($remotePath, '/'), $stream, [
                'visibility' => 'public',
                'ContentType' => mime_content_type($localPath) ?: 'application/octet-stream',
            ]);
        } finally {
            fclose($stream);
        }
    }

    public function delete(string $remotePath): bool
    {
        return $this->disk()->delete(ltrim($remotePath, '/'));
    }

    public function exists(string $remotePath): bool
    {
        return $this->disk()->exists(ltrim($remotePath, '/'));
    }

    public function listFiles(string $prefix = ''): array
    {
        return array_values($this->disk()->allFiles(trim($prefix, '/')));
    }

    public function getPublicUrl(string $remotePath): string
    {
        $publicUrl = trim((string) ($this->configuration['url'] ?? ''));

        if ($publicUrl !== '') {
            return rtrim($publicUrl, '/').'/'.ltrim($remotePath, '/');
        }

        return $this->disk()->url(ltrim($remotePath, '/'));
    }

    public function validateCredentials(): bool
    {
        try {
            $this->listFiles('');

            return true;
        } catch (Throwable) {
            return false;
        }
    }

    private function disk(): Filesystem
    {
        if ($this->disk instanceof Filesystem) {
            return $this->disk;
        }

        return $this->disk = $this->filesystemManager->build([
            'driver' => 's3',
            'key' => $this->configuration['key'] ?? null,
            'secret' => $this->configuration['secret'] ?? null,
            'region' => $this->configuration['region'] ?? 'auto',
            'bucket' => $this->configuration['bucket'] ?? null,
            'endpoint' => $this->configuration['endpoint'] ?? null,
            'url' => $this->configuration['url'] ?? null,
            'use_path_style_endpoint' => (bool) ($this->configuration['use_path_style_endpoint'] ?? false),
            'throw' => true,
        ]);
    }
}
