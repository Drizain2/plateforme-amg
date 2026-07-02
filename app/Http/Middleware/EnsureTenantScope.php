<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureTenantScope
{
    /**
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            abort(403, 'Vous devez être connecté pour accéder à cette page');
        }

        if (! $user->shop) {
            abort(403, 'Vous devez avoir un shop pour accéder à cette page');
        }

        $user->shop->loadMissing('plan');

        if (! $user->shop->is_active) {
            abort(403, 'Votre shop est desactivé');
        }

        app()->instance('current_shop', $user->shop);

        if ($redirect = $this->resolveDepotScope($request, $user)) {
            return $redirect;
        }

        return $next($request);
    }

    private function resolveDepotScope(Request $request, User $user): ?Response
    {
        if ($user->depot_active_id) {
            if ($user->depotActive?->is_active) {
                app()->instance('current_depot', $user->depotActive);
            }

            return null;
        }

        if ($user->isAdminOrSuperAdmin()) {
            return null;
        }

        $depots = $user->depots()->where('is_active', true)->get();

        if ($depots->count() === 1) {
            $user->depot_active_id = $depots->first()->id;
            $user->save();
            app()->instance('current_depot', $depots->first());

            return null;
        }

        if ($depots->count() > 1 && ! $request->routeIs('depot.select', 'depot.save', 'logout')) {
            return redirect()->route('depot.select');
        }

        return null;
    }
}
