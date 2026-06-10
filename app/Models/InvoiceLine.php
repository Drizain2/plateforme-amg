<?php

namespace App\Models;

use App\Models\Concerns\HasShopScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InvoiceLine extends Model
{
    use HasShopScope;

    protected $fillable = [
        'shop_id', 'invoice_id', 'type', 'label', 'quantity', 'unit_price', 'total',
    ];

    protected static function booted(): void
    {
        static::saving(fn ($line) => $line->total = round($line->quantity * $line->unit_price, 2));
        static::saved(fn ($line) => $line->invoice->load('lines')->recalculate());
        static::deleted(fn ($line) => $line->invoice->load('lines')->recalculate());
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }
}
