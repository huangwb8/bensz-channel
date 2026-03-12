<?php

namespace App\Jobs;

use App\Support\Cdn\CdnSyncService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SyncCdnFilesJob implements ShouldQueue
{
    use Queueable;

    public function __construct(public readonly string $trigger = 'build') {}

    public function handle(CdnSyncService $cdnSyncService): void
    {
        if (str_contains($this->trigger, 'clear')) {
            $cdnSyncService->clearRemote($this->trigger);

            return;
        }

        $cdnSyncService->syncAll($this->trigger);
    }
}
