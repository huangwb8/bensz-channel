<?php

namespace App\Console\Commands;

use App\Jobs\SyncCdnFilesJob;
use App\Support\Cdn\CdnSyncService;
use Illuminate\Console\Command;

class SyncCdnFiles extends Command
{
    protected $signature = 'cdn:sync {--queue : 将同步任务加入队列} {--clear : 清空远程文件}';

    protected $description = '同步公开静态资源到 CDN 对象存储';

    public function handle(CdnSyncService $cdnSyncService): int
    {
        if ($this->option('queue')) {
            SyncCdnFilesJob::dispatch($this->option('clear') ? 'manual-clear' : 'manual');
            $this->info('CDN 同步任务已加入队列。');

            return self::SUCCESS;
        }

        $result = $this->option('clear')
            ? $cdnSyncService->clearRemote('manual-clear')
            : $cdnSyncService->syncAll('manual');

        if (! $result->successful()) {
            $this->error($result->message());

            return self::FAILURE;
        }

        $this->info($result->message());

        return self::SUCCESS;
    }
}
