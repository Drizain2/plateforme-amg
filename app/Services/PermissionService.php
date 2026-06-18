<?php

namespace App\Services;

use App\Models\ShopUserPermission;
use App\Models\User;
use Spatie\Permission\Exceptions\PermissionDoesNotExist;
use Spatie\Permission\Models\Permission;

class PermissionService
{
    /**
     * Résout si un user a une permission.
     * Ordre : super_admin bypass > override shop (grant/revoke) > rôle par défaut
     */
    public function has(User $user, string $permission): bool
    {
        if ($user->hasRole('super_admin')) {
            return true;
        }

        $override = ShopUserPermission::getOverride($user->id, $user->shop_id, $permission);

        if ($override !== null) {
            return $override;
        }

        try {
            return $user->hasPermissionTo($permission);
        } catch (PermissionDoesNotExist) {
            return false;
        }
    }

    /**
     * Toutes les permissions effectives d'un user (rôle + overrides shop).
     *
     * @return string[]
     */
    public function effectivePermissions(User $user): array
    {
        if ($user->hasRole('super_admin')) {
            return Permission::pluck('name')->toArray();
        }

        $rolePerms = $user->getPermissionsViaRoles()->pluck('name')->toArray();

        $overrides = ShopUserPermission::where('user_id', $user->id)
            ->where('shop_id', $user->shop_id)
            ->get();

        foreach ($overrides as $override) {
            if ($override->granted && ! in_array($override->permission, $rolePerms)) {
                $rolePerms[] = $override->permission;
            } elseif (! $override->granted) {
                $rolePerms = array_values(array_filter($rolePerms, fn ($p) => $p !== $override->permission));
            }
        }

        return $rolePerms;
    }

    /**
     * Grant ou revoke une permission custom pour un user dans un shop.
     */
    public function setOverride(User $user, string $permission, bool $granted): void
    {
        ShopUserPermission::updateOrCreate(
            [
                'shop_id' => $user->shop_id,
                'user_id' => $user->id,
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
     * Reset complet — retour aux permissions du rôle.
     */
    public function resetToRole(User $user): void
    {
        ShopUserPermission::where('user_id', $user->id)
            ->where('shop_id', $user->shop_id)
            ->delete();
    }
}
