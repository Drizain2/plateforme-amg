<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Builder;

trait HasDepotScope
{
    public static function bootHasDepotScope(): void
    {
        static::addGlobalScope('depot', function (Builder $query) {
            if (app()->has('current_depot')) {
                $query->where($query->getModel()->getTable().'.depot_id', app('current_depot')->id);
            }
        });

        static::creating(function ($model) {
            if (app()->has('current_depot') && empty($model->depot_id)) {
                $model->depot_id = app('current_depot')->id;
            }
        });
    }
}
