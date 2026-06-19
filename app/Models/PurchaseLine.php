<?php

namespace App\Models;

use App\Models\Concerns\HasShopScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PurchaseLine extends Model
{
    use HasShopScope;

    protected $fillable = [
        'shop_id', 'purchase_id', 'part_id', 'label', 'quantity', 'unit_price', 'total',
    ];

    protected static function booted(): void
    {
        static::saving(fn ($line) => $line->total = round($line->quantity * $line->unit_price, 2));
        static::saved(fn ($line) => $line->purchase->load('lines')->recalculate());
        static::deleted(fn ($line) => $line->purchase->load('lines')->recalculate());
    }

    public function purchase(): BelongsTo
    {
        return $this->belongsTo(Purchase::class);
    }

    public function part(): BelongsTo
    {
        return $this->belongsTo(Part::class);
    }
}
