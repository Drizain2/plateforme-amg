<?php

namespace App\Models;

use App\Enums\InvoiceStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Invoice extends Model
{
    protected $fillable = [
        'number', 'shop_id', 'ticket_id', 'customer_id',
        'status', 'total_ht', 'tax_rate', 'tax_amount',
        'total_ttc', 'notes', 'issued_at', 'due_at', 'paid_at',
    ];

    protected $casts = [
        'status' => InvoiceStatus::class,
        'issued_at' => 'datetime',
        'due_at' => 'datetime',
        'paid_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope('shop', fn ($q) => $q->where('shop_id', app('current_shop')->id)
        );

        static::creating(function ($m) {
            $m->shop_id = app('current_shop')->id;
            $m->number = static::generateNumber();
        });
    }

    public static function generateNumber(): string
    {
        $year = now()->year;
        $count = static::withoutGlobalScopes()
            ->whereYear('created_at', $year)
            ->count() + 1;

        return sprintf('FAC-%s-%05d', $year, $count);
    }

    public function recalculate(): void
    {
        $ht = $this->lines->sum('total');
        $tax = round($ht * ($this->tax_rate / 100), 2);

        $this->update([
            'total_ht' => $ht,
            'tax_amount' => $tax,
            'total_ttc' => $ht + $tax,
        ]);
    }

    public function shop(): BelongsTo
    {
        return $this->belongsTo(Shop::class);
    }

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function lines(): HasMany
    {
        return $this->hasMany(InvoiceLine::class);
    }
}
