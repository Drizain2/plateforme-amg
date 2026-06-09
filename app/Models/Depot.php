<?php

namespace App\Models;

use App\Models\Concerns\HasShopScope;
use Database\Factories\DepotFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Depot extends Model
{
    /** @use HasFactory<DepotFactory> */
    use HasFactory, HasShopScope;

    protected $fillable = ['shop_id', 'name', 'address', 'phone', 'is_active'];

    public function shop(): BelongsTo
    {
        return $this->belongsTo(Shop::class);
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class);
    }

    public function movements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }

    public function stocks(): HasMany
    {
        return $this->hasMany(StockDepot::class);
    }
}
