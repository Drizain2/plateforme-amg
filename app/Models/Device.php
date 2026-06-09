<?php

namespace App\Models;

use App\Models\Concerns\HasShopScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Device extends Model
{
    use HasShopScope;
    protected $fillable = [
        'shop_id','customer_id','type','brand',
        'model','serial_number','color','condition_in'
    ];


    public function customer(): BelongsTo { return $this->belongsTo(Customer::class); }
    public function tickets(): HasMany    { return $this->hasMany(Ticket::class); }

    public function getFullNameAttribute(): string
    {
        return "{$this->brand} {$this->model}";
    }
}
