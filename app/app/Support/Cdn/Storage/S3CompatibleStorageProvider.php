<?php

namespace App\Support\Cdn\Storage;

use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Filesystem\FilesystemManager;
use Throwable;
use RuntimeException;

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

    public function testConnection(): ConnectionTestResult
    {
        $details = [
            '使用顶层目录探测对象存储可用性。',
            '端点：'.($this->configuration['endpoint'] ?? '-'),
            '存储桶：'.($this->configuration['bucket'] ?? '-'),
            '区域：'.($this->configuration['region'] ?? '-'),
        ];

        try {
            $files = $this->disk()->files('');

            $details[] = sprintf('连接成功，顶层探测到 %d 个对象。', count($files));

            return new ConnectionTestResult(
                true,
                '对象存储连接测试通过。',
                implode(PHP_EOL, $details),
                [
                    'remote_file_count' => count($files),
                ],
            );
        } catch (Throwable $exception) {
            $details[] = '异常类型：'.$exception::class;
            $details[] = '异常原因：'.$exception->getMessage();

            return new ConnectionTestResult(
                false,
                '对象存储连接测试失败：'.$exception->getMessage(),
                implode(PHP_EOL, $details),
                [
                    'exception' => [
                        'class' => $exception::class,
                        'message' => $exception->getMessage(),
                    ],
                ],
            );
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
