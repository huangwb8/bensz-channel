<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CdnSyncLog extends Model
{
    protected function casts(): array
    {
        return [
            'context' => 'array',
            'started_at' => 'datetime',
            'finished_at' => 'datetime',
        ];
    }

    protected $fillable = [
        'trigger',
        'status',
        'mode',
        'provider',
        'total_count',
        'uploaded_count',
        'skipped_count',
        'deleted_count',
        'duration_ms',
        'message',
        'details',
        'context',
        'started_at',
        'finished_at',
    ];
}
