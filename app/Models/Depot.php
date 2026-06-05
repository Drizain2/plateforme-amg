<?php

namespace App\Models;

use Database\Factories\DepotFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Query\Builder;

class Depot extends Model
{
    /** @use HasFactory<DepotFactory> */
    use HasFactory;

    protected $fillable = ['shop_id', 'name', 'address', 'phone', 'is_active'];

    protected static function booted(): void
    {
        static::addGlobalScope(
            'shop',
            fn (Builder $q) => $q->where('shop_id', app('current_shop')->id)
        );

        static::creating(fn ($m) => $m->shop_id = app('current_shop')->id);
    }

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
