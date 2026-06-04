<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Query\Builder;

class Categorie extends Model
{
    /** @use HasFactory<\Database\Factories\CategorieFactory> */
    use HasFactory;

    protected $fillable =['name', 'shop_id', 'is_active'];

    public function shop(): BelongsTo
    {
        return $this->belongsTo(Shop::class);
    }
    public function parts(): HasMany
    {
        return $this->hasMany(Part::class);
    }
    protected static function booted(): void
    {
        static::addGlobalScope('shop', fn(Builder $q) =>
            $q->where('shop_id', app('current_shop')->id)
        );

        static::creating(fn($m) => $m->shop_id = app('current_shop')->id);
    }
}
