<?php

namespace App\Http\Controllers;

use App\Models\ShopUserPermission;
use App\Models\User;
use App\Services\PermissionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Spatie\Permission\Models\Permission;

class UserPermissionController extends Controller
{
    public function __construct(private PermissionService $permissionService)
    {
        $this->middleware('perm:users.manage');
    }

    public function index(User $user)
    {
        $this->authorizeAdmin($user);

        $allPermissions = Permission::orderBy('name')->pluck('name');
        $effectivePerms = $this->permissionService->effectivePermissions($user);
        $overrides = ShopUserPermission::where('user_id', $user->id)
            ->where('shop_id', app('current_shop')->id)
            ->get()
            ->keyBy('permission');

        return Inertia::render('Users/Permissions', [
            'targetUser' => [
                'id' => $user->id,
                'name' => $user->name,
                'role' => $user->roles->first()?->name,
            ],
            'allPermissions' => $allPermissions,
            'effectivePerms' => $effectivePerms,
            'rolePerms' => $user->getPermissionsViaRoles()->pluck('name'),
            'overrides' => $overrides->map(fn ($o) => [
                'granted' => $o->granted,
            ]),
        ]);
    }

    public function update(User $user, Request $request): RedirectResponse
    {
        $this->authorizeAdmin($user);

        $request->validate([
            'permission' => ['required', 'string', 'exists:permissions,name'],
            'action' => ['required', Rule::in(['grant', 'revoke', 'reset'])],
        ]);

        match ($request->action) {
            'grant' => $this->permissionService->setOverride($user, $request->permission, true),
            'revoke' => $this->permissionService->setOverride($user, $request->permission, false),
            'reset' => $this->permissionService->removeOverride($user, $request->permission),
        };

        return back()->with('success', 'Permission mise à jour.');
    }

    public function resetAll(User $user): RedirectResponse
    {
        $this->authorizeAdmin($user);
        $this->permissionService->resetToRole($user);

        return back()->with('success', 'Permissions réinitialisées au rôle par défaut.');
    }

    private function authorizeAdmin(User $user): void
    {
        if ($user->shop_id !== app('current_shop')->id) {
            abort(403);
        }
        if (! auth()->user()->hasRole('admin')) {
            abort(403);
        }
    }
}
