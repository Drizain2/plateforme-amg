<?php

namespace App\Services;

use App\Models\ShopUserPermission;
use App\Models\User;

class PermissionService
{
    /**
     * Résout si un user a une permission
     * Ordre : override shop (grant/revoke) > rôle par défaut
     */
    public function has(User $user, string $permission): bool
    {
        // Super admin — bypass total
        if ($user->hasRole('super_admin')) return true;

        // Chercher un override shop pour cet user
        $override = ShopUserPermission::hasPermission($user->id, $user->shop_id, $permission);

        if ($override) return $override;

        // Fallback : permission du rôle Spatie
        return $user->hasPermissionTo($permission);
    }

    /**
     * Toutes les permissions effectives d'un user (rôle + overrides)
     */
    public function effectivePermissions(User $user): array
    {
        // Permissions du rôle
        $rolePerms = $user->getPermissionsViaRoles()
            ->pluck('name')
            ->toArray();

        // Overrides shop
        $overrides = ShopUserPermission::where('user_id', $user->id)
            ->where('shop_id', $user->shop_id)
            ->get();

        // Appliquer les overrides
        foreach ($overrides as $override) {
            if ($override->granted && !in_array($override->permission, $rolePerms)) {
                $rolePerms[] = $override->permission;
            } elseif (!$override->granted) {
                $rolePerms = array_filter($rolePerms, fn($p) => $p !== $override->permission);
            }
        }

        return array_values($rolePerms);
    }

    /**
     * Setter — grant ou revoke une permission custom
     */
    public function setOverride(User $user, string $permission, bool $granted): void
    {
        ShopUserPermission::updateOrCreate(
            [
                'shop_id'    => $user->shop_id,
                'user_id'    => $user->id,
                'permission' => $permission,
            ],
            ['granted' => $granted]
        );
    }

    public function removeOverride(User $user, string $permission): void
    {
        ShopUserPermission::where('user_id', $user->id)
            ->where('shop_id', $user->shop_id)
            ->where('permission', $permission)
            ->delete();
    }

    /**
     * Reset complet — retour aux permissions du rôle
     */
    public function resetToRole(User $user): void
    {
        ShopUserPermission::where('user_id', $user->id)
            ->where('shop_id', $user->shop_id)
            ->delete();
    }
}
