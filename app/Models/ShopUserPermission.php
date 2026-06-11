<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ShopUserPermission extends Model
{
    protected $fillable = [
        'shop_id',
        'user_id',
        'permission',
        'granted',
    ];
    public function shop(): BelongsTo
    {
        return $this->belongsTo(Shop::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Vérifier si l'utilisateur a une permission spécifique dans ce shop
     */
    public function hasPermission(int $shopId, int $userId, string $permission): bool
    {
        return self::where([
            'shop_id' => $shopId,
            'user_id' => $userId,
            'permission' => $permission,
            'granted' => true,
        ])->exists();
    }
}
