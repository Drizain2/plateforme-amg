<?php

namespace App\Models;

use App\Models\Concerns\HasShopScope;
use Database\Factories\CustomerFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Notifications\Notifiable;

class Customer extends Model
{
    /** @use HasFactory<CustomerFactory> */
    use HasFactory, HasShopScope, Notifiable;

    protected $fillable = ['shop_id', 'name', 'email', 'phone', 'address', 'notes'];

    public function shop(): BelongsTo
    {
        return $this->belongsTo(Shop::class);
    }

    public function devices(): HasMany
    {
        return $this->hasMany(Device::class);
    }

    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }
}
