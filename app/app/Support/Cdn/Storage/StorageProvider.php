<?php

namespace App\Support\Cdn\Storage;

interface StorageProvider
{
    public function upload(string $localPath, string $remotePath): bool;

    public function delete(string $remotePath): bool;

    public function exists(string $remotePath): bool;

    /**
     * @return array<int, string>
     */
    public function listFiles(string $prefix = ''): array;

    public function getPublicUrl(string $remotePath): string;

    public function testConnection(): ConnectionTestResult;
}
