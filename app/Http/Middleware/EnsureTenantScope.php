<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureTenantScope
{
    /**
     * Handle an incoming request.
     *
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

        $this->resolveDepotScope($request, $user);

        return $next($request);
    }

    private function resolveDepotScope(Request $request, User $user): void
    {
        if ($user->depot_active_id) {
            if ($user->depotActive?->is_active) {
                app()->instance('current_depot', $user->depotActive);
            }

            return;
        }

        // Admin en vue globale : pas de filtre depot
        if ($user->isAdminOrSuperAdmin()) {
            return;
        }

        // Non-admin sans depot sélectionné
        $depots = $user->depots()->where('is_active', true)->get();

        if ($depots->count() === 1) {
            // Auto-sélection du seul depot disponible
            $user->depot_active_id = $depots->first()->id;
            $user->save();
            app()->instance('current_depot', $depots->first());

            return;
        }

        if ($depots->count() > 1 && ! $request->routeIs('depot.select', 'depot.save', 'logout')) {
            // Plusieurs depots : doit choisir
            redirect()->route('depot.select')->send();
        }
    }
}
