<?php

namespace App\Support\Cdn;

use App\Models\CdnSyncLog;
use Carbon\CarbonImmutable;
use Throwable;

class CdnWorkLogManager
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public function record(array $payload): void
    {
        if (! class_exists(CdnSyncLog::class)) {
            return;
        }

        CdnSyncLog::query()->create([
            'trigger' => (string) ($payload['trigger'] ?? 'unknown'),
            'status' => (string) ($payload['status'] ?? 'success'),
            'mode' => (string) ($payload['mode'] ?? 'origin'),
            'provider' => $payload['provider'] ?? null,
            'total_count' => (int) ($payload['total_count'] ?? 0),
            'uploaded_count' => (int) ($payload['uploaded_count'] ?? 0),
            'skipped_count' => (int) ($payload['skipped_count'] ?? 0),
            'deleted_count' => (int) ($payload['deleted_count'] ?? 0),
            'duration_ms' => (int) ($payload['duration_ms'] ?? 0),
            'message' => $payload['message'] ?? null,
            'details' => $payload['details'] ?? null,
            'context' => $payload['context'] ?? [],
            'started_at' => $payload['started_at'] ?? CarbonImmutable::now(),
            'finished_at' => $payload['finished_at'] ?? CarbonImmutable::now(),
        ]);
    }

    /**
     * @param  array<int, string>  $lines
     */
    public function buildDetails(array $lines): string
    {
        $filtered = array_values(array_filter(array_map(
            static fn (mixed $line): ?string => is_string($line) && trim($line) !== '' ? trim($line) : null,
            $lines,
        )));

        return implode(PHP_EOL, $filtered);
    }

    public function describeException(Throwable $exception): string
    {
        return $this->buildDetails([
            '异常类型：'.$exception::class,
            '异常原因：'.$exception->getMessage(),
            '异常位置：'.$exception->getFile().':'.$exception->getLine(),
        ]);
    }

    /**
     * @param  array<int, string>  $items
     */
    public function summarizePaths(string $label, array $items, int $limit = 12): string
    {
        if ($items === []) {
            return $label.'：无';
        }

        $shown = array_slice(array_values($items), 0, $limit);
        $suffix = count($items) > $limit ? sprintf('（其余 %d 项省略）', count($items) - $limit) : '';

        return sprintf('%s：%s%s', $label, implode('，', $shown), $suffix);
    }
}
