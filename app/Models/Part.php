<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Query\Builder;

class Part extends Model
{
    /** @use HasFactory<\Database\Factories\PartFactory> */
    use HasFactory;

    protected $fillable = [
        "shop_id",
        "supplier_id",
        "name",
        "sku",
        "category_id",
        "brand_compat",
        "unit_price",
        "is_active",
    ];

    public function shop(): BelongsTo
    {
        return $this->belongsTo(Shop::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Categorie::class);
    }

    protected static function booted(): void
    {
        static::addGlobalScope('shop', fn(Builder $q) =>
            $q->where('shop_id', app('current_shop')->id)
        );

        static::creating(fn($m) => $m->shop_id = app('current_shop')->id);
    }
}
