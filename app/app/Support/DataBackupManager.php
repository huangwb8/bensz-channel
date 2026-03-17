<?php

namespace App\Support;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class DataBackupManager
{
    private const FORMAT_NAME = 'bensz-channel-core-backup';

    private const FORMAT_VERSION = 3;

    private const BACKUP_TABLES = [
        'site_settings',
        'mail_settings',
        'users',
        'social_accounts',
        'user_notification_preferences',
        'channels',
        'tags',
        'articles',
        'article_tag',
        'comments',
        'comment_subscriptions',
        'channel_email_subscriptions',
        'tag_email_subscriptions',
        'devtools_api_keys',
    ];

    private const TRANSIENT_TABLES_TO_CLEAR = [
        'devtools_connections',
        'login_codes',
        'qr_login_requests',
        'password_reset_tokens',
        'sessions',
        'cache',
        'cache_locks',
        'jobs',
        'job_batches',
        'failed_jobs',
    ];

    public function __construct(
        private readonly SiteSettingsManager $siteSettingsManager,
        private readonly MailSettingsManager $mailSettingsManager,
        private readonly StaticPageBuilder $staticPageBuilder,
    ) {
    }

    public function createBackupArchive(): string
    {
        $workspace = $this->makeTempDirectory('backup-export-');
        $archiveBase = $this->temporaryRoot().'/backup-'.Str::uuid();
        $tarPath = $archiveBase.'.tar';
        $gzPath = $tarPath.'.gz';
        $finalPath = $this->temporaryRoot().'/bensz-channel-backup-'.now()->format('Ymd-His').'-'.Str::lower(Str::random(8)).'.tar.gz';

        try {
            File::ensureDirectoryExists($workspace.'/data');
            File::put($workspace.'/README.txt', $this->backupNotice());
            File::put($workspace.'/manifest.json', $this->encodeJson($this->manifestPayload()));

            foreach (self::BACKUP_TABLES as $table) {
                File::put(
                    $workspace.'/data/'.$table.'.json',
                    $this->encodeJson([
                        'table' => $table,
                        'rows' => $this->rowsForTable($table),
                    ]),
                );
            }

            $archive = new \PharData($tarPath);
            $archive->buildFromDirectory($workspace);
            $archive->compress(\Phar::GZ);
            unset($archive);

            if (! File::exists($gzPath)) {
                throw new RuntimeException('备份压缩文件生成失败。');
            }

            File::move($gzPath, $finalPath);

            return $finalPath;
        } finally {
            if (File::exists($tarPath)) {
                File::delete($tarPath);
            }

            if (File::isDirectory($workspace)) {
                File::deleteDirectory($workspace);
            }
        }
    }

