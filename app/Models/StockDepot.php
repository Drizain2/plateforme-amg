<?php

namespace App\Models;

use App\Models\Concerns\HasShopScope;
use Database\Factories\StockDepotFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StockDepot extends Model
{
    /** @use HasFactory<StockDepotFactory> */
    use HasFactory, HasShopScope;

    protected $fillable = [
        'shop_id',
        'part_id',
        'depot_id',
        'quantity',
        'alert_quantity',
    ];

    public function shop(): BelongsTo
    {
        return $this->belongsTo(Shop::class);
    }

    public function part(): BelongsTo
    {
        return $this->belongsTo(Part::class);
    }

    public function depot(): BelongsTo
    {
        return $this->belongsTo(Depot::class);
    }

    public function movements(): HasMany
    {
        return $this->hasMany(StockMovement::class, 'stock_id');
    }

    public function getIsCriticalAttribute(): bool
    {
        return $this->quantity <= $this->alert_quantity;
    }

    public function scopeCritique(Builder $builder): Builder
    {
        return $builder->whereColumn('quantity', '<=', 'alert_quantity');
    }

    public function scopeNonCritique(Builder $builder): Builder
    {
        return $builder->whereColumn('quantity', '>', 'alert_quantity');
    }
}
