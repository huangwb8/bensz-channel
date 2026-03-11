<?php

namespace App\Support\Cdn\Storage;

use App\Support\Cdn\CdnManager;
use Illuminate\Filesystem\FilesystemManager;
use RuntimeException;

class StorageProviderFactory
{
    public function __construct(
        private readonly CdnManager $cdnManager,
        private readonly FilesystemManager $filesystemManager,
    ) {}

    public function make(): StorageProvider
    {
        $configuration = $this->cdnManager->storageConfiguration();

        if ($configuration === []) {
            throw new RuntimeException('当前 CDN 未配置对象存储模式。');
        }

        return new S3CompatibleStorageProvider($this->filesystemManager, $configuration);
    }
}
