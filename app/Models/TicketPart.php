<?php

namespace App\Models;

use App\Models\Concerns\HasShopScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;

class TicketPart extends Model
{
    use HasShopScope;

    protected $fillable = ['shop_id', 'ticket_id', 'stock_depot_id', 'quantity', 'unit_price'];

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class);
    }

    public function stock_depot(): BelongsTo
    {
        return $this->belongsTo(StockDepot::class);
    }

    public function part(): HasOneThrough
    {
        return $this->hasOneThrough(
            Part::class,
            StockDepot::class,
            'id',             // stock_depots.id = ticket_parts.stock_depot_id
            'id',             // parts.id = stock_depots.part_id
            'stock_depot_id', // local key on ticket_parts
            'part_id'         // local key on stock_depots
        );
    }

    public function getTotalAttribute(): float
    {
        return $this->quantity * $this->unit_price;
    }
}
