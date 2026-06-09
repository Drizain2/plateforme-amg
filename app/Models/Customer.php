<?php

namespace App\Models;

use App\Models\Concerns\HasShopScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Customer extends Model
{
    use HasShopScope;
    protected $fillable = ['shop_id','name','email','phone','address','notes'];

    public function shop(): BelongsTo  { return $this->belongsTo(Shop::class); }
    public function devices(): HasMany { return $this->hasMany(Device::class); }
    public function tickets(): HasMany { return $this->hasMany(Ticket::class); }

}
