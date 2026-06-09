<?php

namespace App\Models;

use App\Models\Concerns\HasShopScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TicketPart extends Model
{
    use HasShopScope;
    protected $fillable = ['shop_id','ticket_id','part_id','quantity','unit_price'];

    public function ticket(): BelongsTo { return $this->belongsTo(Ticket::class); }
    public function part(): BelongsTo   { return $this->belongsTo(Part::class); }

    public function getTotalAttribute(): float
    {
        return $this->quantity * $this->unit_price;
    }
}
