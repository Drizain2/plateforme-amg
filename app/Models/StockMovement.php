<?php

namespace App\Models;

use App\Models\Concerns\HasDepotScope;
use App\Models\Concerns\HasShopScope;
use Database\Factories\StockMovementFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockMovement extends Model
{
    /** @use HasFactory<StockMovementFactory> */
    use HasDepotScope, HasFactory, HasShopScope;

    protected $fillable = [
        'shop_id', 'depot_id', 'stock_id', 'user_id', 'ticket_id', 'invoice_id', 'invoice_line_id', 'purchase_id',
        'type', 'quantity', 'unit_cost', 'transfer_depot_id', 'note',
    ];

    protected $casts = [
        'unit_cost' => 'decimal:2',
    ];

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

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function invoiceLine(): BelongsTo
    {
        return $this->belongsTo(InvoiceLine::class);
    }

    public function purchase(): BelongsTo
    {
        return $this->belongsTo(Purchase::class);
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
