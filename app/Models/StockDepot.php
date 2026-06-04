<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Query\Builder;

class StockDepot extends Model
{
    /** @use HasFactory<\Database\Factories\StockDepotFactory> */
    use HasFactory;

    protected $fillable = [
        "shop_id",
        "part_id",
        "depot_id",
        "quantity",
        "alert_quantity",
    ];

    protected static function booted(): void
    {
        static::addGlobalScope('shop', fn(Builder $q) =>
            $q->where('shop_id', app('current_shop')->id)
        );

        static::creating(fn($m) => $m->shop_id = app('current_shop')->id);
    }

    public function shop(): BelongsTo  { return $this->belongsTo(Shop::class); }
    public function part(): BelongsTo   { return $this->belongsTo(Part::class); }
    public function depot(): BelongsTo   { return $this->belongsTo(Depot::class); }

    public function getIsCriticalAttribute(): bool{
        return $this->quantity <= $this->alert_quantity;
    }

    // Scopes
    public function scopeCritique(Builder $builder): Builder{
        return $builder->whereColumn("quantity", "<=", "alert_quantity");
    }

    public function scopeNonCritique(Builder $builder): Builder{
        return $builder->whereColumn("quantity", ">", "alert_quantity");
    }
}
