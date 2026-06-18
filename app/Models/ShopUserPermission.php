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
     * Vérifier si l'utilisateur a un override de permission (grant ou revoke).
     * Retourne null si aucun override, true/false si override trouvé.
     */
    public static function getOverride(int $userId, int $shopId, string $permission): ?bool
    {
        $record = self::where([
            'user_id' => $userId,
            'shop_id' => $shopId,
            'permission' => $permission,
        ])->first();

        return $record ? (bool) $record->granted : null;
    }
}
