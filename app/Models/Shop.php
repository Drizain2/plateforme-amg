<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Shop extends Model
{
    /** @use HasFactory<\Database\Factories\ShopFactory> */
    use HasFactory;
    protected $fillable = [
        'name','slug','email','phone','logo_url','address','plan','trial_ends_at','is_active'
    ];

    protected $casts = ['trial_ends_at' => 'datetime'];

    public function users(): HasMany    { return $this->hasMany(User::class); }
    public function depots(): HasMany   { return $this->hasMany(Depot::class); }
    public function suppliers(): HasMany{ return $this->hasMany(Supplier::class); }
    public function parts(): HasMany    { return $this->hasMany(Part::class); }

    public function admin(): HasOne
    {
        return $this->hasOne(User::class)->whereHas('roles', fn($q) =>
            $q->where('name', 'admin')
        );
    }

    public function isOnTrial(): bool
    {
        return $this->trial_ends_at && $this->trial_ends_at->isFuture();
    }
}
