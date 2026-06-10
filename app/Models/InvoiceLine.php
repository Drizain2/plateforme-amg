<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InvoiceLine extends Model
{
    protected $fillable = [
        'shop_id', 'invoice_id', 'type', 'label', 'quantity', 'unit_price', 'total',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope('shop', fn ($q) => $q->where('shop_id', app('current_shop')->id)
        );

        static::creating(fn ($m) => $m->shop_id = app('current_shop')->id);

        // Recalculer la facture à chaque modification de ligne
        static::saved(fn ($line) => $line->invoice->load('lines')->recalculate());
        static::deleted(fn ($line) => $line->invoice->load('lines')->recalculate());
    }

    protected static function boot(): void
    {
        parent::boot();
        static::saving(function ($line) {
            $line->total = round($line->quantity * $line->unit_price, 2);
        });
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }
}
