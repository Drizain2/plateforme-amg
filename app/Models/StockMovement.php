<?php

namespace App\Models;

use Database\Factories\StockMovementFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockMovement extends Model
{
    /** @use HasFactory<StockMovementFactory> */
    use HasFactory;

    protected $fillable = [
        'shop_id', 'depot_id', 'stock_id', 'user_id', 'ticket_id',
        'type', 'quantity', 'transfer_depot_id', 'note',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope('shop', fn (Builder $q) => $q->where('shop_id', app('current_shop')->id)
        );

        static::creating(fn ($m) => $m->shop_id = app('current_shop')->id);
    }

    public function stock(): BelongsTo
    {
        return $this->belongsTo(StockDepot::class, 'stock_id');
    }

    public function depot(): BelongsTo
    {
        return $this->belongsTo(Depot::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function transferDepot(): BelongsTo
    {
        return $this->belongsTo(Depot::class, 'transfer_depot_id');
    }

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class);
    }

    public function getTypeLabelAttribute(): string
    {
        return match ($this->type) {
            'in' => 'Entrée',
            'out' => 'Sortie',
            'adjustment' => 'Ajustement',
            'transfer_in' => 'Transfert entrant',
            'transfer_out' => 'Transfert sortant',
            default => $this->type,
        };
    }
}
