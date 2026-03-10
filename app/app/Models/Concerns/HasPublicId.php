<?php

namespace App\Models\Concerns;

use App\Support\PublicIdGenerator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

trait HasPublicId
{
    public static function bootHasPublicId(): void
    {
        static::creating(function (Model $model): void {
            if (filled($model->getAttribute('public_id'))) {
                return;
            }

            $model->setAttribute('public_id', static::generateUniquePublicId());
        });
    }

    public function getRouteKeyName(): string
    {
        return 'public_id';
    }

    public function scopeWherePublicReference(Builder $query, string|int $identifier): Builder
    {
        $normalized = trim((string) $identifier);

        return $query->where(function (Builder $query) use ($normalized): void {
            $query->where('public_id', $normalized)
                ->orWhere('slug', $normalized);

            if (ctype_digit($normalized)) {
                $query->orWhere($this->getQualifiedKeyName(), (int) $normalized);
            }
        });
    }

    public function resolveRouteBinding($value, $field = null): ?Model
    {
        if ($field !== null && $field !== $this->getRouteKeyName()) {
            return $this->newQuery()->where($field, $value)->first();
        }

        return $this->newQuery()->wherePublicReference((string) $value)->first();
    }

    protected static function generateUniquePublicId(): string
    {
        do {
            $publicId = PublicIdGenerator::make();
        } while (static::query()->where('public_id', $publicId)->exists());

        return $publicId;
    }
}
