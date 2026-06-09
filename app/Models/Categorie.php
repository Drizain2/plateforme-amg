<?php

namespace App\Models;

use App\Models\Concerns\HasShopScope;
use Database\Factories\CategorieFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Categorie extends Model
{
    /** @use HasFactory<CategorieFactory> */
    use HasFactory, HasShopScope;

    protected $fillable = ['name', 'shop_id', 'is_active'];

    public function shop(): BelongsTo
    {
        return $this->belongsTo(Shop::class);
    }

    public function parts(): HasMany
    {
        return $this->hasMany(Part::class);
    }
}
