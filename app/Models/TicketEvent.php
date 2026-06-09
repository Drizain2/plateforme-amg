<?php

namespace App\Models;

use App\Models\Concerns\HasShopScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TicketEvent extends Model
{
    use HasShopScope;
    protected $fillable = ['shop_id','ticket_id','user_id','type','note','metadata'];

    protected $casts = ['metadata' => 'array'];

  

    public function ticket(): BelongsTo { return $this->belongsTo(Ticket::class); }
    public function user(): BelongsTo   { return $this->belongsTo(User::class); }
}
