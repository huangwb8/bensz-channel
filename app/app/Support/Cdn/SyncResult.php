<?php

namespace App\Support\Cdn;

use Carbon\CarbonImmutable;

class SyncResult
{
    /**
     * @param  array<int, string>  $errors
     * @param  array<string, mixed>  $context
     */
    public function __construct(
        public readonly string $trigger,
        public readonly string $status,
        public readonly string $mode,
        public readonly ?string $provider,
        public readonly int $uploadedCount,
        public readonly int $skippedCount,
        public readonly int $deletedCount,
        public readonly int $totalCount,
        public readonly int $durationMs,
        public readonly array $errors,
        public readonly string $details,
        public readonly array $context,
        public readonly CarbonImmutable $startedAt,
        public readonly CarbonImmutable $finishedAt,
    ) {}

    public function successful(): bool
    {
        return $this->status === 'success' && $this->errors === [];
    }

    public function message(): string
    {
        if ($this->errors !== []) {
            return implode('；', $this->errors);
        }

        return sprintf(
            '同步完成：上传 %d，跳过 %d，删除 %d。',
            $this->uploadedCount,
            $this->skippedCount,
            $this->deletedCount,
        );
    }

    public function toLogPayload(): array
    {
        return [
            'trigger' => $this->trigger,
            'status' => $this->status,
            'mode' => $this->mode,
            'provider' => $this->provider,
            'total_count' => $this->totalCount,
            'uploaded_count' => $this->uploadedCount,
            'skipped_count' => $this->skippedCount,
            'deleted_count' => $this->deletedCount,
            'duration_ms' => $this->durationMs,
            'message' => $this->message(),
            'details' => $this->details,
            'context' => $this->context,
            'started_at' => $this->startedAt,
            'finished_at' => $this->finishedAt,
        ];
    }
}
