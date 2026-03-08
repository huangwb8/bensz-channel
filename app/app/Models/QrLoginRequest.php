<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QrLoginRequest extends Model
{
    /** @use HasFactory<\Database\Factories\QrLoginRequestFactory> */
    use HasFactory;

    public const STATUS_PENDING = 'pending';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_CONSUMED = 'consumed';

    public const STATUS_EXPIRED = 'expired';

    protected $fillable = [
        'provider',
        'token',
        'status',
        'approved_user_id',
        'expires_at',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'meta' => 'array',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'token';
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_user_id');
    }

    public function isExpired(): bool
    {
        return $this->status === self::STATUS_EXPIRED || $this->expires_at->isPast();
    }
}
