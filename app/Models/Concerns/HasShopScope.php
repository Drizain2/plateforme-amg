<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Builder;

trait HasShopScope
{
    public static function bootHasShopScope(): void
    {
        static::addGlobalScope('shop', function (Builder $query) {
            if (app()->has('current_shop')) {
                $query->where($query->getModel()->getTable().'.shop_id', app('current_shop')->id);
            }
        });

        static::creating(function ($model) {
            if (app()->has('current_shop')) {
                $model->shop_id = app('current_shop')->id;
            }
        });
    }
}
