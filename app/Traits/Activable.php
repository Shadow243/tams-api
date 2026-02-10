<?php

declare(strict_types=1);

namespace App\Traits;

use App\Scopes\ActiveScope;

trait Activable
{
    /**
     * Boot the active trait for a model.
     *
     * @return void
     */
    public static function bootActivable()
    {
        static::addGlobalScope(new ActiveScope);
    }

    /**
     * Get the query builder without the scope applied.
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public static function scopeWithInactive()
    {
        return with(new static)->newQueryWithoutScope(new ActiveScope);
    }

    public function isActive()
    {
        return (bool) $this->getAttribute($this->getActiveColumn());
    }

    public function getActiveLabel()
    {
        return $this->isActive() ? 'OUI' : 'NON';
    }

    public function isDisabled()
    {
        return (bool) ! $this->getAttribute($this->getActiveColumn());
    }

    /**
     * Get the name of the column for applying the scope.
     *
     * @return string
     */
    public function getActiveColumn()
    {
        return defined(static::class . '::ACTIVE_COLUMN')
            ? constant(static::class . '::ACTIVE_COLUMN')
            : 'active';
    }

    /**
     * Get the fully qualified column name for applying the scope.
     *
     * @return string
     */
    public function getQualifiedActiveColumn()
    {
        return $this->getTable() . '.' . $this->getActiveColumn();
    }

    public function scopeActive($query)
    {
        return $query->where($this->getActiveColumn(), true);
    }

    public function scopeDisabled($query)
    {
        return $query->where($this->getActiveColumn(), false);
    }
}