    public function restoreFromArchive(string $archivePath): void
    {
        $workspace = $this->makeTempDirectory('backup-import-');
        $uploadedArchivePath = $workspace.'/backup.tar.gz';
        $tarPath = $workspace.'/backup.tar';
        $extractPath = $workspace.'/extracted';

        try {
            File::copy($archivePath, $uploadedArchivePath);
            File::ensureDirectoryExists($extractPath);

            $compressedArchive = new \PharData($uploadedArchivePath);
            $compressedArchive->decompress();
            unset($compressedArchive);

            $archive = new \PharData($tarPath);
            $archive->extractTo($extractPath, null, true);
            unset($archive);

            $this->readManifest($extractPath.'/manifest.json');
            $tablePayloads = $this->readTablePayloads($extractPath);

            DB::transaction(function () use ($tablePayloads): void {
                Schema::withoutForeignKeyConstraints(function () use ($tablePayloads): void {
                    $this->clearTransientTables();
                    $this->clearBackupTables();
                    $this->restoreBackupTables($tablePayloads);
                });

                $this->resetSequences();
            });

            $this->refreshRuntimeState();
        } catch (Throwable $exception) {
            throw new RuntimeException('无法恢复备份文件，请确认上传的是系统导出的完整 tar.gz 文件。', 0, $exception);
        } finally {
            if (File::isDirectory($workspace)) {
                File::deleteDirectory($workspace);
            }
        }
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function backupTables(): array
    {
        return array_map(static fn (string $table): array => [
            'name' => $table,
            'count' => Schema::hasTable($table) ? DB::table($table)->count() : 0,
        ], self::BACKUP_TABLES);
    }

    private function manifestPayload(): array
    {
        return [
            'format' => self::FORMAT_NAME,
            'format_version' => self::FORMAT_VERSION,
            'generated_at' => now()->toIso8601String(),
            'database_connection' => DB::getDefaultConnection(),
            'tables' => self::BACKUP_TABLES,
            'transient_tables_cleared_on_restore' => self::TRANSIENT_TABLES_TO_CLEAR,
            'warning' => '该备份文件包含用户资料、密码哈希、双因子信息、SMTP 凭据和 DevTools 密钥等敏感数据，请离线妥善保管。',
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function rowsForTable(string $table): array
    {
        if (! Schema::hasTable($table)) {
            return [];
        }

        return DB::table($table)
            ->get()
            ->map(static fn (object $row): array => get_object_vars($row))
            ->all();
    }

    private function backupNotice(): string
    {
        return implode(PHP_EOL, [
            'Bensz Channel 核心数据备份文件',
            '',
            '警告：此归档包含站点设置、邮件设置、用户资料、密码哈希、双因子数据、文章、评论、频道结构与 DevTools 密钥等敏感数据。',
            '请仅在可信环境中保存、传输和恢复该文件。恢复操作会覆盖当前核心数据，并清理现有登录会话。',
        ]);
    }

    private function readManifest(string $manifestPath): array
    {
        if (! File::exists($manifestPath)) {
            throw new RuntimeException('备份清单缺失。');
        }

        $manifest = json_decode((string) File::get($manifestPath), true, 512, JSON_THROW_ON_ERROR);

        if (($manifest['format'] ?? null) !== self::FORMAT_NAME || ($manifest['format_version'] ?? null) !== self::FORMAT_VERSION) {
            throw new RuntimeException('备份文件格式不受支持。');
        }

        return $manifest;
    }

    /**
     * @return array<string, array<int, array<string, mixed>>>
     */
    private function readTablePayloads(string $extractPath): array
    {
        $payloads = [];

        foreach (self::BACKUP_TABLES as $table) {
            $tablePath = $extractPath.'/data/'.$table.'.json';

            if (! File::exists($tablePath)) {
                throw new RuntimeException('备份文件缺少核心表数据：'.$table);
            }

            $payload = json_decode((string) File::get($tablePath), true, 512, JSON_THROW_ON_ERROR);

            if (($payload['table'] ?? null) !== $table || ! is_array($payload['rows'] ?? null)) {
                throw new RuntimeException('备份文件中的表数据格式无效：'.$table);
            }

            $payloads[$table] = array_values($payload['rows']);
        }

        return $payloads;
    }

    private function clearTransientTables(): void
    {
        foreach (self::TRANSIENT_TABLES_TO_CLEAR as $table) {
            if (Schema::hasTable($table)) {
                DB::table($table)->delete();
            }
        }
    }

    private function clearBackupTables(): void
    {
        foreach (array_reverse(self::BACKUP_TABLES) as $table) {
            if (Schema::hasTable($table)) {
                DB::table($table)->delete();
            }
        }
    }

    /**
     * @param array<string, array<int, array<string, mixed>>> $tablePayloads
     */
    private function restoreBackupTables(array $tablePayloads): void
    {
        foreach (self::BACKUP_TABLES as $table) {
            if (! Schema::hasTable($table)) {
                continue;
            }

            $rows = $this->sanitizeRowsForTable($table, $tablePayloads[$table] ?? []);

            if ($rows === []) {
                continue;
            }

            foreach (array_chunk($rows, 200) as $chunk) {
                DB::table($table)->insert($chunk);
            }
        }
    }

    /**
     * @param array<int, array<string, mixed>> $rows
     * @return array<int, array<string, mixed>>
     */
    private function sanitizeRowsForTable(string $table, array $rows): array
    {
        $columns = Schema::getColumnListing($table);

        return array_values(array_map(
            static fn (array $row): array => Arr::only($row, $columns),
            $rows,
        ));
    }

    private function resetSequences(): void
    {
        $driver = DB::getDriverName();

        if ($driver === 'pgsql') {
            foreach (self::BACKUP_TABLES as $table) {
                if (! Schema::hasTable($table) || ! in_array('id', Schema::getColumnListing($table), true)) {
                    continue;
                }

                DB::statement(
                    "SELECT setval(pg_get_serial_sequence('{$table}', 'id'), COALESCE(MAX(id), 1), MAX(id) IS NOT NULL) FROM {$table}"
                );
            }

            return;
        }

        if ($driver === 'sqlite') {
            if (! Schema::hasTable('sqlite_sequence')) {
                return;
            }

            foreach (self::BACKUP_TABLES as $table) {
                if (! Schema::hasTable($table) || ! in_array('id', Schema::getColumnListing($table), true)) {
                    continue;
                }

                $maxId = (int) (DB::table($table)->max('id') ?? 0);
                DB::table('sqlite_sequence')->updateOrInsert(['name' => $table], ['seq' => $maxId]);
            }
        }
    }

    private function refreshRuntimeState(): void
    {
        $this->siteSettingsManager->forgetCached();
        $this->mailSettingsManager->forgetCached();
        $this->siteSettingsManager->applyConfiguredSettings();
        $this->mailSettingsManager->applyConfiguredSettings();
        $this->staticPageBuilder->rebuildAll();
    }

    private function encodeJson(array $payload): string
    {
        return json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
    }

    private function makeTempDirectory(string $prefix): string
    {
        $path = $this->temporaryRoot().'/'.$prefix.Str::uuid();
        File::ensureDirectoryExists($path);

        return $path;
    }

    private function temporaryRoot(): string
    {
        $path = storage_path('app/tmp/backups');
        File::ensureDirectoryExists($path);

        return $path;
    }
}
