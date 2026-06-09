<?php

namespace App\Models;

use App\Models\Concerns\HasShopScope;
use Database\Factories\SupplierFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Supplier extends Model
{
    /** @use HasFactory<SupplierFactory> */
    use HasFactory, HasShopScope;

    protected $fillable = ['shop_id', 'name', 'email', 'phone', 'address', 'is_active'];

    public function shop(): BelongsTo
    {
        return $this->belongsTo(Shop::class);
    }

    public function parts(): HasMany
    {
        return $this->hasMany(Part::class);
    }
}
