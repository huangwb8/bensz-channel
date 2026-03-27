<?php

namespace App\Support;

use App\Models\DevtoolsApiKey;
use App\Models\DevtoolsIdempotencyKey;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;

class DevtoolsIdempotencyManager
{
    private const WAIT_ATTEMPTS = 40;

    private const WAIT_USEC = 250000;

    public function fingerprint(array $payload): string
    {
        return hash('sha256', json_encode(
            $this->normalizeValue($payload),
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
        ));
    }

    public function reserve(DevtoolsApiKey $apiKey, string $scope, string $token, string $fingerprint): DevtoolsIdempotencyKey
    {
        $attributes = [
            'key_id' => $apiKey->id,
            'scope' => $scope,
            'token_hash' => hash('sha256', $token),
        ];

        try {
            return DevtoolsIdempotencyKey::query()->firstOrCreate($attributes, [
                'request_fingerprint' => $fingerprint,
            ]);
        } catch (QueryException) {
            return DevtoolsIdempotencyKey::query()
                ->where($attributes)
                ->firstOrFail();
        }
    }

    public function conflictResponse(string $token): JsonResponse
    {
        return response()->json([
            'error' => 'idempotency_key_conflict',
            'message' => '同一个幂等键不能对应不同的请求内容。',
        ], 409, [
            'X-Idempotency-Key' => $token,
            'X-Idempotency-Replayed' => 'false',
        ]);
    }

    public function inProgressResponse(string $token): JsonResponse
    {
        return response()->json([
            'error' => 'idempotency_request_in_progress',
            'message' => '相同幂等键的请求仍在处理中，请稍后使用相同请求重试。',
        ], 409, [
            'X-Idempotency-Key' => $token,
            'X-Idempotency-Replayed' => 'false',
        ]);
    }

    public function replayResponse(DevtoolsIdempotencyKey $record, string $token): JsonResponse
    {
        return response()->json(
            $record->response_body ?? [],
            $record->response_status ?? 200,
            [
                'X-Idempotency-Key' => $token,
                'X-Idempotency-Replayed' => 'true',
            ],
        );
    }

    public function complete(
        DevtoolsIdempotencyKey $record,
        string $fingerprint,
        int $status,
        array $body,
        ?string $resourceType = null,
        ?int $resourceId = null,
    ): void {
        $record->forceFill([
            'request_fingerprint' => $fingerprint,
            'response_status' => $status,
            'response_body' => $body,
            'resource_type' => $resourceType,
            'resource_id' => $resourceId,
            'completed_at' => now(),
        ])->save();
    }

    public function abandon(DevtoolsIdempotencyKey $record): void
    {
        if (! $record->isCompleted()) {
            $record->delete();
        }
    }

    public function waitForCompletion(DevtoolsIdempotencyKey $record): ?DevtoolsIdempotencyKey
    {
        for ($attempt = 0; $attempt < self::WAIT_ATTEMPTS; $attempt++) {
            $record->refresh();

            if ($record->isCompleted()) {
                return $record;
            }

            usleep(self::WAIT_USEC);
        }

        $record->refresh();

        return $record->isCompleted() ? $record : null;
    }

    /**
     * @return mixed
     */
    private function normalizeValue(mixed $value): mixed
    {
        if (! is_array($value)) {
            return $value;
        }

        if (array_is_list($value)) {
            return array_map(fn (mixed $item): mixed => $this->normalizeValue($item), $value);
        }

        ksort($value);

        foreach ($value as $key => $item) {
            $value[$key] = $this->normalizeValue($item);
        }

        return $value;
    }
}
