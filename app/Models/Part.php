<?php

namespace App\Models;

use App\Models\Concerns\HasShopScope;
use Database\Factories\PartFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Part extends Model
{
    /** @use HasFactory<PartFactory> */
    use HasFactory, HasShopScope;

    protected $fillable = [
        'shop_id',
        'supplier_id',
        'name',
        'sku',
        'category_id',
        'brand_compat',
        'unit_price',
        'sell_price',
        'is_active',
    ];

    protected $casts = [
        'brand_compat' => 'array',
        'unit_price' => 'decimal:2',
        'sell_price' => 'decimal:2',
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

    public function stockDepots(): HasMany
    {
        return $this->hasMany(StockDepot::class);
    }
}
