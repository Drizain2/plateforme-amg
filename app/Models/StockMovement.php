<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Query\Builder;

class StockMovement extends Model
{
    /** @use HasFactory<\Database\Factories\StockMovementFactory> */
    use HasFactory;
    protected $fillable = [
        'shop_id','depot_id','stock_id','user_id','ticket_id',
        'type','quantity','transfer_depot_id','note'
    ];

    protected static function booted(): void
    {
        static::addGlobalScope('shop', fn(Builder $q) =>
            $q->where('shop_id', app('current_shop')->id)
        );

        static::creating(fn($m) => $m->shop_id = app('current_shop')->id);
    }

    public function part(): BelongsTo          { return $this->belongsTo(Part::class); }
    public function depot(): BelongsTo         { return $this->belongsTo(Depot::class); }
    public function user(): BelongsTo          { return $this->belongsTo(User::class); }
    public function transferDepot(): BelongsTo { return $this->belongsTo(Depot::class, 'transfer_depot_id'); }
    public function ticket(): BelongsTo        { return $this->belongsTo(Ticket::class); }

}
