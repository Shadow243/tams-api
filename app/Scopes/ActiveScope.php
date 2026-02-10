<?php

declare(strict_types=1);

namespace App\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

final class ActiveScope implements Scope
{
    /**
     * Apply the scope to a given Eloquent query builder.
     *
     * @return void
     */
    public function apply(Builder $builder, Model $model)
    {
        if (method_exists($model, 'getActiveColumn')) {
            $builder->where($model->getQualifiedActiveColumn(), '=', 1);
        } else {
            $builder->where($model->getTable() . '.active', '=', 1);
        }
    }
}
