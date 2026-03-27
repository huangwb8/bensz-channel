<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DevtoolsIdempotencyKey extends Model
{
    protected $table = 'devtools_idempotency_keys';

    protected $fillable = [
        'key_id',
        'scope',
        'token_hash',
        'request_fingerprint',
        'response_status',
        'response_body',
        'resource_type',
        'resource_id',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'response_body' => 'array',
            'completed_at' => 'datetime',
        ];
    }

    public function apiKey(): BelongsTo
    {
        return $this->belongsTo(DevtoolsApiKey::class, 'key_id');
    }

    public function isCompleted(): bool
    {
        return $this->completed_at !== null
            && $this->response_status !== null
            && is_array($this->response_body);
    }
}
