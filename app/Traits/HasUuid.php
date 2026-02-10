<?php

declare(strict_types=1);

namespace App\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

trait HasUuid
{
    /**
     * Generate a new UUID for the model.
     *
     * @return string
     */
    public function newUniqueId()
    {
        return (string) Str::orderedUuid();
    }

    /**
     * Get the columns that should receive a unique identifier.
     *
     * @return array<int, string>
     */
    public function uniqueIds(): array
    {
        return ['uuid'];
    }

    /**
     * Scope a query to only include popular users.
     */
    public function scopeFindByUuid(Builder $query, string $uuid)
    {
        if (is_numeric($uuid)) {
            $query = $query->where('id', $uuid);
        } else {
            $query = $query->where('uuid', $uuid);
        }

        return $query->firstOrFail();
    }

    /**
     * The "booted" method of the model.
     */
    protected static function booted(): void
    {
        static::saving(function ($model) {

            foreach ($model->uniqueIds() as $column) {
                if (is_null($model->{$column})) {
                    $model->{$column} = $model->newUniqueId();
                }
            }
        });
    }
}
