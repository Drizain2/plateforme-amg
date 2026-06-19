<?php

namespace App\Models;

use App\Enums\PurchaseStatus;
use App\Models\Concerns\HasDepotScope;
use App\Models\Concerns\HasShopScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Purchase extends Model
{
    use HasDepotScope, HasShopScope;

    protected $fillable = [
        'number', 'shop_id', 'depot_id', 'supplier_id',
        'status', 'total_ht', 'tax_rate', 'tax_amount',
        'total_ttc', 'notes', 'ordered_at', 'received_at', 'paid_at',
    ];

    protected $casts = [
        'status' => PurchaseStatus::class,
        'ordered_at' => 'datetime',
        'received_at' => 'datetime',
        'paid_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(fn ($m) => $m->number = static::generateNumber());
    }

    public static function generateNumber(): string
    {
        $year = now()->year;
        $count = static::withoutGlobalScopes()
            ->whereYear('created_at', $year)
            ->lockForUpdate()
            ->count() + 1;

        return sprintf('ACH-%s-%05d', $year, $count);
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

    public function depot(): BelongsTo
    {
        return $this->belongsTo(Depot::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function lines(): HasMany
    {
        return $this->hasMany(PurchaseLine::class);
    }
}
