<?php

namespace App\Models;

use App\Models\Concerns\HasShopScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockCountLine extends Model
{
    use HasShopScope;

    protected $fillable = [
        'shop_id', 'stock_count_id', 'stock_depot_id',
        'expected_quantity', 'counted_quantity', 'unit_cost', 'note',
    ];

    protected $casts = [
        'unit_cost' => 'decimal:2',
    ];

    public function getDifferenceAttribute(): ?int
    {
        return $this->counted_quantity === null
            ? null
            : $this->counted_quantity - $this->expected_quantity;
    }

    public function stockCount(): BelongsTo
    {
        return $this->belongsTo(StockCount::class);
    }

    public function stockDepot(): BelongsTo
    {
        return $this->belongsTo(StockDepot::class);
    }
}
