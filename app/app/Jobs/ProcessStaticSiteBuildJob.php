<?php

namespace App\Jobs;

use App\Support\StaticPageBuilder;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUniqueUntilProcessing;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessStaticSiteBuildJob implements ShouldBeUniqueUntilProcessing, ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $uniqueFor = 60;

    public function __construct(public readonly array $payload)
    {
        $this->onQueue((string) config('community.static.queue', 'static-builds'));
    }

    public function uniqueId(): string
    {
        return sha1(json_encode($this->payload, JSON_UNESCAPED_UNICODE));
    }

    public function middleware(): array
    {
        return [
            (new WithoutOverlapping('static-site-build'))
                ->expireAfter(900)
                ->releaseAfter(5),
        ];
    }

    public function handle(StaticPageBuilder $staticPageBuilder): void
    {
        $summary = $staticPageBuilder->buildPayload($this->payload, function (string $message): void {
            Log::info($message);
        });

        Log::info('静态页面队列构建完成。', $summary);
    }
}
