<?php

namespace App\Models;

use App\Models\Concerns\HasShopScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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

    public function getTotalAttribute(): float
    {
        return $this->quantity * $this->unit_price;
    }
}
