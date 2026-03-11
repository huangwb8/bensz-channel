<?php

namespace Tests\Feature\Static;

use App\Enums\CdnMode;
use App\Jobs\SyncCdnFilesJob;
use App\Support\StaticPageBuilder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Tests\TestCase;

class CdnSyncTriggerTest extends TestCase
{
    use RefreshDatabase;

    public function test_static_build_dispatches_cdn_sync_job_when_storage_sync_on_build_is_enabled(): void
    {
        config([
            'community.static.enabled' => true,
            'community.static.async' => false,
            'community.static.output_dir' => 'static',
            'cdn.mode' => CdnMode::STORAGE->value,
            'cdn.sync.enabled' => true,
            'cdn.sync.on_build' => true,
        ]);

        Bus::fake();

        app(StaticPageBuilder::class)->buildAll();

        Bus::assertDispatched(SyncCdnFilesJob::class, function (SyncCdnFilesJob $job): bool {
            return $job->trigger === 'build';
        });
    }
}
