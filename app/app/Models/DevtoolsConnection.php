<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class DevtoolsConnection extends Model
{
    protected $table = 'devtools_connections';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'id',
        'key_id',
        'client_name',
        'client_version',
        'machine',
        'workdir',
        'last_seen_at',
        'last_error',
        'terminate_requested_at',
        'terminated_at',
    ];

    protected function casts(): array
    {
        return [
            'last_seen_at' => 'datetime',
            'terminate_requested_at' => 'datetime',
            'terminated_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (DevtoolsConnection $model): void {
            if (empty($model->id)) {
                $model->id = (string) Str::uuid();
            }
        });
    }

    public function apiKey(): BelongsTo
    {
        return $this->belongsTo(DevtoolsApiKey::class, 'key_id');
    }

    public function isActive(): bool
    {
        return $this->terminated_at === null;
    }

    public function terminateRequested(): bool
    {
        return $this->terminate_requested_at !== null;
    }
}
