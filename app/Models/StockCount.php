<?php

namespace App\Models;

use App\Enums\StockCountStatus;
use App\Models\Concerns\HasDepotScope;
use App\Models\Concerns\HasShopScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StockCount extends Model
{
    use HasDepotScope, HasShopScope;

    protected $fillable = [
        'number', 'shop_id', 'depot_id', 'user_id',
        'status', 'note', 'started_at', 'validated_at',
    ];

    protected $casts = [
        'status' => StockCountStatus::class,
        'started_at' => 'datetime',
        'validated_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(fn ($model) => $model->number = static::generateNumber());
    }

    public static function generateNumber(): string
    {
        $year = now()->year;
        $count = static::withoutGlobalScopes()
            ->whereYear('created_at', $year)
            ->lockForUpdate()
            ->count() + 1;

        return sprintf('INV-%s-%05d', $year, $count);
    }

    public function shop(): BelongsTo
    {
        return $this->belongsTo(Shop::class);
    }

    public function depot(): BelongsTo
    {
        return $this->belongsTo(Depot::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function lines(): HasMany
    {
        return $this->hasMany(StockCountLine::class);
    }
}
