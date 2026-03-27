<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class DevtoolsApiKey extends Model
{
    protected $table = 'devtools_api_keys';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'id',
        'user_id',
        'name',
        'key_hash',
        'key_prefix',
        'revoked_at',
    ];

    protected function casts(): array
    {
        return [
            'revoked_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function connections(): HasMany
    {
        return $this->hasMany(DevtoolsConnection::class, 'key_id');
    }

    public function idempotencyKeys(): HasMany
    {
        return $this->hasMany(DevtoolsIdempotencyKey::class, 'key_id');
    }

    public function isRevoked(): bool
    {
        return $this->revoked_at !== null;
    }

    /**
     * Generate a new API key and return [DevtoolsApiKey, rawKey].
     */
    public static function generate(int $userId, string $name = 'default'): array
    {
        $raw = 'bdc_' . Str::random(48); // 52 chars total
        $hash = hash('sha256', $raw);
        $prefix = substr($raw, 0, 12);

        $model = static::query()->create([
            'id' => (string) Str::uuid(),
            'user_id' => $userId,
            'name' => $name,
            'key_hash' => $hash,
            'key_prefix' => $prefix,
        ]);

        return [$model, $raw];
    }

    /**
     * Find an active key by raw value.
     */
    public static function findByRaw(string $rawKey): ?static
    {
        $hash = hash('sha256', $rawKey);

        return static::query()
            ->whereNull('revoked_at')
            ->where('key_hash', $hash)
            ->first();
    }
}
