<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class BootTenantScope
{
    /**
     * Fixe current_shop (et current_depot si applicable) avant que SubstituteBindings
     * ne résolve les route model bindings, de sorte que HasShopScope soit actif.
     *
     * Ce middleware est léger (pas de rejet) — EnsureTenantScope reste responsable
     * de la validation complète sur les routes protégées.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user?->shop && $user->shop->is_active) {
            app()->instance('current_shop', $user->shop);

            if ($user->depot_active_id && $user->depotActive?->is_active) {
                app()->instance('current_depot', $user->depotActive);
            }
        }

        return $next($request);
    }
}
