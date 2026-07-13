<?php

namespace App\Http\Middleware;

use App\Models\User;
use App\Services\PermissionService;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $user = $request->user();
        $user?->shop?->loadMissing('plan');
        $permService = app(PermissionService::class);

        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'auth' => [
                'user' => $user?->load('roles'),
                'shop' => $user?->shop,
                'depotActive' => fn () => $user?->depotActive,
                'depots' => fn () => $this->availableDepots($user),
                'isGlobalView' => fn () => $user && $user->isAdminOrSuperAdmin() && ! $user->depot_active_id,
                'unread_count' => fn () => $user?->unreadNotifications()->count() ?? 0,
                'permissions' => $user ? $permService->effectivePermissions($user) : [],
                'is_on_trial' => fn () => $user?->shop
                    ? ($user->shop->isOnTrial() && ! $user->shop->hasActiveSubscription())
                    : false,
            ],
            'flash' => [
                'success' => session('success'),
                'error' => session('error'),
                'instructions' => session('instructions'),
                'reference' => session('reference'),
            ],
            'impersonating' => fn () => session()->has('impersonating_user_id')
                ? User::find(session('impersonating_user_id'))?->only('id', 'name')
                : null,
        ];
    }

    /**
     * @return array<int, array{id: int, name: string, is_active: bool}>
     */
    private function availableDepots($user): array
    {
        if (! $user || ! $user->shop) {
            return [];
        }

        if ($user->isAdminOrSuperAdmin()) {
            return $user->shop->depots()
                ->where('depots.is_active', true)
                ->get([
                    'depots.id',
                    'depots.name',
                    'depots.is_active',
                ])
                ->toArray();
        }

        return $user->depots()
            ->where('depots.is_active', true)
            ->get([
                'depots.id',
                'depots.name',
                'depots.is_active',
            ])
            ->toArray();
    }
}
